# M4 — Test Report

## Test Environment

- **Date:** 2026-08-27
- **Stack:** PHP 8.x built-in server (`php -S 127.0.0.1:8000 -t public public/router.php`), MariaDB 11.8.8 (`gdesign` database), vanilla JS SPA.
- **API testing:** `curl` with a cookie jar (simulated browser session).
- **Frontend testing:** headless Google Chrome driven over the DevTools Protocol (real rendering, real XHR, real session cookies).
- **Test data:** 9 requests (8 `pending`, 1 `reviewing`), 4 attachments across 3 requests, admin `admin@example.com`.

## Phase 34 — API Tests

| Test | Expected | Actual | Result |
|------|----------|--------|--------|
| T1 GET overview without authentication | 401 | 401 | PASS |
| T2 GET request list without authentication | 401 | 401 | PASS |
| T3 GET request details without authentication | 401 | 401 | PASS |
| A1 Admin login | 200 | 200 | PASS |
| T4 Authenticated overview | 200 | 200 | PASS |
| T4b Statistics present (6 counters) | 200 | 200 | PASS |
| T4c Recent requests present | 200 | 200 | PASS |
| T5 Authenticated request list | 200 | 200 | PASS |
| T6 Pagination (`page=2&limit=3`) | 200 | page=2, limit=3, items=3, total=9 | PASS |
| T7 Search by reference | matching request | matched | PASS |
| T8 Search by client email | matching request | matched (Jane Wanjiku) | PASS |
| T9 Filter by status (`reviewing`) | only matching | `{reviewing}` | PASS |
| T9b Filter by service (`flyer-brochures`) | only matching | matched | PASS |
| T10 Valid request details | 200 | 200 | PASS |
| T10b Detail fields (client/service/requirements/attachments) | 200 | 200 | PASS |
| T11 Nonexistent request (`999999`) | 404 | 404 | PASS |
| T12 Dynamic requirements (labelled + values) | 200 | labels + submitted values | PASS |
| T13 Attachment record list | 200 | 2 records (blue.jpg, red.png) | PASS |
| T14 Secure attachment download | 200 | 200, `image/png`, `Content-Disposition: attachment`, nosniff, length 282 | PASS |
| T14b Inline preview headers | 200 | 200, `image/jpeg`, inline, nosniff | PASS |
| T14c PDF download | 200 | 200, `application/pdf`, attachment | PASS |
| T15 Attachment under another request | 404 | 404 (no leak) | PASS |
| T16 Valid status update (`pending→reviewing`) | 200 | 200 | PASS |
| T17 Invalid status value (`bogus`) | 400 | 400 | PASS |
| T17b Blocked transition (`reviewing→completed`) | 400 | 400 | PASS |
| T18 Status update without auth | 401 | 401 | PASS |
| T19 Malicious HTML in a requirement | accepted + stored | stored raw | PASS |
| T19b Served as text (not executable HTML) | 200 | `<script>` returned as plain text; browser rendered as text | PASS |
| T20 Malformed request ID (`abc`) | 400 / no SQL error | 400 `INVALID_ID` | PASS |
| T20b Oversized ID | 400 / no SQL error | 400 `INVALID_ID` | PASS |

**30/30 PASSED.**

## Phase 35 — Frontend Tests (headless Chrome)

| # | Scenario | Result |
|---|----------|--------|
| 1 | Open Admin Login | PASS |
| 2 | Login redirects to dashboard | PASS |
| 3 | Dashboard loads (shell + routing) | PASS |
| 4 | Statistics load (9/8/1/0/0/0) | PASS |
| 5 | Recent requests load (6 rows) | PASS |
| 6 | Open Requests | PASS |
| 7 | Request list shows 9 rows | PASS |
| 8 | Search (e.g. "Bobby") reduces to 1 row | PASS |
| 9 | Filter by state/service options present | PASS |
| 10 | Pagination pager renders (page info, Prev/Next) | PASS |
| 11 | Open request (route `#/requests/3`) | PASS |
| 12 | Client information displayed | PASS |
| 13 | Service information displayed | PASS |
| 14 | Dynamic requirements rendered with labels (Quantity, Print size, Service type, Sides, Estimated budget) | PASS |
| 15 | Attachments displayed with meta (2) | PASS |
| 16 | Image preview (thumbnails load 90×90 / 120×80, lightbox trigger) | PASS |
| 17 | Download links point to `?download=1` (secure headers verified via API) | PASS |
| 18 | Change status via UI | PASS |
| 18b | Blocked transition shows server error in the UI | PASS |
| 19 | Refresh (view reloads after update) | PASS |
| 20 | New status persists (badge + select reflect `reviewing`) | PASS |
| 21 | Logout from sidebar | PASS |
| 22 | Revisit dashboard after logout | redirect to login | PASS |
| 23 | Session-expiry handling (`?expired=1`, redirect to login) | PASS |
| 24 | XSS probe rendered as inert text (0 scripts injected in `#view`) | PASS |
| 25 | Authenticated visitor to login page bounces to dashboard | PASS |

## Phase 36 — Regression Tests

| Test | Expected | Actual | Result |
|------|----------|--------|--------|
| GET /api/v1/services | 200 | 200 | PASS |
| POST /api/v1/requests (valid JSON) | 201 | 201 | PASS |
| POST /api/v1/requests (invalid) | 400 | 400 | PASS |
| Dynamic service requirements | validation works | validated | PASS |
| File upload (multipart `files[]`: PNG + PDF) | 201 | 201 | PASS |
| Attachment persistence | rows written | PNG 282 B + PDF 303 B stored with matching mime/size | PASS |
| Admin login | 200 | 200 | PASS |
| Admin logout | 200 | 200 | PASS |
| Admin /me | 200 → 401 after logout | 200, then 401 | PASS |
| Protected admin routes | 401 without auth | 401 | PASS |
| Public homepage / | 200 | 200 | PASS |
| GET /api/v1/requests (no public route) | 405 | 405 | PASS |

## Phase 37 — Database Checks

| Check | Actual | Result |
|-------|--------|--------|
| Request count | 9 | PASS |
| Request statuses | 8 pending, 1 reviewing | PASS |
| Service relationships | 7 distinct services referenced | PASS |
| Attachment relationships | 4 attachments on 3 requests | PASS |
| No orphan attachment records | 0 | PASS |
| No duplicate request data | 0 | PASS |
| Status update persists | request 1 `reviewing` survives reloads | PASS |
| `created_at` unchanged | original timestamps intact | PASS |
| `updated_at` on status change | request 1 `updated_at` moved with the status change | PASS |

## Notes

- Tests were run against a live server and a real MySQL database; no test runner, mocks, or SQLite.
- The one pre-existing mutation was carried into this report deliberately: request 1 was moved `pending → reviewing` during M4 verification as workflow test data.
- Temporary rows created by T19 (XSS probe) and Phase 36 regression probes were removed afterwards; the database returns to its 9-request baseline.
- All touched PHP files pass `php -l`; all admin JS modules pass `node --check`.