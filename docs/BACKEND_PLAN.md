# G DESIGN — Frontend Inspection & Backend Architecture Plan

Produced by executing `guide.txt` (Phases 1–7). No backend code has been written yet, per the guide's instruction.

Stack decision honored: HTML5/CSS3-SCSS/Vanilla JS frontend (existing) + **custom PHP REST-style JSON APIs + PDO/MySQL**. No frameworks (no Laravel, no Node, no Django, no SQLite).

---

## PHASE 1 — Project Structure Overview

```
g-design/
├── index.html                    Homepage
├── about.html                    About + embedded contact form
├── contact.html                  Contact page + contact form
├── quote.html                    5-step quotation wizard (dynamic request system)
├── service.html                  Services listing
├── service-{branding,graphic-design,printing,content-creation,
│            creative-strategy,art-direction}.html   6 service detail pages
├── work.html / work-details.html Portfolio grid / case study (static)
├── blog-standard.html            Blog listing (static)
├── blog-*.html                   6 static blog posts
├── 404.html
└── assets/
    ├── js/quote.js               ★ Quotation wizard — entire service catalog hardcoded here
    ├── js/main.js                Template UI logic (menu, sliders, animations, search popup)
    ├── js/plugins/contact-form.js ★ AJAX submitter for #contact-form → mailer.php
    ├── css/, scss/, images/, fonts/
```

**Critical facts discovered:**

- **Zero PHP files exist.** No `.php`, no `.sql`, no config, no `.htaccess`, no documentation. The backend is 100% greenfield.
- **No admin dashboard exists yet.** The admin UI must still be built (it is listed as a platform goal in the guide).
- **No authentication of any kind exists** in the frontend.
- The **entire service catalog is hardcoded in `assets/js/quote.js`** (`QUOTE_CONFIG`, ~300 lines). This is the authoritative service data source — nothing was invented.
- jQuery is present and used by `contact-form.js`; the quote wizard is dependency-free vanilla JS.

---

## PHASE 2 — Page Inventory

| # | File | Purpose | User type | Data displayed | Data required from backend | Forms | Actions | JS modules | API expected | Static/Dynamic |
|---|------|---------|-----------|----------------|---------------------------|-------|---------|------------|--------------|----------------|
| 1 | `index.html` | Homepage: hero, services preview, portfolio highlights, testimonials, CTA | Visitor | Marketing content, service cards, portfolio items | Later: featured services, featured works, testimonials | Contact form (`mailer.php`) | Submit service inquiry | main.js, plugins, contact-form.js | POST mailer.php | Mostly static |
| 2 | `about.html` | Company story, team, awards, working process | Visitor | Marketing content | None | Contact form | Submit inquiry | main.js, contact-form.js | POST mailer.php | Static |
| 3 | `contact.html` | Contact page with full inquiry form | Visitor | Contact info | Service list (currently a hardcoded `<select>` with 7 options incl. "Other") | Contact form: name, email, service (select), message, file attachment | Submit inquiry + upload | contact-form.js | POST mailer.php | Form = dynamic |
| 4 | `quote.html` | **Dynamic request system** — wizard: Service → Requirements → Project Details → Review → Success | Visitor/client | Service catalog, per-item requirement fields, review summary | Full catalog + field definitions (from CMS later); accepts `?service=<id>&item=<id>` deep links | Wizard-built form; file uploads | Select service/item, fill dynamic fields, attach files, submit | quote.js | POST (MISSING BACKEND ENDPOINT) | Fully dynamic |
| 5 | `service.html` | Services overview | Visitor | 6 category cards | Service categories from DB (later) | None | Navigate to detail pages | main.js | None | Static |
| 6–11 | `service-*.html` (6 pages) | Category detail pages | Visitor | Description, offerings, process, FAQ, pricing hints | Category + items from DB (later) | None | CTA → `quote.html?service=<id>` | main.js | None | Static |
| 12 | `work.html` | Portfolio grid | Visitor | 10 project cards | Projects table (later phase) | None | Filter/navigate | main.js | None | Static |
| 13 | `work-details.html` | Single case study template | Visitor | Project details | Projects table (later) | None | Navigate | main.js | None | Static |
| 14 | `blog-standard.html` | Blog listing | Visitor | Post cards, sidebar search form (non-functional) | Posts table (later), search endpoint (later) | Search form (no action) | Search (not wired) | main.js | None | Static |
| 15–20 | `blog-*.html` (6 posts) | Articles | Visitor | Article content | Posts table (later) | Sidebar search + subscribe-style CTA form (no action) | Read | main.js | None | Static |
| 21 | `404.html` | Error page | Any | — | — | — | Navigate home | — | None | Static |

