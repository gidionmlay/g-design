# M4 — Admin Dashboard & Request Management

## Objective

Build the authenticated admin dashboard around the M3 admin foundation: an **Overview** with live statistics, a searchable/filterable/paginated **Requests** list, deep **request details** (client, service, dynamic requirements, attachments), and **controlled status management** with a safe transition workflow.

```
Admin Login (M3)
  → GET /api/v1/admin/auth/me   (session bootstrap)
  → SPA dashboard (hash router)
      #/overview        → GET /admin/dashboard/overview
      #/requests        → GET /admin/requests?page&limit&search&status&service
      #/requests/:id    → GET /admin/requests/{id}  +  PATCH .../status
                         + attachments preview/download
```

## Architecture

```
Browser (dashboard.html + modern vanilla JS modules)
  admin.js           core: API client, session bootstrap, safe DOM builder, hash router
  dashboard.js       Overview view  (stat cards + recent requests)
  requests.js        Request list   (toolbar search, status/service filters, pager)
  request-details.js Detail view    (client/service, requirements, attachments, status control)

Middleware: AuthMiddleware::handle() protects every admin endpoint (401 without session).
Controllers: AdminDashboardController, AdminRequestController
Models:      RequestModel (pagination, search, detail, status transitions)
             RequestAttachmentModel (ownership-scoped lookup + presentation)
             ServiceField (requirements config) / ServiceItem / ServiceCategory
Core:        Response (JSON envelope + streamFile), Auth, Config
```

The dashboard is a **single-page application**: `public/admin/dashboard.html` hosts the sidebar, top-bar, and a `<main id="view">` slot. Views are plain functions rendered through a tiny hash router (`#/overview`, `#/requests`, `#/requests/{id}`). No UI framework is introduced — the project stack stays HTML/CSS/vanilla JS.

## Dashboard Structure

| File | Purpose |
|---|---|
| `public/admin/dashboard.html` | SPA shell: sidebar (menu, view site, logout), top bar (title + session identity), `<main id="view">`, preview modal |
| `public/admin/assets/css/admin.css` | Dark theme (`#0E0F12` bg, `#FF6B1A` accent, Space Grotesk), status badges, table/pager/toolbar, cards, responsive (mobile off-canvas sidebar) |
| `public/admin/assets/js/admin.js` | API wrapper, `/admin/auth/me` bootstrap, central 401 → login redirect, `el()` safe DOM builder and `esc()`, formatters, router, toast, lightbox |
| `public/admin/assets/js/dashboard.js` | Overview view |
| `public/admin/assets/js/requests.js` | Requests list view |
| `public/admin/assets/js/request-details.js` | Request detail view |

`public/admin/index.html` (M3 login) now:
- redirects a successfully-signed-in admin to `/admin/dashboard.html`;
- bounces an already-authenticated visitor to the dashboard;
- shows a "session expired, please sign in again" banner when reopened with `?expired=1`.

## API Endpoints

All admin endpoints require the `gds_admin_session` cookie. Responses use the `{ok, data}` / `{ok:false, error:{code,message}}` envelope.

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/admin/dashboard/overview` | Statistics by status + 6 most recent requests |
| `GET` | `/api/v1/admin/requests` | Paginated request list with search & filters |
| `GET` | `/api/v1/admin/requests/{id}` | Full details: client, service, requirements, attachments |
| `PATCH` | `/api/v1/admin/requests/{id}/status` | Controlled status update (spans: `{status}`) |
| `GET` | `/api/v1/admin/requests/{id}/attachments` | Attachment metadata for a request |
| `GET` | `/api/v1/admin/requests/{id}/attachments/{aid}` | Secure file stream (inline preview or `?download=1`) |

Overview response:

```json
{
  "ok": true,
  "data": {
    "statistics": { "total_requests": 9, "pending": 8, "reviewing": 1,
                    "in_progress": 0, "completed": 0, "cancelled": 0 },
    "recent_requests": [
      { "id": 9, "reference": "GDS-REQ-20260827-BDE6",
        "client": { "name": "...", "email": "...", "phone": null, "company": null },
        "service": { "id": 6, "name": "Poster Design", "slug": "poster-design", "category": "Graphic Design" },
        "status": "pending", "attachments_count": 0,
        "created_at": "2026-08-27 14:08:39", "updated_at": "2026-08-27 14:08:39" }
    ]
  }
}
```

## Request Management

- **List** supports pagination (`page`, `limit`, max 100), full-text `search`, `status`, `service` (slug) and date range (`from`, `to`).
- **Detail** resolves stored `requirements_data` against the live service field configuration so old submissions still render labelled, human-readable values (see below).
- **Client info**: name, company, email, phone — taken directly from the public submission.
- **Status** is only changed through the workflow below; a same-status `PATCH` is a no-op, invalid values return 400, and missing requests return 404.

## Status Model

Statuses: `pending → reviewing → in_progress → completed`, with `cancelled` available from any non-terminal state.

```
pending ──► reviewing ──► in_progress ──► completed
   │            │              │
   └────────────┴──────────────┴──► cancelled   (from any non-terminal state)
