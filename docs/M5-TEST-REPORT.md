# M5 — Test Report

## Test Environment

- **Date:** 2026-08-29
- **Stack:** PHP 8.4 built-in server (`php -S 127.0.0.1:8000 -t public public/router.php`), MariaDB 11.8.8 (database `gdesign`, user `gdesign`), custom PHP + PDO + MySQL backend, vanilla JS admin SPA (no frameworks).
- **API testing:** `curl` with a cookie jar (browser-style `gds_admin_session` session).
- **Static/lint checks:** `php -l` on all new/edited PHP files; `node --check` on every admin JS module.
- **Test data:** the seed catalog (6 categories, 29 services) restored to baseline after each test pass; 0 managed images after cleanup; admin `admin@example.com` / dev password.

> **Frontend note:** Interactive browser testing (real clicks) was attempted via headless
> Chrome over the DevTools Protocol, but this environment's persistent shell / desktop
> session makes headless Chrome unreliable (background processes hold the session output
> pipe and spawns fight the user's existing Chrome). The admin UI was instead verified by:
> (1) `node --check` syntax validation of all JS, (2) static-asset HTTP loads, (3) direct
> API-shape checks for **every endpoint the frontend consumes**, (4) full review of the
> router/nav/modal wiring in `admin.js`/`dashboard.html`. Manual click-through is the
> recommended final acceptance step.

## Phase 38 — Catalog API Tests

All admin requests sent with the session cookie after `POST /api/v1/admin/auth/login`.

| Test | Expected | Actual | Result |
|------|----------|--------|--------|
| T1 GET categories without auth | 401 | 401 | PASS |
| T2 GET services without auth | 401 | 401 | PASS |
| A1 Admin login | 200 | 200 | PASS |
| A2 `/admin/auth/me` with session | 200 + admin | 200, admin row | PASS |
| C1 GET categories (authenticated) | 200 | 200, `service_count` present | PASS |
| C2 Create category | 201 | 201 | PASS |
| C3 Create category duplicate slug | 400 | 400 | PASS |
| C4 Create category missing name | 400 | 400 | PASS |
| S1 Create service (fixed) | 201 | 201, pricing stored | PASS |
| S2 Create service (range) | 201 | 201, min/max stored | PASS |
| S3 Create service invalid pricing type | 400 | 400 | PASS |
| S4 Create service `fixed` without price | 400 | 400 | PASS |
| S5 Create service invalid category | 400 | 400 | PASS |
| S6 Create service with `quote` (no price) | 201 | 201 | PASS |
| S7 GET services list | 200 | 200, items + `pagination` | PASS |
| S8 Search services | matching rows | matching rows | PASS |
| S9 Pagination metadata | correct | `{page,limit,total,pages}`, pages=2 | PASS |
| S10 GET service detail | 200 | 200, service + pricing | PASS |
| S11 Update service | 200 | 200 | PASS |
| S12 Deactivate service | 200 | 200 | PASS |
| S13 Reactivate service | 200 | 200 | PASS |
| P1 Create dynamic field | 201 | 201 | PASS |
| P2 Duplicate field key | 400 | 400 | PASS |
| P3 Invalid field key format | 400 | 400 | PASS |
| P4 Select field with options | 201 | 201, options stored | PASS |
| P5 Replace options | 200 | 200, options replaced | PASS |
| P6 Delete field | 200 | 200 | PASS |
| I1 Upload valid PNG | 201 | 201 | PASS |
| I2 Upload `evil.php` | 400 | 400 | PASS |
| I3 Upload `evil.svg` | 400 | 400 | PASS |
| I4 Upload fake `.webp` | 400 | 400 | PASS |
| I5 Upload without auth | 401 | 401 | PASS |
| I6 Replace image (old file removed) | 1 file remains | 1 file in `storage/service-images/` | PASS |
| I7 Replaced image id | 404 | 404 | PASS |
| I8 Current image id | 200 (image stream) | 200, nosniff headers | PASS |

## Phase 39 — Public API & Quote Regression

| Test | Expected | Actual | Result |
|------|----------|--------|--------|
| Public `GET /api/v1/services` | 200 | 200 | PASS |
| Public response has category fields `slug/name/tag/image/description/items` | present | present | PASS |
| Public item has `slug/name/short_description/description/image/pricing/fields` | present | present | PASS |
| Inactive service hidden from public API | not listed | not listed | PASS |
| Reactivated service re-appears | listed | listed | PASS |
| Historical request on deactivated service readable | full details | request 18 (branding, 3 requirements) readable, then restored | PASS |
| Public quote (M1): missing required field | 400 | 400 | PASS |
| Public quote (M1): unknown field | 400 | 400 | PASS |
| Public quote (M1): invalid select option | 400 | 400 | PASS |
| Public quote (M1): valid submission | 201 | 201 | PASS |
| M2 file upload regression | 201 | 201 | PASS |
| quote.js data-mapping compatibility | unchanged | all fields it consumes (`description`, category `image_path`, `tag`) intact | PASS |

## Phase 40 — Security & Data Checks

| Test | Expected | Actual | Result |
|------|----------|--------|--------|
| Unauthenticated image upload | 401 | 401 | PASS |
| `service_images` rows never expose `stored_filename`/paths | API has id/mime/url only | confirmed | PASS |
| Pricing CHECK rejects invalid combination | DB error/400 | failed on `fixed` w/o price | PASS |
| `chk_items_pricing` constraint present | — | present, verified in DB | PASS |
| XSS probes (service name, description, category name, requirement label, option label) | stored raw, rendered as text | UI uses `textContent`/`esc()` | PASS |
| No SQL injection indicators (all queries parameterized) | — | PDO prepared statements | PASS |
| Migrations idempotent | safe re-run | `IF NOT EXISTS` / `DROP CONSTRAINT IF EXISTS` | PASS |

## Phase 41 — Regression (M0–M4)

| Area | Result |
|------|--------|
| Backend startup / DB connection | PASS |
| M1 dynamic request submission + server validation | PASS |
| M2 file uploads + attachment records | PASS |
| M3 admin login / logout / me / protected routes | PASS |
| M4 overview stats / request list / search / filters / details / requirements rendering / status updates | PASS (re-verified endpoints against M5 shapes) |
| Public homepage + assets | PASS (200) |
| `GET /api/v1/admin/requests` unaffected by service changes | PASS |

## Phase 42 — Frontend Static & Endpoint-shape Checks

| # | Check | Result |
|---|-------|--------|
| 1 | `catalog.js` `node --check` | PASS |
| 2 | `admin.js` `node --check` | PASS |
| 3 | All admin static assets serve (dashboard.html, css, js) | PASS |
| 4 | Router dispatches `categories`, `services`, `services/{id}`, `services/new` | PASS (code review) |
| 5 | Nav group + sublinks render on `body.catalog-open` toggle | PASS (code + CSS review) |
| 6 | Catalog modal present + close wiring | PASS (code review) |
| 7 | `GET /admin/service-categories` shape matches `catalog.js` list/modal | PASS |
| 8 | `GET /admin/services?page&limit&search...` shape matches list view | PASS |
| 9 | `GET /admin/services/{id}` shape matches editor | PASS |
| 10 | `GET /admin/services/{id}/fields` shape matches field builder | PASS |
| 11 | Pricing inputs render per mode (dynamic show/hide) | PASS (code review) |

## Database Verification

| Check | Actual | Result |
|-------|--------|--------|
| Categories | 6 | PASS |
| Services | 29 (baseline restored) | PASS |
| Managed images | 0 after cleanup | PASS |
| `storage/service-images/` | empty, `.gitkeep` kept | PASS |
| No orphan/leftover test rows | cleaned | PASS |
| Inactive services preserved (no deletes) | all 29 intact | PASS |
| Historical requests preserved | 18+ requests intact, readable | PASS |

## Notes & Known Limitations

- Interactive UI click-through (browser) is documented as the recommended final acceptance
  step; automated interactive browser testing was not reliable in this environment (see
  Frontend note above). All API behavior the UI depends on was exercised and passed.
- The admin field builder manages text/textarea/email/tel/number/date/select/radio/checkbox;
  legacy `sizegrid`/`upload` fields and conditional config (`show_when_json`,
  `one_size_when_json`) are preserved as data but not editable in the M5 UI.
- Category image upload is not included (the category model keeps `image_path`);
  flagged as a natural follow-up.
- All touched PHP files pass `php -l`; all admin JS passes `node --check`.