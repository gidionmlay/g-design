<?php
/**
 * Secure service image upload.
 *
 * Files are stored OUTSIDE the public document root under
 *   storage/service-images/
 * The database stores only a randomized stored filename + MIME. No filesystem
 * paths are ever returned to the API; images are served through a controller
 * route that resolves the stored name by id.
 *
 * Allowed: JPG/JPEG, PNG, WebP (server-side finfo detection).
 * Blocked: PHP, HTML, SVG, JS, EXE, and any non-image payload.
 */

declare(strict_types=1);

namespace App\Core;

final class ServiceImageUpload
{
    /** @var array<string,string> Allowed MIME types => extension */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Extensions that are never allowed regardless of MIME. */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml',
        'html', 'htm', 'js', 'jsx', 'ts', 'tsx',
        'svg', 'exe', 'sh', 'bat', 'cmd', 'com', 'msi',
        'cgi', 'pl', 'py', 'rb', 'jar',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    /**
     * Validate and store a single service image.
     *
     * @param array<string,mixed> $file $_FILES entry
     * @return array{stored_filename:string,mime_type:string,file_size:int}
     * @throws \RuntimeException on validation failure
     */
    public static function process(array $file): array
    {
        if (!isset($file['tmp_name']) || $file['tmp_name'] === '') {
            throw new \RuntimeException('No image was provided.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Image upload failed.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('Image exceeds the maximum size of 5 MB.');
        }
        if ($size <= 0) {
            throw new \RuntimeException('Image is empty.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw new \RuntimeException('File type ".' . $extension . '" is not allowed.');
        }

        $mimeType = self::detectMimeType($file['tmp_name']);
        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            throw new \RuntimeException('Only JPG, PNG and WebP images are allowed.');
        }

        $storedExtension = self::ALLOWED_TYPES[$mimeType];
        $storedFilename = 'svc_' . bin2hex(random_bytes(16)) . '.' . $storedExtension;
        $dir = self::storageDir();

        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $path = $dir . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('Failed to store the image.');
        }

        return [
            'stored_filename' => $storedFilename,
            'mime_type'       => $mimeType,
            'file_size'       => $size,
        ];
    }

    /**
     * Remove a stored image file by its stored filename.
     */
    public static function remove(string $storedFilename): void
    {
        if ($storedFilename === '') {
            return;
        }
        $path = self::storageDir() . '/' . basename($storedFilename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Resolve the absolute path for a stored filename (basename-enforced).
     *
     * @return string empty if not found
     */
    public static function resolvePath(string $storedFilename): string
    {
        if ($storedFilename === '') {
            return '';
        }
        $safe = basename($storedFilename);
        $path = self::storageDir() . '/' . $safe;
        return is_readable($path) ? $path : '';
    }

    private static function detectMimeType(string $tmpPath): string
    {
        $realMime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $realMime = finfo_file($finfo, $tmpPath) ?: '';
                finfo_close($finfo);
            }
        }
        if (isset(self::ALLOWED_TYPES[$realMime])) {
            return $realMime;
        }
        return '';
    }

    public static function storageDir(): string
    {
        return defined('STORAGE_DIR')
            ? STORAGE_DIR . '/service-images'
            : dirname(__DIR__, 2) . '/storage/service-images';
    }

    public static function maxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }
}
