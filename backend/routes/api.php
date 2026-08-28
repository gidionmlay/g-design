<?php
/**
 * API route table.
 */

declare(strict_types=1);

use App\Controllers\AdminCatalogController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminRequestController;
use App\Controllers\AuthController;
use App\Controllers\RequestController;
use App\Controllers\ServiceController;
use App\Controllers\ServiceImageController;
use App\Core\Router;

return static function (Router $router): void {
    $router->add('GET', '/api/v1/services', [new ServiceController(), 'index']);
    $router->add('GET', '/api/v1/services/{slug}', [new ServiceController(), 'show']);
    $router->add('POST', '/api/v1/requests', [new RequestController(), 'create']);
    $router->add('GET', '/api/v1/service-images/{id}', [new ServiceImageController(), 'show']);

    // Admin authentication.
    $router->add('POST', '/api/v1/admin/auth/login', [new AuthController(), 'login']);
    $router->add('POST', '/api/v1/admin/auth/logout', [new AuthController(), 'logout']);
    $router->add('GET', '/api/v1/admin/auth/me', [new AuthController(), 'me']);
    $router->add('POST', '/api/v1/admin/auth/settings', [new AuthController(), 'updateSettings']);

    // Admin dashboard + request management.
    $router->add('GET', '/api/v1/admin/dashboard/overview', [new AdminDashboardController(), 'overview']);
    $router->add('GET', '/api/v1/admin/requests', [new AdminRequestController(), 'index']);
    $router->add('GET', '/api/v1/admin/requests/{id}', [new AdminRequestController(), 'show']);
    $router->add('PATCH', '/api/v1/admin/requests/{id}/status', [new AdminRequestController(), 'updateStatus']);
    $router->add('GET', '/api/v1/admin/requests/{id}/attachments', [new AdminRequestController(), 'attachments']);
    $router->add('GET', '/api/v1/admin/requests/{id}/attachments/{attachmentId}', [new AdminRequestController(), 'streamAttachment']);

    // Admin Service & Catalog CMS (M5).
    $router->add('GET', '/api/v1/admin/service-categories', [new AdminCatalogController(), 'categories']);
    $router->add('POST', '/api/v1/admin/service-categories', [new AdminCatalogController(), 'createCategory']);
    $router->add('GET', '/api/v1/admin/service-categories/{id}', [new AdminCatalogController(), 'showCategory']);
    $router->add('PATCH', '/api/v1/admin/service-categories/{id}', [new AdminCatalogController(), 'updateCategory']);
    $router->add('PATCH', '/api/v1/admin/service-categories/{id}/status', [new AdminCatalogController(), 'categoryStatus']);
    $router->add('PATCH', '/api/v1/admin/service-categories/{id}/order', [new AdminCatalogController(), 'categoryOrder']);

    $router->add('GET', '/api/v1/admin/services', [new AdminCatalogController(), 'services']);
    $router->add('POST', '/api/v1/admin/services', [new AdminCatalogController(), 'createService']);
    $router->add('GET', '/api/v1/admin/services/{id}', [new AdminCatalogController(), 'showService']);
    $router->add('PATCH', '/api/v1/admin/services/{id}', [new AdminCatalogController(), 'updateService']);
    $router->add('PATCH', '/api/v1/admin/services/{id}/status', [new AdminCatalogController(), 'serviceStatus']);
    $router->add('PATCH', '/api/v1/admin/services/{id}/order', [new AdminCatalogController(), 'serviceOrder']);

    $router->add('GET', '/api/v1/admin/services/{id}/fields', [new AdminCatalogController(), 'fields']);
    $router->add('POST', '/api/v1/admin/services/{id}/fields', [new AdminCatalogController(), 'createField']);
    $router->add('PATCH', '/api/v1/admin/services/{id}/fields/{fieldId}', [new AdminCatalogController(), 'updateField']);
    $router->add('DELETE', '/api/v1/admin/services/{id}/fields/{fieldId}', [new AdminCatalogController(), 'deleteField']);
    $router->add('POST', '/api/v1/admin/services/{id}/fields/{fieldId}/options', [new AdminCatalogController(), 'options']);

    $router->add('POST', '/api/v1/admin/services/{id}/image', [new AdminCatalogController(), 'uploadImage']);
};
