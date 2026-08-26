<?php
/**
 * Secure file upload service.
 *
 * Handles validation, secure filename generation, storage,
 * and metadata extraction for uploaded files.
 */

declare(strict_types=1);

namespace App\Core;

final class FileUpload
{
    /** @var array<string,string> Allowed MIME types => extension */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /** Extensions that are never allowed regardless of MIME */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml',
        'html', 'htm', 'js', 'jsx', 'ts', 'tsx',
        'svg', 'exe', 'sh', 'bat', 'cmd', 'com', 'msi',
        'sql', 'db', 'sqlite',
        'bat', 'cmd', 'ps1', 'vbs', 'js', 'jar',
        'cgi', 'pl', 'py', 'rb',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const MAX_FILES = 5;

    /**
     * Validate and store a single uploaded file.
     *
     * @param array<string,mixed> $file $_FILES entry
     * @param int $requestId The request this file belongs to
     * @return array<string,mixed> File metadata on success
     * @throws \RuntimeException on validation failure
     */
    public static function process(array $file, int $requestId): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage($file['error']));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('File upload failed.');
        }

        $size = (int) $file['size'];
        if ($size > self::MAX_FILE_SIZE) {
            throw new \RuntimeException(
                'File "' . $file['name'] . '" exceeds the maximum size of 10 MB.'
            );
        }

        if ($size === 0) {
            throw new \RuntimeException('File "' . $file['name'] . '" is empty.');
        }

        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw new \RuntimeException(
                'File type ".' . $extension . '" is not allowed.'
            );
        }

        $mimeType = self::detectMimeType($file['tmp_name'], $extension);
        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            throw new \RuntimeException(
                'File type "' . ($mimeType ?: 'unknown') . '" is not allowed.'
            );
        }

        $storedExtension = self::ALLOWED_TYPES[$mimeType];
        $storedFilename = self::generateStoredFilename($requestId, $storedExtension);
        $storageDir = self::storageDir();
        $storagePath = $storageDir . '/' . $storedFilename;

        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0750, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
            throw new \RuntimeException('Failed to store file "' . $originalName . '".');
        }

        return [
            'original_filename' => $originalName,
            'stored_filename'   => $storedFilename,
            'storage_path'      => $storagePath,
            'mime_type'         => $mimeType,
            'file_extension'    => $storedExtension,
            'file_size'         => $size,
        ];
    }

    /**
     * Remove a stored file from disk.
     */
    public static function remove(string $storagePath): void
    {
        if ($storagePath !== '' && is_file($storagePath)) {
            @unlink($storagePath);
        }
    }

    /**
     * Get the absolute storage directory path.
     */
    public static function storageDir(): string
    {
        return defined('STORAGE_DIR')
            ? STORAGE_DIR . '/uploads'
            : dirname(__DIR__, 2) . '/storage/uploads';
    }

    /**
     * Detect MIME type using finfo (server-side), falling back to extension mapping.
     */
    private static function detectMimeType(string $tmpPath, string $extension): string
    {
        $realMime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $realMime = finfo_file($finfo, $tmpPath) ?: '';
                finfo_close($finfo);
            }
        }

        if ($realMime !== '' && isset(self::ALLOWED_TYPES[$realMime])) {
            return $realMime;
        }

        $extMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
        ];

        return $extMap[$extension] ?? $realMime;
    }

    /**
     * Generate a secure, unique stored filename.
     *
     * Format: req_{requestId}_{random}.{ext}
     */
    private static function generateStoredFilename(int $requestId, string $extension): string
    {
        return 'req_' . $requestId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Map PHP upload error codes to human-readable messages.
     */
    private static function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form upload size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by a server extension.',
            default               => 'Unknown upload error.',
        };
    }

    /** @return int */
    public static function maxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /** @return int */
    public static function maxFiles(): int
    {
        return self::MAX_FILES;
    }

    /** @return array<string,string> */
    public static function allowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }
}
