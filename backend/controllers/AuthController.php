<?php
/**
 * Admin authentication endpoints.
 *
 *   POST /api/v1/admin/auth/login    -> admin log in
 *   POST /api/v1/admin/auth/logout   -> destroy the session
 *   GET  /api/v1/admin/auth/me       -> current authenticated admin (protected)
 *   POST /api/v1/admin/auth/settings -> update username and/or password (protected)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Models\AdminModel;

final class AuthController
{
    public function login(): void
    {
        $body = self::readJsonBody();

        if (!isset($body['password']) || !is_string($body['password']) || $body['password'] === '') {
            Response::validationError(['password' => 'Password is required.']);
        }

        $identifier = isset($body['email']) && is_string($body['email']) && $body['email'] !== ''
            ? $body['email']
            : (isset($body['username']) && is_string($body['username']) ? $body['username'] : null);

        if ($identifier === null) {
            Response::validationError(['email' => 'Email is required.']);
        }

        $admin = Auth::attempt([
            'identifier' => $identifier,
            'password'   => $body['password'],
        ]);

        // Generic failure message — never reveal whether the account exists.
        if ($admin === null) {
            Response::error('UNAUTHORIZED', 'Invalid credentials.', 401);
        }

        Auth::login($admin);

        // Re-fetch so last_login_at reflects the update just performed.
        $fresh = AdminModel::withoutHash(AdminModel::findById((int) $admin['id']));

        Response::ok(['admin' => $fresh]);
    }

    public function logout(): void
    {
        Auth::logout();
        Response::ok(['message' => 'Logged out successfully.']);
    }

    public function me(): void
    {
        $admin = AuthMiddleware::handle();
        Response::ok(['admin' => AdminModel::withoutHash($admin)]);
    }

    /**
     * Update the authenticated admin's profile: username and/or password.
     *
     * The current password is always required to confirm identity. Password
     * fields are optional — an empty new_password leaves it unchanged. The
     * session is intentionally left intact so the admin stays signed in.
     */
    public function updateSettings(): void
    {
        $admin = AuthMiddleware::handle();
        $body  = self::readJsonBody();

        $username    = $body['username'] ?? null;
        $current     = $body['current_password'] ?? null;
        $new         = $body['new_password'] ?? null;
        $confirmation = $body['new_password_confirmation'] ?? null;

        $fields = [];

        // Current password is always required to confirm identity.
        if (!is_string($current) || $current === '') {
            $fields['current_password'] = 'Current password is required.';
        }

        // Username: required, 3–30 of a-z0-9_- (normalized to lowercase).
        if (!is_string($username) || trim($username) === '') {
            $fields['username'] = 'Username is required.';
        } else {
            $username = strtolower(trim($username));
            if (!preg_match('/^[a-z0-9_-]{3,30}$/', $username)) {
                $fields['username'] = 'Username must be 3–30 characters using letters, digits, "_" or "-".';
            } elseif (AdminModel::usernameExists($username, (int) $admin['id'])) {
                $fields['username'] = 'That username is already taken.';
            }
        }

        // Optional new password (left blank = unchanged).
        if (is_string($new) && $new !== '') {
            if (strlen($new) < 8) {
                $fields['new_password'] = 'New password must be at least 8 characters long.';
            } elseif (is_string($current) && $current !== '' && $new === $current) {
                $fields['new_password'] = 'New password must be different from your current password.';
            }
            if (!is_string($confirmation) || $confirmation === '') {
                $fields['new_password_confirmation'] = 'Please confirm your new password.';
            } elseif ($new !== $confirmation) {
                $fields['new_password_confirmation'] = 'Password confirmation does not match.';
            }
        } elseif (is_string($confirmation) && $confirmation !== '') {
            $fields['new_password'] = 'New password is required.';
        }

        if ($fields !== []) {
            Response::validationError($fields);
        }

        // The session admin record is sanitized (no hash), fetch it for verification.
        $hash = AdminModel::findPasswordHash((int) $admin['id']);
        if ($hash === null) {
            Response::error('NOT_FOUND', 'Admin account not found.', 404);
        }

        if (!password_verify((string) $current, $hash)) {
            Response::error('CURRENT_PASSWORD_INVALID', 'Current password is incorrect.', 400);
        }

        // Commit provided changes.
        $changed = [];
        if (strcasecmp($username, (string) $admin['username']) !== 0) {
            AdminModel::updateUsername((int) $admin['id'], $username);
            $changed[] = 'username';
        }
        if (is_string($new) && $new !== '') {
            AdminModel::updatePassword((int) $admin['id'], password_hash($new, PASSWORD_DEFAULT));
            $changed[] = 'password';
        }

        $fresh = AdminModel::withoutHash(AdminModel::findById((int) $admin['id']));

        Response::ok([
            'admin'   => $fresh,
            'message' => $changed === [] ? 'Profile is already up to date.' : 'Profile updated successfully.',
        ]);
    }

    /** @return array<string,mixed> */
    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            Response::error('EMPTY_BODY', 'Request body is empty.', 400);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::error('INVALID_JSON', 'Request body must be valid JSON.', 400);
        }
        return $data;
    }
}
