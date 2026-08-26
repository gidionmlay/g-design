<?php
/**
 * Lightweight pattern router: /api/v1/services/{slug}
 * Supports exact segments plus single {param} placeholders.
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string,array<int,array{pattern:string,regex:string,handler:callable}>> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $pattern) ?? $pattern;
        $this->routes[strtoupper($method)][] = [
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = '/' . trim($request->path, '/');

        foreach ($this->routes as $method => $definitions) {
            foreach ($definitions as $def) {
                if (!preg_match($def['regex'], $path, $m)) {
                    continue;
                }
                if ($request->method === 'OPTIONS') {
                    Response::handlePreflight();
                }
                if ($method !== $request->method) {
                    continue;
                }
                array_shift($m);
                ($def['handler'])(...array_map('urldecode', $m));
                return;
            }
        }

        // Path matched under a different method?
        foreach (array_keys($this->routes) as $method) {
            if ($method === $request->method) {
                continue;
            }
            foreach ($this->routes[$method] as $def) {
                if (preg_match($def['regex'], $path)) {
                    Response::methodNotAllowed(array_keys($this->routes));
                }
            }
        }

        Response::notFound('Unknown API route.');
    }
}
