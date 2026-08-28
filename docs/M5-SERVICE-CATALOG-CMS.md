# M5 — Admin Service & Catalog CMS

## Objective

Let the G DESIGN administrator manage the service catalog **without editing source code**:

- Service Categories (create / edit / activate / deactivate / display order)
- Services (create / edit / activate / deactivate / display order / assign category)
- Service descriptions (`short_description` + full `description`)
- Service images (secure upload, replace, cleanup; never exposes storage paths)
- Service status (activation — inactive services disappear from the public catalog)
- Pricing (flexible modes: `fixed`, `starting_from`, `range`, `quote`; currency; raw numbers only)
- Dynamic service requirements (a per-service field builder: label, key, type, required, order, options)

Everything is delivered through the existing M4 admin SPA (vanilla JS, no framework)
and the existing custom PHP 8 + PDO/MySQL backend.

## Architecture

```
admin SPA (dashboard.html)
  catalog.js           Categories view, Services list, Service editor (with field builder)
  admin.js             API client (prepending /api/v1), session bootstrap, hash router, nav

        │  GET /api/v1/admin/...        (gds_admin_session cookie)
        ▼
Router (backend/routes/api.php)
  AdminCatalogController     categories, services, fields, options, images
  ServiceImageController     public image streaming (/api/v1/service-images/{id})
  ServiceController          public catalog (/api/v1/services)

Models: ServiceCategory, ServiceItem, ServiceFieldAdmin, ServiceField (legacy read path)
Core:   Auth / AuthMiddleware (session guard), ServiceImageUpload (secure storage),
        Response ({ok,data}/{ok:false,error}), Request, Validator, Router
```

The frontend is a **single-page application** extension of M4. Three views are
registered on `GDApp.views`:

| Route | View |
|---|---|
| `#/catalog` (parent) | toggles the submenu (`body.catalog-open`) |
| `#/categories` | Categories list + create/edit modal |
| `#/services` | Services list (search, category/status filters, pagination) |
| `#/services/{id}` | Service editor (basic info, image, pricing, status, order, field builder) |
| `#/services/new` | Service editor in create mode |

## Database Structure

New migration: `database/migrations/2026-08-29-m5-service-catalog-cms.sql` (idempotent).

### `service_items` (extended)

| Column | Type | Meaning |
|---|---|---|
| `id` | int unsigned PK | — |
| `category_id` | int unsigned FK → `service_categories.id` (cascade) | owning category |
| `slug` | varchar(100) | unique per category (for the public URL) |
| `name` | varchar(150) | service name |
| `description` | text | full description |
| `short_description` | varchar(255) | one-line summary (new) |
| `image_path` | varchar(255) | legacy/bundled static image path (M0-era seed data) |
| `pricing_type` | enum `fixed`,`starting_from`,`range`,`quote` | pricing mode (new, default `quote`) |
| `price` | decimal(14,2) | value for `fixed` / `starting_from` |
| `min_price` / `max_price` | decimal(14,2) | bounds for `range` |
| `currency` | varchar(3) | ISO code, default `TZS` |
| `sort_order` | smallint unsigned | display order |
| `is_active` | tinyint(1) | activation flag |
| `created_at` / `updated_at` | timestamp | — |

`chk_items_pricing` CHECK constraint enforces valid pricing combinations (see Pricing).

### `service_images` (new)

| Column | Type | Meaning |
|---|---|---|
| `id` | int unsigned PK | referenced by the public image URL |
| `item_id` | int unsigned FK → `service_items.id` (cascade) | owning service |
| `stored_filename` | varchar(255) | randomized name only (e.g. `svc_<64hex>.webp`) — **never returned by the API** |
| `mime_type` | varchar(50) | `image/jpeg`, `image/png`, `image/webp` |
| `file_size` | int unsigned | bytes |
| `created_at` / `updated_at` | timestamp | — |

Files live **outside the public document root** at `storage/service-images/`
(added to `.gitignore` with a `.gitkeep`).

### `service_fields` and `service_field_options` (legacy M1 tables, reused)