**Deep-link contract used across all service pages:** `quote.html?service=branding|graphic-design|printing|content-creation|creative-strategy|art-direction` (optionally `&item=<itemId>`).

---

## PHASE 3 — API Dependency Map

Search performed for: `fetch(`, `XMLHttpRequest`, `$.ajax`, `$.get`, `$.post`, `axios`, `/api/`, `http(s)://`, `localStorage`, `sessionStorage`, `FormData`, `Authorization`, `Bearer`, `Token`.

Results:

| Location | Finding | Verdict |
|---|---|---|
| 19 HTML pages | `<form id="contact-form" action="mailer.php" method="POST" enctype="multipart/form-data">` | **MISSING BACKEND ENDPOINT — `mailer.php` does not exist** |
| `assets/js/plugins/contact-form.js` | jQuery `$.ajax POST` to `$(form).attr('action')` (= `mailer.php`), raw `FormData`, expects **plain-text response body** on success; reads `data.responseText` on failure | Consumed by every page's contact form |
| `assets/js/quote.js` → `submitRequest()` | Builds JSON payload then only `console.log(...)`s it | **MISSING BACKEND ENDPOINT** — submission is frontend-only |
| `assets/js/plugins/smoothscroll.js` | `localStorage.SS_deltaBuffer` | Not auth-related (scroll wheel tuning) |
| `jarallax.js`, `jquery-ui.js`, `jquery.min.js` | Internal `XMLHttpRequest` usage (asset loading) | Irrelevant to business APIs |
| Everything else | No fetch/axios/token/sessionStorage usage | — |

### Complete API dependency table

| Frontend Feature | Method | Endpoint | Auth | Request | Response |
|---|---|---|---|---|---|
| Contact form (all 19 pages) | POST | `mailer.php` ⚠ MISSING | None | multipart/form-data: `name`, `email`, `service`, `message`, `file` (optional single attachment) | Plain text success message rendered into `#form-messages`; non-empty error body on fail (HTTP error status) |
| Quote wizard submission (`quote.js:submitRequest`) | POST | *(none wired)* ⚠ MISSING BACKEND ENDPOINT | None | JSON payload (see Phase 7) + up to 5 file attachments (pdf/png/jpg/jpeg/ai/psd/zip) | Success state screen shown unconditionally today; real implementation should return request reference/ID |
| Catalog loading (`quote.js`) | GET | *(none)* — catalog is hardcoded in JS | None | — | Future: serve catalog from CMS via `/api/services` so admin can manage it without redeploying JS |

---

## PHASE 4 — Authentication Inspection

Findings:

- **There is currently NO login, registration, logout, token, session, or role handling anywhere in the frontend.**
- No auth headers, no JWT/Bearer strings, no cookie logic, no protected pages, no redirect behavior, no expiration handling.
- All existing forms are anonymous/public.

**Recommendation (cleanest custom-PHP mechanism compatible with this frontend):**

- **Native PHP sessions with secure, HttpOnly, SameSite=Lax cookies** — zero changes needed to the existing static HTML public site; simplest to protect future `/admin` pages server-side; avoids client-side token plumbing that vanilla-JS static pages don't have infrastructure for.
- Password hashing: `password_hash()` / `password_verify()` (bcrypt default).
- Structure:
  - `POST /api/auth/login` → sets session, returns user JSON (id, name, role)
  - `POST /api/auth/logout`
  - `GET /api/auth/me`
  - Admin pages additionally guarded by a PHP gate include checking `$_SESSION['user']['role'] === 'admin'`.
- If stateless tokens are ever preferred (e.g., for a future SPA/mobile client), add bearer-token support behind the same endpoint shape — but do not introduce it now since the current frontend needs none of it.

---

## PHASE 5 — User Roles

Roles actually present in the codebase: **none.** Only an implicit anonymous *visitor*.

The guide's platform goals imply these roles going forward (to be implemented, not assumed):

```
VISITOR (exists today)
  ↓ Permissions: browse all public pages, submit contact form, submit quotation requests
  ↓ Pages: everything public
  ↓ API access: POST /api/contact, POST /api/requests (+ GET /api/services)

CLIENT (future)
  ↓ Permissions: own requests visibility, own profile
  ↓ Pages: login, my requests/dashboard
  ↓ API access: auth endpoints + own-scope request reads

ADMINISTRATOR (future)
  ↓ Permissions: full CRUD on services/items/fields, manage all requests, users, view messages
  ↓ Pages: /admin dashboard (to be built)
  ↓ API access: all /api/admin/* endpoints, session-guarded
```

Note: the guide mentions "Student" as a possible role — it does **not** appear anywhere in the code and is not implemented.

---

## PHASE 6 — Service CMS Inspection (exact fields found)

