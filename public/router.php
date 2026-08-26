<?php
/**
 * Development router for the PHP built-in server:
 *   php -S 127.0.0.1:8000 -t public public/router.php
 *
 * - existing files/directories are served statically
 * - /api/** is handled by api.php
 * - everything else falls through to the static handler (directory indexes)
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // serve as-is
}

if (str_starts_with($path, '/api/') || $path === '/api') {
    require __DIR__ . '/api.php';
    return true;
}

if (is_dir($file)) {
    return false; // built-in server serves directory index (index.html)
}

return false;
