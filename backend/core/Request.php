<?php
/**
 * Immutable HTTP request wrapper.
 */

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    /** @var array<string,string> */
    public readonly array $query;

    /**
     * @param array<string,string> $server
     * @param array<string,string> $query
     */
    public function __construct(array $server, array $query)
    {
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $uri = $server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // When invoked directly as /api.php/api/v1/services, drop the script prefix.
        // Rewritten requests (/api/v1/...) and php -S router requests are already clean.
        $script = str_replace('\\', '/', $server['SCRIPT_NAME'] ?? '');
        if ($script !== '' && $script !== '/' && str_starts_with($path, $script . '/')) {
            $path = substr($path, strlen($script));
        }

        $this->path = '/' . trim($path, '/');
        if ($this->path === '/') {
            $this->path = '';
        }
        $this->query = array_map('strval', $query);
    }

    public static function fromGlobals(): self
    {
        return new self($_SERVER, $_GET);
    }

    public function query(string $key, ?string $default = null): ?string
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json');
    }
}
