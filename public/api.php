<?php
/**
 * G DESIGN — API front controller.
 *
 * Clean URLs require a rewrite (public/.htaccess on Apache, or
 * `php -S <host> -t public public/router.php` in development):
 *
 *   GET  /api/v1/services
 *   GET  /api/v1/services/{slug}
 *   POST /api/v1/requests
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/bootstrap.php';

use App\Config\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$request = Request::fromGlobals();
$router  = new Router();

(require BACKEND_DIR . '/routes/api.php')($router);

try {
    $router->dispatch($request);
} catch (Throwable $e) {
    if ((string) Config::get('log_errors', '1') === '1') {
        $logDir = STORAGE_DIR . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @error_log(sprintf(
            "[%s] %s %s :: %s in %s:%d\n",
            date('c'),
            $request->method,
            $request->path,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ), 3, $logDir . '/api-error.log');
    }
    Response::error('SERVER_ERROR', 'An internal error occurred.', 500);
}
