<?php
/**
 * service_categories data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ServiceCategory
{
    /** @return array<int,array<string,mixed>> */
    public static function allActive(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM service_categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function findActiveBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM service_categories WHERE is_active = 1 AND slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string,mixed>|null */
    public static function findActiveById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM service_categories WHERE is_active = 1 AND id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
