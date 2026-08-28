<?php
/**
 * Admin dashboard endpoints.
 *
 *   GET /api/v1/admin/dashboard/overview  -> statistics + recent requests
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Models\RequestModel;

final class AdminDashboardController
{
    public function overview(): void
    {
        AuthMiddleware::handle();

        Response::ok(RequestModel::overview(6));
    }
}