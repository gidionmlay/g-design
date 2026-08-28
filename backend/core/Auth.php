<?php
/**
 * G DESIGN — Authentication service for administrators.
 *
 * Responsibilities:
 *   - login / logout
 *   - current session / authenticated admin lookup
 *   - session validation
 *   - secure session configuration
 *
 * Authentication logic is isolated here, separate from controllers.
 */

declare(strict_types=1);

namespace App\Core;

use App\Models\AdminModel;

final class Auth
{
    public const SESSION_KEY_ADMIN_ID = 'admin_id';

    /**
     * Configure secure PHP session behaviour and start the session.
     *
     * Called before the router handles admin/auth requests. No-op if a
     * session is already active.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = self::isHttps();

        session_name('gds_admin_session');

        session_set_cookie_params([
            'lifetime' => 0,                 // session cookie (browser close)
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,           // only over HTTPS
            'httponly' => true,              // not readable by JS
            'samesite' => 'Lax',             // mitigates CSRF for same-site forms
        ]);

        session_start();
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    /**
     * Attempt to authenticate with credentials.
     *
     * Returns a sanitized admin on success, or null on any failure.
     * A single generic result avoids user enumeration.
     *
     * @param array{identifier:string,password:string} $credentials
     * @return array<string,mixed>|null
     */
    public static function attempt(array $credentials): ?array
    {
        $identifier = trim($credentials['identifier'] ?? '');
        $password   = (string) ($credentials['password'] ?? '');

        if ($identifier === '' || $password === '') {
            return null;
        }

        $admin = AdminModel::findForLogin($identifier);
        if ($admin === null) {
            return null;
        }

        // Account must exist and be active.
        if ((int) $admin['is_active'] !== 1) {
            return null;
        }

        // Verify the password hash.
        if (!password_verify($password, $admin['password_hash'])) {
            return null;
        }

        return $admin;
    }

    /**
     * Establish an authenticated session for the given admin.
     * Regenerates the session ID to prevent session fixation.
     */
    public static function login(array $admin): void
    {
        self::startSession();

        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY_ADMIN_ID] = (int) $admin['id'];

        AdminModel::updateLastLogin((int) $admin['id']);
    }

    /**
     * Return the currently authenticated admin (sanitized) or null.
     *
     * @return array<string,mixed>|null
     */
    public static function admin(): ?array
    {
        self::startSession();

        if (!isset($_SESSION[self::SESSION_KEY_ADMIN_ID])) {
            return null;
        }

        $id = (int) $_SESSION[self::SESSION_KEY_ADMIN_ID];
        $admin = AdminModel::findById($id);

        // Session references an admin that no longer exists or was disabled.
        if ($admin === null || (int) $admin['is_active'] !== 1) {
            self::logout();
            return null;
        }

        return AdminModel::withoutHash($admin);
    }

    /** Whether the current request is from an authenticated admin. */
    public static function check(): bool
    {
        return self::admin() !== null;
    }

    /**
     * Destroy the authenticated session and clear the session cookie.
     * Safe to call with no active session.
     */
    public static function logout(): void
    {
        // Load any existing session so it can be destroyed and cookie cleared.
        self::startSession();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