All catalog structure below is transcribed verbatim from `QUOTE_CONFIG` in `assets/js/quote.js`. Nothing invented.

### Hierarchy

```
Category (6)  →  Item (23 total)  →  Field definitions  →  Options
```

Categories: `branding`, `graphic-design`, `printing`, `content-creation`, `creative-strategy`, `art-direction` — each with `name`, `tag` (uppercase label), `image`, `desc`, `items[]`.

Items each have: `id`, `name`, `desc`, `fields[]`.

### Field schema observed (per-field properties actually used)

| Property | Used by | Purpose |
|---|---|---|
| `key` | all | Answer key in payload/state |
| `type` | all | One of: `radio`, `checkbox`, `text`, `email`, `tel`, `number`, `date`, `textarea`, `sizegrid`, `upload` |
| `label` | all | Display label (also becomes the key in submitted `requirements` object) |
| `required` | most | Validation flag |
| `options[]` | radio, checkbox | String option values |
| `placeholder` | text/email/tel/number/date/textarea | Hint text |
| `hint` | upload | Accepted-types hint string |
| `showWhen {key, equals\|notIn\|value}` | supported by engine (conditional visibility) | Currently no field uses it, but the renderer/validation engine fully supports it |
| `sizes[]` | sizegrid | Column labels (e.g. S/M/L/XL/XXL or A5…A1) |
| `oneSizeWhen {key,value,label}` | sizegrid | Replaces size grid with single input when a condition matches (used by T-Shirt & Kofia when garment = "Cap (Kofia)") |

### Common fields appended for every request (COMMON_FIELDS)

`name`* (text), `email`* (email), `phone` (tel, optional), `completion_date`* (date, min=today), `notes` (textarea), `files` (upload, max **5** files, accepted: pdf/png/jpg/jpeg/ai/psd/zip).

### Item-level budget pattern

Most printing/graphic items carry an `Estimated budget` radio with TZS ranges (ranges vary per item — e.g., apparel starts at "Below TZS 150,000/="). These are literal option strings, stored as chosen values.

### MySQL tables implied by the CMS requirements

```
service_categories(id, slug, name, tag, image_path, description, sort_order, is_active, created_at, updated_at)
service_items(id, category_id FK, slug, name, description, image_path?, sort_order, is_active, created_at, updated_at)
service_fields(id, item_id FK, field_key, label, type ENUM(radio,checkbox,text,email,tel,number,date,textarea,sizegrid,upload),
               required TINYINT, placeholder, hint, sort_order, show_when_json NULL, created_at)
service_field_options(id, field_id FK, option_value, sort_order)
field_size_labels(id, field_id FK, size_label, sort_order)          -- for sizegrid columns
-- oneSizeWhen can be stored inside show_when_json (same conditional mechanism family)
users(id, name, email UNIQUE, phone, password_hash, role ENUM('client','admin'), is_active, created_at)
requests(id, reference_no UNIQUE, user_id NULLABLE FK, category_id FK, item_id FK,
         contact_name, contact_email, contact_phone, completion_date, budget_value NULL, notes,
         status ENUM('new','in_review','quoted','approved','in_production','completed','cancelled'),
         submitted_at, created_at, updated_at)
request_answers(id, request_id FK, field_id FK, answer_text NULL, answer_json NULL)
request_attachments(id, request_id FK, original_name, stored_path, mime_type, size_bytes, uploaded_at)
contact_messages(id, name, email, service, message, attachment_path NULL, status ENUM('new','read','replied'), created_at)
settings(key PRIMARY, value)                                        -- mail creds, office info, etc.
```

---

## PHASE 7 — Dynamic Request System Workflow (as implemented in quote.js)

```
USER lands on quote.html (?service=&item= deep links honored at init)
 ↓ STEP 1: pick CATEGORY card → panel swaps to ITEMS of that category → pick ITEM
 ↓ STEP 2: REQUIREMENTS — fields rendered from QUOTE_CONFIG[item].fields
 │    • types: radio / checkbox / text / email / tel / number / date / textarea / sizegrid / upload
 │    • conditional visibility engine (showWhen) + sizegrid oneSizeWhen re-render on change
 ↓ STEP 3: PROJECT DETAILS — common fields (name*, email*, phone, completion_date*, notes, attachments)
 │    • attachments: max 5, ext whitelist .pdf .png .jpg .jpeg .ai .psd .zip, kept in memory (state.files[])
 ↓ STEP 4: REVIEW — renders every visible answer (sizegrid flattened to "S: 2, M: 3", checkbox joined)
 ↓ SUBMIT → buildRequestPayload():
 {
   service: "<category name>",
   item: "<item name>",
   contact: { name, email, phone },
   requirements: { "<field label>": <string|string[]|object> },   // keyed BY LABEL, not by field.key
   project: { completion_date, budget, notes },
   attachments: ["file1.png", ...],        // names only — File objects never leave the browser today
   submitted_at: ISO-8601
 }
 → currently console.log() only → SUCCESS SCREEN (step 5) always shown
```

