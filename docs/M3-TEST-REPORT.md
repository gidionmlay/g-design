# M3 — Test Report

## Test Environment

- MySQL / MariaDB on localhost (`gdesign` database)
- PHP 8.4.24 built-in server on port 8000 (`php -S 127.0.0.1:8000 -t public public/router.php`)
- Server-side requests via `curl` with a cookie jar (simulating a browser session)
- Frontend pages served same-origin from `public/`

---

## Authentication Tests

| # | Test | Expected | Actual | Result |
|---|------|----------|--------|--------|
| 1 | Create admin through seed | Admin created successfully | `Admin created: G DESIGN Administrator (admin@example.com)` | PASS |
| 2 | Run seed again | No duplicate admin | `Admin already exists (admin@example.com). No duplicate created.` | PASS |
| 3 | Correct credentials (`POST /admin/auth/login`) | 200, sanitized admin | 200, `ok:true`, admin object without hash | PASS |
| 4 | Wrong password | 401 | 401, `UNAUTHORIZED` / `Invalid credentials.` | PASS |
| 5 | Unknown email | 401, generic message | 401, `UNAUTHORIZED` / `Invalid credentials.` (identical to wrong password) | PASS |
| 6 | Missing credentials | 400 or validation | 400, `VALIDATION_ERROR` with `password` field; empty body → 400 `EMPTY_BODY` | PASS |
| 7 | Inactive admin | 401 | 401, generic `Invalid credentials.` | PASS |
| 8 | `GET /admin/auth/me` without login | 401 | 401, `UNAUTHORIZED` / `Authentication required.` | PASS |
| 9 | Login then `GET /admin/auth/me` | 200 | 200, current authenticated admin returned | PASS |
| 10 | Refresh browser (reuse cookie jar) `GET /me` | Session retained, 200 | 200, still authenticated | PASS |
| 11 | Logout then `GET /me` | Logout 200; then 401 | Logout 200; `/me` 401, cookie cleared | PASS |
| 12 | Public `GET /api/v1/services` | Still 200 | 200 | PASS |
| 13 | Protected/future endpoint without auth | 401 | `/admin/auth/me` (protected test endpoint) → 401 when unauthenticated | PASS |
| 14 | Password hash in DB | Secure hash, not plaintext | bcrypt `$2y$12$…` (60 chars), no plaintext | PASS |

## Login Validation Details

| Input | HTTP | Response |
|---|---|---|
| Correct email + password | 200 | `ok:true`, admin profile |
| Correct username + password | 200 | `ok:true`, admin profile |
| Wrong password | 401 | `Invalid credentials.` |
| Unknown email | 401 | `Invalid credentials.` (identical, no enumeration) |
| Inactive account | 401 | `Invalid credentials.` |
| Missing password | 400 | `VALIDATION_ERROR` (password required) |
| Missing identifier | 400 | `VALIDATION_ERROR` (email required) |
| Empty JSON body | 400 | `EMPTY_BODY` |

## Session / Cookie Behavior

| Check | Result |
|---|---|
| Session cookie `gds_admin_session` set on login | PASS |
| Cookie is `HttpOnly` and `SameSite=Lax` | PASS |
| Session ID regenerated after login (fixation) | PASS (via `session_regenerate_id(true)`) |
| Session survives a new request / "refresh" | PASS |
| Logout destroys session and clears cookie | PASS |
| `/me` after logout → 401 | PASS |

## Database Verification

| Check | Result |
|---|---|
| `admins` table exists | PASS |
| `email` unique index | PASS |
| `username` unique index | PASS |
| `password_hash` populated (bcrypt) | PASS |
| `role` populated (`admin`) | PASS |
| `is_active` populated (`1`) | PASS |
| `created_at` / `updated_at` populated | PASS |
| `last_login_at` changes after successful login | PASS |

## M1 / M2 Regression Tests

| Test | Expected | Actual | Result |
|---|---|---|---|
| `GET /api/v1/services` | 200 | 200, 6 categories | PASS |
| `POST /api/v1/requests` (valid, JSON, no files) | 201 | 201, reference + `status:pending` | PASS |
| `POST /api/v1/requests` (validation error) | 400 | 400, `VALIDATION_ERROR` with fields | PASS |
| `POST /api/v1/requests` (multipart + PNG upload) | 201 | 201, 1 attachment stored | PASS |
| Public request flow unaffected by auth | Works | Works | PASS |

---

## Summary

| | |
|---|---|
| **Total tests** | 14 auth tests + validation/session/db checks + 5 regression |
| **Passed** | All |
| **Failed** | 0 |
| **Status** | ALL PASS |

## Notes / Limitations

- All tests run against a live server and a real MySQL database.
- No brute-force rate limiting is implemented (documented as a known limitation for a future hardening phase).
- Test admin (`admin@example.com`) uses a development environment password. In production, seed via environment variables (`ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`, `ADMIN_USERNAME`) and use a strong secret.