Field config already existed for the M5/M1 dynamic request system. The M5 admin
UI manages them through `ServiceFieldAdmin`:

- `service_fields`: `item_id` FK, `field_key`, `label`, `type`
  (enum `radio`,`checkbox`,`text`,`email`,`tel`,`number`,`date`,`textarea`,`select`,`sizegrid`,`upload`),
  `required`, `placeholder`, `hint`, `sort_order`, and legacy conditional config columns.
- `service_field_options`: `field_id` FK, `option_value`, `sort_order`.

**Field keys are permanent once saved** (uniqueness enforced per service) so
historical request data stored under those keys stays readable even if labels or
options change.

## Categories

`ServiceCategory` model + `AdminCatalogController@categories/createCategory/showCategory/updateCategory`.

- **List** (`GET /api/v1/admin/service-categories`) returns every category with
  `service_count` (live count of services in it) plus `is_active`, `sort_order`.
- **Create** (`POST`) validates `name` (required) and `slug` (auto-normalized,
  unique). Optional: `tag`, `description`, `sort_order`, `is_active`.
- **Edit** (`PATCH /{id}`) same fields; slug uniqueness checked against the current row.
- **Status** (`PATCH /{id}/status`) sets `is_active`.
- **Order** (`PATCH /{id}/order`) sets `sort_order`.
- Deleting a category is intentionally **not** exposed (see Historical data / DoD).
  Deactivation is the supported "hide" mechanism.

## Services

`ServiceItem` model + `AdminCatalogController@services/createService/showService/updateService`.

- **List** (`GET /api/v1/admin/services`) — paginated, searchable, filterable:
  `page`, `limit` (max 100), `search` (name/slug), `category_id`, `status`
  (`active`|`inactive`). Returns `{ items, pagination:{page,limit,total,pages} }`.
- Each admin row carries `category`, `category_slug`, `pricing` (presented),
  `sort_order`, `is_active` and `image` (managed URL object or legacy string/null).
- **Create** (`POST`) requires `name` and a valid `category_id`. `slug` unique within
  the category. Optional `short_description`, `description`, `sort_order`, `is_active`,
  and a `pricing` object.
- **Edit** (`PATCH /{id}`) same fields.
- **Status** (`PATCH /{id}/status`) toggle; **Order** (`PATCH /{id}/order`).
- No destructive delete: services with (or without) requests are kept and
  deactivated instead.

## Pricing Model

`ServiceItem::presentPricing()` and `ServiceItem::PRICING_TYPES`.

Four modes (`pricing_type`):

| Mode | Inputs | Example display (frontend formats) |
|---|---|---|
| `fixed` | `price` | TZS 150,000 |
| `starting_from` | `price` | From TZS 100,000 |
| `range` | `min_price` + `max_price` | TZS 50,000 – TZS 200,000 |
| `quote` | none | Available on Request |

Rules:

- Currency is stored as a raw ISO code (`currency`, default `TZS`). **No formatted
  currency strings are ever stored** — `150000` is stored, `"TSh 150,000"` is not.
- The `chk_items_pricing` CHECK constraint enforces:
  - `quote` → no price required;
  - `fixed`/`starting_from` → `price` NOT NULL;
  - `range` → `min_price` + `max_price` NOT NULL and `min_price <= max_price`.
- Invalid pricing type or missing values are rejected with 400.
- The admin editor shows only the price inputs relevant to the selected mode
  (dynamic `value`/`min`/`max` fields).

## Images

`ServiceImageUpload` (core) + `ServiceImageController` + `AdminCatalogController@uploadImage`.

- Upload endpoint: `POST /api/v1/admin/services/{id}/image` (authenticated, multipart).
- **Allowed:** JPG/JPEG, PNG, WebP — verified server-side with `finfo`
  (never trusts the browser MIME). **Blocked:** PHP/HTML/SVG/JS/EXE and any
  non-image payload; a blocked-extension allowlist also hard-fails `evil.php`,
  `evil.svg`, fake `.webp`, etc.
