<?php
/**
 * service_categories data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceCategory
{
    /**
     * All categories for the admin CMS (including inactive), each annotated
     * with the number of services under it. Ordered by display order.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function allForAdmin(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT sc.*, (SELECT COUNT(*) FROM service_items si WHERE si.category_id = sc.id) AS service_count
               FROM service_categories sc
              ORDER BY sc.sort_order ASC, sc.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a category.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>|null
     */
    public static function create(array $data): ?array
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO service_categories (slug, name, tag, description, image_path, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['slug'],
            $data['name'],
            $data['tag'] ?? null,
            $data['description'] ?? null,
            $data['image_path'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
        ]);
        return self::findById((int) Database::pdo()->lastInsertId());
    }

    /**
     * Update a category's editable fields (slug/name/tag/description/image).
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>|null
     */
    public static function update(int $id, array $data): ?array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_categories
                SET slug = ?, name = ?, tag = ?, description = ?, image_path = ?
              WHERE id = ?'
        );
        $stmt->execute([
            $data['slug'],
            $data['name'],
            $data['tag'] ?? null,
            $data['description'] ?? null,
            $data['image_path'] ?? null,
            $id,
        ]);
        return self::findById($id);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_categories SET is_active = ? WHERE id = ?'
        );
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function setSortOrder(int $id, int $order): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_categories SET sort_order = ? WHERE id = ?'
        );
        $stmt->execute([$order, $id]);
    }

    public static function slugExists(string $slug, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM service_categories WHERE slug = ? AND id <> ? LIMIT 1'
        );
        $stmt->execute([$slug, $exceptId]);
        return $stmt->fetchColumn() !== false;
    }
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

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM service_categories WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
