<?php
/**
 * Admin authentication & authorization middleware.
 *
 *   AuthMiddleware::handle()      -> require an authenticated admin (401 otherwise)
 *   AuthMiddleware::requireRole() -> additionally require a specific role (403 otherwise)
 *
 * Controllers should not re-implement authentication checks themselves.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

final class AuthMiddleware
{
    /**
     * Guard an admin route: must be authenticated.
     *
     * Returns the sanitized admin on success, or emits a 401 response.
     *
     * @return array<string,mixed>
     */
    public static function handle(): array
    {
        $admin = Auth::admin();
        if ($admin === null) {
            Response::error('UNAUTHORIZED', 'Authentication required.', 401);
        }
        return $admin;
    }

    /**
     * Guard an admin route by role after authentication.
     *
     * @param string|list<string> $roles
     * @return array<string,mixed>
     */
    public static function requireRole(string|array $roles): array
    {
        $admin = self::handle();

        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array((string) $admin['role'], $allowed, true)) {
            Response::error('FORBIDDEN', 'You do not have permission to perform this action.', 403);
        }

        return $admin;
    }
}