- Max size: 5 MB; empty/invalid uploads rejected.
- Storage: randomized `svc_<64 hex>.webp|jpg|png` under `storage/service-images/`
  (outside the document root, git-ignored).
- Replacing an image deletes the previous file and inserts a new `service_images`
  row. IDs are never reused; a replaced image id returns 404.
- **No filesystem paths are ever exposed.** Public access goes through the
  controller route `GET /api/v1/service-images/{id}`, which checks the owning
  service (and category) are both **active** before streaming — with `Content-Type`
  from the DB, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Content-Disposition: inline; filename="service-image"`.
- Items that still use legacy `image_path` continue to return that static path as
  a string; managed images return `{ url, mime }`.

## Dynamic Requirements (Field Builder)

`ServiceFieldAdmin` + `AdminCatalogController@fields/createField/updateField/deleteField/options`.

- Managed per service from the editor's **Client Requirements** section.
- Per field: `label`, `field_key`, `type`, `required`, `sort_order`, and (for
  `select`/`radio`/`checkbox`) editable options.
- Field types exposed in the admin UI: text, textarea, email, tel, number, date,
  select, radio, checkbox. (The legacy M1 special types `sizegrid`, `upload`, and
  the conditional `show_when_json` / `one_size_when_json` config are preserved
  read-only in the database and are not editable in the M5 UI.)
- **Field key rules:** lowercase, underscores allowed, `^[a-z][a-z0-9_]*$`,
  unique **per service**, no spaces — enforced server-side (400 on violation or
  duplicate). Keys become permanent after a field is created (key input disabled
  in the UI once saved).
- Changing a label does not affect stored request data: requests store values by
  `field_key`, and the detail view resolves keys against the **live** configuration,
  falling back to a humanized label for legacy/unknown keys.
- Options (`POST /{id}/fields/{fieldId}/options`, and the replace variants):
  added/edited/removed without touching historical values. Requests store the
  submitted option value independently, so removing an option from a field never
  deletes old submissions.

## Admin API

All under `/api/v1/admin/`, every route guarded by `AuthMiddleware::handle()`
(401 without a valid `gds_admin_session`).

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/admin/service-categories` | List categories (+ `service_count`) |
| `POST` | `/api/v1/admin/service-categories` | Create category |
| `GET` | `/api/v1/admin/service-categories/{id}` | Category detail |
| `PATCH` | `/api/v1/admin/service-categories/{id}` | Edit category |
| `PATCH` | `/api/v1/admin/service-categories/{id}/status` | Activate / deactivate |
| `PATCH` | `/api/v1/admin/service-categories/{id}/order` | Set display order |
| `GET` | `/api/v1/admin/services` | Paginated, searchable service list |
| `POST` | `/api/v1/admin/services` | Create service |
| `GET` | `/api/v1/admin/services/{id}` | Service detail (incl. pricing) |
| `PATCH` | `/api/v1/admin/services/{id}` | Edit service |
| `PATCH` | `/api/v1/admin/services/{id}/status` | Activate / deactivate |
| `PATCH` | `/api/v1/admin/services/{id}/order` | Set display order |
| `GET` | `/api/v1/admin/services/{id}/fields` | List a service's field config |
| `POST` | `/api/v1/admin/services/{id}/fields` | Create field |
| `PATCH` | `/api/v1/admin/services/{id}/fields/{fieldId}` | Edit field (label/type/required/order/options) |
| `DELETE` | `/api/v1/admin/services/{id}/fields/{fieldId}` | Delete field |
| `POST` | `/api/v1/admin/services/{id}/fields/{fieldId}/options` | Add/replace options |
| `POST` | `/api/v1/admin/services/{id}/image` | Upload service image |

## Public API

`GET /api/v1/services` and `GET /api/v1/services/{slug}` remain **source-compatible**
with M0/M1 (verified — the public quote form keeps working unchanged):

- Only **active** categories and active services are returned.
- Each category keeps `slug`, `name`, `tag`, `description`, `image`
  (legacy `image_path` string).
