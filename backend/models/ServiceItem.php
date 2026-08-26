<?php
/**
 * service_items data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ServiceItem
{
    private const BASE_SQL =
        'SELECT i.*
           FROM service_items i
           JOIN service_categories c ON c.id = i.category_id
          WHERE i.is_active = 1 AND c.is_active = 1';

    /** @return array<int,array<string,mixed>> */
    public static function allActive(): array
    {
        $stmt = Database::pdo()->prepare(
            self::BASE_SQL . ' ORDER BY c.sort_order ASC, c.id ASC, i.sort_order ASC, i.id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function activeByCategory(int $categoryId): array
    {
        $stmt = Database::pdo()->prepare(
            self::BASE_SQL . ' AND i.category_id = ? ORDER BY i.sort_order ASC, i.id ASC'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Item slugs are unique per category but may repeat across categories.
     * Returns every active match ordered deterministically; callers decide.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function findActiveBySlug(string $slug): array
    {
        $stmt = Database::pdo()->prepare(
            self::BASE_SQL . ' AND i.slug = ? ORDER BY c.sort_order ASC, i.sort_order ASC'
        );
        $stmt->execute([$slug]);
        return $stmt->fetchAll();
    }
}
