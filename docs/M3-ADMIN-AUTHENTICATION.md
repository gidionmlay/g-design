# M3 — Admin Authentication & Admin Foundation

## Objective

Establish a secure authentication and authorization foundation for the future Admin Dashboard.

```
ADMIN → Admin Login → Authentication API → Session established → Protected Admin APIs → Admin Dashboard
```

Public APIs remain publicly accessible. Admin APIs require authentication.

## Architecture

```
Browser (Login form) → POST /api/v1/admin/auth/login → AuthController::login
  → Auth::attempt()          (find admin by email/username, verify password hash)
  → Auth::login()            (regenerate session id, store admin_id, update last_login_at)
  → sanitized admin JSON

Browser (subsequent requests) → cookie gds_admin_session
  → GET /api/v1/admin/auth/me → AuthMiddleware::handle
      → Auth::admin() (authenticated? YES → controller | NO → 401)
```

Authentication logic is isolated in `App\Core\Auth`, separate from controllers. Routes are protected through `App\Middleware\AuthMiddleware` instead of duplicating checks inside each controller.

## Admin Table

Migration: `database/migrations/2026-08-27-m3-admins-table.sql`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `username` | VARCHAR(100) UNIQUE | Login identifier |
| `email` | VARCHAR(190) UNIQUE | Login identifier / contact |
| `password_hash` | VARCHAR(255) | ONLY `password_hash()` output (bcrypt `$2y$`) |
| `full_name` | VARCHAR(190) NULL | Display name |
| `role` | VARCHAR(50) | Defaults to `admin` |
| `is_active` | TINYINT(1) | Controls access; defaults to 1 |
| `last_login_at` | TIMESTAMP NULL | Updated on successful login |
| `created_at` | TIMESTAMP | Default CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

No plaintext, encrypted, or reversible password is ever stored.

## Password Hashing

- **Creation:** `password_hash($password, PASSWORD_DEFAULT)` → bcrypt (cost 12)
- **Verification:** `password_verify($password, $hash)`
- No MD5, SHA1, custom hashing, or encryption.

## Session Strategy

Uses PHP native sessions with the cookie `gds_admin_session`.

Secure configuration applied in `Auth::startSession()`:

- `HttpOnly` cookies (not readable by JavaScript)
- `SameSite=Lax` (mitigates CSRF for same-site requests)
- `Secure` cookie flag when the request is over HTTPS
- **`session_regenerate_id(true)`** after successful login (prevents session fixation)
- Session stores only the `admin_id` — no sensitive data

Sessions are started lazily. `Auth::logout()` loads any existing session, empties it, expires the cookie, and destroys it — safe to call with no active session.

## API Endpoints

### POST /api/v1/admin/auth/login

Request (`application/json`):
```json
{ "email": "admin@example.com", "password": "password" }
```
The identifier accepts either `email` or `username`.

Success (200):
```json
{
  "ok": true,
  "data": {
    "admin": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "full_name": "G DESIGN Administrator",
      "role": "admin",
      "is_active": 1,
      "last_login_at": "2026-08-27 13:00:11"
    }
  }
}
```

`password`, `password_hash`, and the session ID are never returned.

### POST /api/v1/admin/auth/logout

Destroys the authenticated session and clears the cookie.

Success (200):
```json
{ "ok": true, "data": { "message": "Logged out successfully." } }
```

Calling logout without an active session returns the same success (no server error).

### GET /api/v1/admin/auth/me

Returns the current authenticated admin (200) or 401 `UNAUTHORIZED` when not authenticated.

## Middleware

`backend/middleware/AuthMiddleware.php` provides reusable protection:

- `AuthMiddleware::handle()` — require an authenticated admin; emits 401 otherwise.
- `AuthMiddleware::requireRole($roles)` — authenticate, then require a role; emits 403 otherwise.

## Authorization

Authentication ("who are you?") is distinguished from authorization ("may you perform this?").

For M3 only the `admin` role exists; `requireRole()` establishes the foundation so future roles (e.g. `superadmin`, `manager`) can be enforced without duplication. No complex permission system is introduced yet.

## Validation

Login validates: identifier supplied, password supplied, account exists, account active, password correct. Any failure returns a single generic message — `"Invalid credentials."` — to prevent account enumeration.

## Route Organization

Auth routes are namespaced under `/api/v1/admin/...`:

```
POST /api/v1/admin/auth/login
POST /api/v1/admin/auth/logout
GET  /api/v1/admin/auth/me
```

Future routes will follow `/api/v1/admin/requests|services|categories|analytics`.

## API Error Format

| Code | HTTP | When |
|---|---|---|
| `UNAUTHORIZED` | 401 | Not authenticated, or invalid credentials |
| `FORBIDDEN` | 403 | Authenticated but role not permitted |
| `VALIDATION_ERROR` | 400 | Missing/invalid input fields |
| `EMPTY_BODY` / `INVALID_JSON` | 400 | Malformed JSON body |

Example (401):
```json
{ "ok": false, "error": { "code": "UNAUTHORIZED", "message": "Authentication required." } }
```

## Security Measures

- Plaintext passwords never stored or transmitted as such — `password_hash()` only
- Session fixation prevented via `session_regenerate_id(true)`
- Session hijacking mitigated: `HttpOnly`, `SameSite=Lax`, `Secure` (on HTTPS)
- Account enumeration prevented by generic invalid-credential message
- Inactive accounts cannot log in
- Unauthorized admin API access returns 401
- `password_hash` / session IDs / DB credentials / filesystem paths / stack traces / SQL errors never exposed

## Frontend

A temporary admin login page was added for testing (the Dashboard is out of scope for M3):

- `public/admin/index.html` — login form (submit credentials, loading state, invalid-credential and network-error handling, redirect to the protected test page on success)
- `public/admin/home.html` — temporary protected test page that calls `/api/v1/admin/auth/me` and shows the authenticated admin; redirects to login on 401; provides logout

The frontend and API are served same-origin, so the session cookie is sent automatically (no wildcard CORS, credentials handled natively).

## Files Created

- `database/migrations/2026-08-27-m3-admins-table.sql`
- `database/seed_admin.php`
- `backend/models/AdminModel.php`
- `backend/core/Auth.php`
- `backend/middleware/AuthMiddleware.php`
- `backend/controllers/AuthController.php`
- `public/admin/index.html`
- `public/admin/home.html`
- `docs/M3-ADMIN-AUTHENTICATION.md`
- `docs/M3-TEST-REPORT.md`

## Files Modified

- `backend/routes/api.php` — registered admin auth routes

## Known Limitations

- Brute-force / rate limiting is not yet implemented (inspect: no login-attempt tracking, IP throttling, account throttling, or CAPTCHA exist). A future production-hardening phase should add these.
- Session strategy relies on PHP native files sessions; a more scalable store (Redis/DB) can be introduced later.
- `Secure` cookie flag is only active over HTTPS; local HTTP dev uses an unsecure cookie (expected in development).
- Password hash algorithm is tied to PHP's `PASSWORD_DEFAULT`; no rehash-on-login upgrade step is implemented yet.

## Recommended Next Phase

**M4 — Admin Dashboard + Request Management** (request list, details, attachment viewing/downloading, filtering, status, client/service info, dynamic requirements display).
