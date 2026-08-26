<?php
/**
 * requests data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RequestModel
{
    /**
     * Insert a new request.
     *
     * Expects to be called inside an existing transaction.
     *
     * @param array<string,mixed> $data
     * @return array{id:int,request_reference:string,status:string,created_at:string}
     */
    public static function create(array $data): array
    {
        $pdo = Database::pdo();

        $reference = self::generateReference($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO requests
                (request_reference, service_item_id, client_name, client_email,
                 client_phone, company_name, description, quantity,
                 requirements_data, status)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $reference,
            $data['service_item_id'],
            $data['client_name'],
            $data['client_email'],
            $data['client_phone'] ?? null,
            $data['company_name'] ?? null,
            $data['description'] ?? null,
            $data['quantity'] ?? null,
            $data['requirements_data'] !== null
                ? json_encode($data['requirements_data'], JSON_UNESCAPED_UNICODE)
                : null,
            $data['status'] ?? 'pending',
        ]);

        $id = (int) $pdo->lastInsertId();

        return self::findById($id);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM requests WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function deleteById(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM requests WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByReference(string $reference): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM requests WHERE request_reference = ? LIMIT 1'
        );
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Generate a unique reference: GDS-REQ-YYYYMMDD-XXXX
     *
     * Uses a date prefix + 4-char hex suffix. Retries on collision (max 5).
     */
    private static function generateReference(\PDO $pdo): string
    {
        $date = date('Ymd');
        $prefix = 'GDS-REQ-' . $date . '-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $reference = $prefix . $suffix;

            $stmt = $pdo->prepare(
                'SELECT 1 FROM requests WHERE request_reference = ? LIMIT 1'
            );
            $stmt->execute([$reference]);
            if ($stmt->fetch() === false) {
                return $reference;
            }
        }

        throw new \RuntimeException('Unable to generate a unique request reference.');
    }
}
