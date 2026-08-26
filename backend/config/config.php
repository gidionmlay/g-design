<?php
/**
 * Application configuration accessor (dot notation over .env + defaults).
 */

declare(strict_types=1);

namespace App\Config;

final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    /** @var array<string,mixed> */
    private static array $defaults = [
        'app_env'          => 'local',
        'app_url'          => 'http://127.0.0.1:8000',
        'api_url'          => '',
        'db_host'          => '127.0.0.1',
        'db_port'          => '3306',
        'db_name'          => 'gdesign',
        'db_user'          => 'gdesign',
        'db_password'      => '',
        'db_charset'       => 'utf8mb4',
        'allowed_origins'  => '', // comma-separated absolute origins; empty = same-origin only
        'log_errors'       => '1',
    ];

    public static function boot(): void
    {
        foreach (self::$defaults as $key => $value) {
            $envKey = strtoupper($key);
            self::$items[$key] = ($_ENV[$envKey] ?? getenv($envKey) ?: $value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$items[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return strtolower((string) self::get('app_env')) === 'production';
    }

    /** @return list<string> */
    public static function allowedOrigins(): array
    {
        $raw = trim((string) self::get('allowed_origins'));
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
