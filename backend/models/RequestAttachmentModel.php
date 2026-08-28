<?php
/**
 * request_attachments data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RequestAttachmentModel
{
    /**
     * Insert an attachment record.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed> The inserted row
     */
    public static function create(array $data): array
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO request_attachments
                (request_id, original_filename, stored_filename, storage_path,
                 mime_type, file_extension, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['request_id'],
            $data['original_filename'],
            $data['stored_filename'],
            $data['storage_path'],
            $data['mime_type'],
            $data['file_extension'],
            $data['file_size'],
        ]);

        return self::findById((int) Database::pdo()->lastInsertId());
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM request_attachments WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public static function findByRequestId(int $requestId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM request_attachments WHERE request_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array<string,mixed>> */
    public static function findByRequestReference(string $reference): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ra.*
               FROM request_attachments ra
               JOIN requests r ON r.id = ra.request_id
              WHERE r.request_reference = ?
              ORDER BY ra.id ASC'
        );
        $stmt->execute([$reference]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete all attachments for a request (used in rollback).
     */
    public static function deleteByRequestId(int $requestId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM request_attachments WHERE request_id = ?'
        );
        $stmt->execute([$requestId]);
    }

    /**
     * Find an attachment that must belong to the given request.
     * Enforces ownership — never exposes an attachment across requests.
     *
     * @return array<string,mixed>|null
     */
    public static function findForRequest(int $attachmentId, int $requestId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM request_attachments
              WHERE id = ? AND request_id = ?
              LIMIT 1'
        );
        $stmt->execute([$attachmentId, $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Present an attachment row for API/UI consumption.
     * Never reveals the physical storage path.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function present(array $row): array
    {
        $mime = (string) $row['mime_type'];
        return [
            'id'          => (int) $row['id'],
            'request_id'  => (int) $row['request_id'],
            'filename'    => (string) $row['original_filename'],
            'mime_type'   => $mime,
            'extension'   => (string) $row['file_extension'],
            'size'        => (int) $row['file_size'],
            'created_at'  => (string) $row['created_at'],
            'is_image'    => str_starts_with($mime, 'image/'),
            'is_pdf'      => $mime === 'application/pdf',
            'url'         => '/api/v1/admin/requests/' . (int) $row['request_id']
                           . '/attachments/' . (int) $row['id'],
        ];
    }
}
