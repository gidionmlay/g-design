<?php
/**
 * JSON response emission + CORS handling.
 *
 * CORS policy: the platform is served same-origin by default (public/ + /api
 * behind one host), so no CORS headers are emitted unless ALLOWED_ORIGINS is
 * configured. Wildcard "*" is intentionally not supported.
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

final class Response
{
    /** @param array<string,mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        self::applyCors();
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @param array<string,mixed> $data */
    public static function ok(array $data): never
    {
        self::json(['ok' => true, 'data' => $data], 200);
    }

    public static function error(string $code, string $message, int $status = 400): never
    {
        self::json(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], $status);
    }

    /** @param array<string,string> $fields field => message */
    public static function validationError(array $fields): never
    {
        self::json([
            'ok'    => false,
            'error' => [
                'code'    => 'VALIDATION_ERROR',
                'message' => 'Please correct the highlighted fields.',
                'fields'  => $fields,
            ],
        ], 400);
    }

    /** @param array<string,mixed> $data */
    public static function created(array $data): never
    {
        self::json(['ok' => true, 'data' => $data], 201);
    }

    /** @param array<int,array{name:string,error:string}> $files */
    public static function fileValidationError(array $files): never
    {
        self::json([
            'ok'    => false,
            'error' => [
                'code'    => 'FILE_VALIDATION_ERROR',
                'message' => 'One or more files could not be uploaded.',
                'files'   => $files,
            ],
        ], 400);
    }

    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error('NOT_FOUND', $message, 404);
    }

    /**
     * @param list<string> $allowed
     */
    public static function methodNotAllowed(array $allowed): never
    {
        header('Allow: ' . implode(', ', $allowed));
        self::error('METHOD_NOT_ALLOWED', 'HTTP method not allowed for this endpoint.', 405);
    }

    public static function handlePreflight(): never
    {
        self::applyCors();
        http_response_code(204);
        if (!headers_sent()) {
            header('Content-Length: 0');
        }
        exit;
    }

    private static function applyCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if ($origin === null || $origin === '') {
            return;
        }
        if (in_array($origin, Config::allowedOrigins(), true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');
            header('Access-Control-Max-Age: 600');
        }
    }
}