```

Enforced by `RequestModel::canTransition($from, $to)` and checked in `AdminRequestController::updateStatus`. Terminal states (`completed`, `cancelled`) are locked. Invalid transitions return `INVALID_TRANSITION` (400) with an explanatory message; the UI surfaces it and reloads the view only after a successful update.

The DB column was widened via `database/migrations/2026-08-27-m4-request-statuses.sql`:

```sql
ALTER TABLE requests
    MODIFY COLUMN status ENUM('pending','reviewing','in_progress','completed','cancelled')
    NOT NULL DEFAULT 'pending';
```

## Dynamic Requirement Rendering

- Stored requirements are key/value JSON (`requirements_data`).
- `RequestModel::resolveRequirements()` enriches each entry against `ServiceField::forItems()`:
  - known keys → configured label + type from the live catalogue;
  - unknown/legacy keys → humanized fallback label (e.g. `colour_style` → `Colour style`);
  - `checkbox` values → arrays; `sizegrid` → `size × quantity` pairs; everything else → string.
- The detail view renders each as a labelled row (`dt`/`dd`), never as raw JSON. Values are inserted with `textContent`, so a submitted `<script>` payload is displayed as inert text.

## Attachment Handling

- Uploads persist on disk (M2) with randomized names; `storage_path`/absolute paths are **never** exposed by the API.
- The admin API returns only metadata: `filename`, `mime_type`, `extension`, `size`, `is_image`, `is_pdf`, `url`.
- Streaming (`Response::streamFile`):
  - inline preview for images; `?download=1` forces `Content-Disposition: attachment`;
  - proper MIME type (no bogus charset), `Content-Length`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Cache-Control: private, no-store`;
  - `RequestAttachmentModel::findForRequest($attachmentId, $requestId)` scopes ownership, so fetching an attachment under the wrong request (or any unauthenticated access) returns 404 — the URL is not a direct file path and leaks nothing.
- The detail view shows previewable thumbnails with a lightbox (`GD.openPreview`) and per-file Download buttons.

## Security

- Every admin endpoint gated by `AuthMiddleware::handle()` → 401 `UNAUTHORIZED` without a valid session.
- All rendering uses `textContent`/`el()`; `esc()` additionally guards any template output. Verified against a stored `<script>` probe.
- SQL is fully parameterized (PDO prepared statements) — list search/filter/order are built with bound parameters only.
- No filesystem paths returned; attachment IDs are validated (`^\d{1,10}$`) against `INVALID_ID`.
- Session cookies are `HttpOnly` + `SameSite=Lax` (and `Secure` over HTTPS), with `session_regenerate_id()` after login (M3).
- Constant-time concerns: status validation uses a whitelist; input length and types are checked before hitting the database.

## Pagination

- `page` (default 1) and `limit` (default 20, max 100) — both validated as positive integers.
- Response includes `pagination: { page, limit, total, pages }`; the UI renders Prev/Next with "Page X of Y · N requests" and disables at bounds.
- The overview "recent requests" slice is the 6 newest rows.

## Search / Filter Behavior

- `search` matches `request_reference`, `client_name`, `client_email`, `client_phone`, `company_name`, and `service.name` with a wildcard `LIKE` (single prepared statement).
- `status` accepts only the 5 valid statuses; `service` requires a valid slug; `from`/`to` require `YYYY-MM-DD`.
- Filters combine (AND); searches are case-insensitive; the toolbar is rebuilt with the active state preserved and a debounced live search (350 ms).
- Service filter options are loaded from the public `/api/v1/services` catalogue and hidden when empty.

## Files Changed

| File | Change |
|---|---|
| `database/migrations/2026-08-27-m4-request-statuses.sql` | New — widens `requests.status` |
| `backend/models/RequestModel.php` | Status model/transitions, overview, paginated list, detail, requirements resolution |
| `backend/models/RequestAttachmentModel.php` | Ownership-scoped `findForRequest`, safe `present()` |
| `backend/models/ServiceItem.php`, `ServiceCategory.php` | `findById()` helpers |
| `backend/core/Response.php` | `streamFile()` (clean MIME headers, inline/attachment) |
| `backend/controllers/AdminDashboardController.php`, `AdminRequestController.php` | New admin endpoints |
| `backend/routes/api.php` | 6 admin routes registered |
| `public/admin/index.html` | Redirect to dashboard; authed-bounce; expired-session banner |
| `public/admin/dashboard.html`, `public/admin/assets/{css,js}/…` | New SPA dashboard shell + 3 view modules |