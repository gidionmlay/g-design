<?php
/**
 * G DESIGN — API bootstrap: env loading, autoloading, base constants.
 */

declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
define('BACKEND_DIR', __DIR__);
define('STORAGE_DIR', BASE_DIR . '/storage');

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

/**
 * Minimal .env loader. KEY=VALUE lines, '#' comments, optional quotes.
 * Real environment variables always take precedence over .env values.
 */
function gd_load_env(string $file): void
{
    if (!is_readable($file)) {
        return;
    }
    // Values already present in the real environment take precedence;
    // among duplicate .env lines, the last one wins.
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[0] === substr($value, -1)) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

gd_load_env(BASE_DIR . '/.env');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
App\Config\Config::boot();

/**
 * Case-tolerant autoloader for App\* classes living in lowercase dirs:
 *   App\Core\Database      -> backend/core/Database.php
 *   App\Models\ServiceItem -> backend/models/ServiceItem.php
 *   App\Controllers\...    -> backend/controllers/...
 */
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $name = array_pop($parts);
    $dir = BACKEND_DIR . '/' . strtolower(implode('/', $parts));
    $file = $dir . '/' . $name . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