Validation behavior to replicate server-side (defense in depth): required checks per type; email regex `^[^@\s]+@[^@\s]+\.[^@\s]+$`; date ≥ today; upload required-check; hidden fields excluded from validation and payload.

**Backend wiring decisions required (frontend-compatible):**
1. Keep the exact payload shape above; POST as `multipart/form-data` with a `payload` JSON part + `attachments[]` file parts (mirrors how contact-form already sends files), OR JSON body + separate upload step. Recommended: single multipart POST to `/api/requests`.
2. Server must validate against the CMS-stored field definitions (labels/types/options), persist answers normalized by `field_id`, store attachments under non-guessable names, generate human reference (e.g. `GD-2026-000123`).
3. Replace the hardcoded `QUOTE_CONFIG` with `GET /api/services?include=items.fields.options` once the CMS exists (keep quote.js's renderer untouched — it already consumes a config array).

---

## BACKEND ARCHITECTURE (proposed, custom PHP — no framework)

```
/api                          ← thin front controller (index.php router or per-endpoint files)
├── v1
│   ├── services.php          GET  catalog (public, cached)
│   ├── requests.php          POST create quote request (public, rate-limited)
│   ├── contact.php           POST contact message (alias/companion of mailer.php contract)
│   ├── auth/login.php        POST login  (session cookie)
│   ├── auth/logout.php       POST
│   ├── auth/me.php           GET
│   └── admin/                ALL session+role guarded
│       ├── requests.php      GET list / GET one / PATCH status
│       ├── messages.php      GET list / PATCH read/replied
│       ├── categories.php    CRUD
│       ├── items.php         CRUD
│       ├── fields.php        CRUD (incl. options, sizes, conditions)
│       ├── uploads.php       GET/DELETE attachment files
│       └── users.php         CRUD (admin-only)
/core
├── Database.php              PDO singleton (utf8mb4, exceptions, prepared statements only)
├── Router.php / Response.php JSON helpers (consistent envelope: {ok, data|error})
├── Auth.php                  session guard helpers
├── Validator.php             mirrors quote.js validation rules
├── Uploader.php              whitelist ext/mime, 5-file cap, randomized names, private storage dir
└── Mailer.php                wraps PHPMailer or native mail() for notifications
/admin                        ← future dashboard UI (vanilla JS consuming /api/v1/admin/*)
/uploads                      ← private attachment storage (deny direct access via .htaccess)
database/schema.sql           ← tables from Phase 6
```

Conventions: all responses `Content-Type: application/json`; errors `{ok:false, error:{code,message,fields?}}`; CSRF token for admin mutating calls; prepared statements everywhere; upload dir outside public-readable paths or `.htaccess deny`.

---

## IMPLEMENTATION PLAN (phased, after approval)

1. **M0 — Foundation:** MySQL schema (Phase 6 tables), `/core` classes, `/api` skeleton, seed script importing the exact `QUOTE_CONFIG` catalog into the DB (single source of truth migration).
2. **M1 — Public intake (highest value):** implement `POST /api/requests` accepting the wizard's payload + attachments; wire `quote.js:submitRequest()` to it (fetch, keep UI identical); implement `mailer.php` (or repoint all 19 forms' `action` to `/api/v1/contact.php` — recommend repointing) storing `contact_messages` + optional attachment + email notification.
3. **M2 — Auth:** users table seeding first admin, login/logout/me endpoints, session guard middleware include.
4. **M3 — Services CMS APIs:** admin CRUD for categories/items/fields/options/sizes/conditions; switch quote.js to load catalog from `GET /api/v1/services` (fallback to bundled config if fetch fails).
5. **M4 — Request management UI:** admin list/detail screens, status workflow, attachment download, filters/search, message inbox.
6. **M5 — Hardening:** rate limiting on public POSTs, CSRF for admin, audit columns, backups.
7. **Later phases (per guide):** client accounts & project management, messaging/notifications, analytics.

---

## Open questions before M0 coding begins

1. **Contact endpoint strategy:** keep legacy path `mailer.php` (zero HTML edits) vs. repoint forms to `/api/v1/contact.php` (cleaner)? Recommend repointing during M1.
2. Hosting constraints: PHP version target (recommend ≥ 8.1), mail transport available (SMTP creds vs `mail()`)?
3. Should clients eventually log in to see their request status (drives `users.role='client'` scope now vs later)?
