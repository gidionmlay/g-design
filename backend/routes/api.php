<?php
/**
 * API route table.
 */

declare(strict_types=1);

use App\Controllers\RequestController;
use App\Controllers\ServiceController;
use App\Core\Router;

return static function (Router $router): void {
    $router->add('GET', '/api/v1/services', [new ServiceController(), 'index']);
    $router->add('GET', '/api/v1/services/{slug}', [new ServiceController(), 'show']);
    $router->add('POST', '/api/v1/requests', [new RequestController(), 'create']);
};