- Each service now also exposes (additive):
  - `short_description`
  - `image` — managed `{ url, mime }` if an image was uploaded via the CMS,
    else the legacy `image_path` string (or `null`)
  - `pricing` — `{ type, currency, value, min, max }` (new)
  - `fields` — the live active requirement configuration (label/key/type/required/options)
- Inactive services disappear from listings and cannot start new requests.
- `GET /api/v1/service-images/{id}` streams a managed image (public, active-only).

## Security

- **Auth:** every admin CMS endpoint returns 401 without a session
  (`AuthMiddleware::handle()`); image upload requires an authenticated admin.
- **SQL injection:** all queries use PDO prepared statements / bound parameters
  (search, filters, ordering, IDs).
- **Whitelists:** `pricing_type` and field `type` are enum-validated server-side;
  category/service status and order values are type-checked.
- **Input validation:** ID regex `^\d{1,10}$`, pagination bounds, slug/key rules,
  pricing rule via CHECK + controller validation.
- **File safety:** `finfo`-detected MIME allowlist (JPG/PNG/WebP), blocked
  extension list, 5 MB cap, random filenames, storage outside document root,
  no path leakage, old-file cleanup on replace.
- **XSS-safe rendering:** the admin UI inserts untrusted strings with
  `textContent`/`el()` helpers; template interpolation goes through `esc()`
  (incl. `<script>` probes in service name, description, category name,
  requirement label and option label — stored as text, never executed).
- **Session hardening** (from M3): HttpOnly + SameSite=Lax cookies,
  session-regeneration on login, central 401 → login redirect.

## Historical Data Handling

- **Inactive services** are hidden from the public API but their existing requests
  remain fully readable (verified: a request on a deactivated service still returns
  full details through `/api/v1/admin/requests/{id}`).
- **No destructive deletes** anywhere: categories and services are deactivated;
  fields can be deleted but old stored `requirements_data` is still rendered on the
  detail view by key (with humanized fallback labels).
- **Field/options changes** never rewrite historical `requirements_data`.

## Files Changed / Created

| File | Change |
|---|---|
| `database/migrations/2026-08-29-m5-service-catalog-cms.sql` | New — pricing + `service_images` + CHECK |
| `backend/models/ServiceCategory.php` | Admin list/create/update/status/order/slug checks |
| `backend/models/ServiceItem.php` | Admin CRUD, pricing, images, public presentation |
| `backend/models/ServiceFieldAdmin.php` | New — field & option management |
| `backend/core/ServiceImageUpload.php` | New — secure upload (JPG/PNG/WebP, random names, cleanup) |
| `backend/controllers/AdminCatalogController.php` | New — full catalog CMS endpoints |
| `backend/controllers/ServiceImageController.php` | New — public image streaming |
| `backend/controllers/ServiceController.php` | Added `short_description`, `pricing`, managed images |
| `backend/routes/api.php` | Registered M5 admin + image routes |
| `public/admin/dashboard.html` | "Service Catalog" nav group (Categories/Services), catalog modal, catalog.js script |
| `public/admin/assets/js/admin.js` | Routes for categories/services/editor, submenu toggle, modal wiring |
| `public/admin/assets/js/catalog.js` | New — Categories + Services + Service editor views (field builder) |
| `public/admin/assets/css/admin.css` | Submenu, editor, field-row, pricing, modals, thumbnails |
| `.gitignore` | `storage/service-images/*` (with `.gitkeep`) |

## Known Limitations

- **Field types** in the M5 admin UI cover text/textarea/email/tel/number/date/
  select/radio/checkbox. The legacy M1 `sizegrid`/`upload` field types and the
  conditional `show_when_json`/`one_size_when_json` configuration are preserved
  as data but not editable in the CMS UI.
- **Category images** are not yet uploadable through the CMS (the data model has
  `image_path`; the public API keeps returning it). Category image upload is a
  natural extension.
- **Field deletion** is exposed but the UI relies on the certainty that old
  request values remain readable by key (documented above); the detail view is the
  source of truth for archived submissions.
- The admin editor manages labels, types, required flags and options, but not the
  legacy conditional-visible logic.