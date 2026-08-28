<?php
/**
 * service_items data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceItem
{
    public const PRICING_FIXED         = 'fixed';
    public const PRICING_STARTING_FROM = 'starting_from';
    public const PRICING_RANGE         = 'range';
    public const PRICING_QUOTE         = 'quote';

    /** @var list<string> */
    public const PRICING_TYPES = [
        self::PRICING_FIXED,
        self::PRICING_STARTING_FROM,
        self::PRICING_RANGE,
        self::PRICING_QUOTE,
    ];

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

    /**
     * Look up an item by its id regardless of active state.
     * Historical requests must resolve even if a service is later disabled.
     *
     * @return array<string,mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug
               FROM service_items i
               JOIN service_categories c ON c.id = i.category_id
              WHERE i.id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * All services for the admin CMS (including inactive), with category and
     * image info. Ordered by category order, then service order.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function allForAdmin(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug
               FROM service_items i
               JOIN service_categories c ON c.id = i.category_id
              ORDER BY c.sort_order ASC, c.id ASC, i.sort_order ASC, i.id ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Paginated, searchable service list for the admin CMS.
     *
     * @param int $page @param int $limit
     * @param array{search?:?string,category_id?:?int,status?:?string} $filters
     * @return array{items:list<array<string,mixed>>,pagination:array{page:int,limit:int,total:int,pages:int}}
     */
    public static function paginatedForAdmin(int $page, int $limit, array $filters): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        $where = ['1 = 1'];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(i.name LIKE ? OR i.slug LIKE ?)';
            array_push($params, $like, $like);
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'i.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (isset($filters['status'])) {
            $status = (string) $filters['status'];
            if ($status === 'active') {
                $where[] = 'i.is_active = 1';
            } elseif ($status === 'inactive') {
                $where[] = 'i.is_active = 0';
            }
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $count = Database::pdo()->prepare('SELECT COUNT(*) FROM service_items i ' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = (int) ceil($total / $limit);

        $offset = ($page - 1) * $limit;
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug
               FROM service_items i
               JOIN service_categories c ON c.id = i.category_id
              ' . $whereSql . '
              ORDER BY c.sort_order ASC, c.id ASC, i.sort_order ASC, i.id ASC
              LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        return [
            'items'      => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }

    /**
     * Create a service with pricing.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>|null
     */
    public static function create(array $data): ?array
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO service_items
                (category_id, slug, name, short_description, description, image_path,
                 pricing_type, price, min_price, max_price, currency, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['slug'],
            $data['name'],
            $data['short_description'] ?? null,
            $data['description'] ?? null,
            $data['image_path'] ?? null,
            $data['pricing_type'] ?? self::PRICING_QUOTE,
            $data['price'] ?? null,
            $data['min_price'] ?? null,
            $data['max_price'] ?? null,
            $data['currency'] ?? 'TZS',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
        ]);
        return self::findById((int) Database::pdo()->lastInsertId());
    }

    /**
     * Update a service's editable fields and pricing.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>|null
     */
    public static function update(int $id, array $data): ?array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_items
                SET category_id = ?, slug = ?, name = ?, short_description = ?, description = ?,
                    image_path = ?, pricing_type = ?, price = ?, min_price = ?, max_price = ?,
                    currency = ?, sort_order = ?
              WHERE id = ?'
        );
        $stmt->execute([
            $data['category_id'],
            $data['slug'],
            $data['name'],
            $data['short_description'] ?? null,
            $data['description'] ?? null,
            $data['image_path'] ?? null,
            $data['pricing_type'],
            $data['price'] ?? null,
            $data['min_price'] ?? null,
            $data['max_price'] ?? null,
            $data['currency'] ?? 'TZS',
            $data['sort_order'] ?? 0,
            $id,
        ]);
        return self::findById($id);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_items SET is_active = ? WHERE id = ?'
        );
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function setSortOrder(int $id, int $order): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_items SET sort_order = ? WHERE id = ?'
        );
        $stmt->execute([$order, $id]);
    }

    /**
     * Whether another service in the same category already uses this slug.
     */
    public static function slugExistsInCategory(string $slug, int $categoryId, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM service_items WHERE slug = ? AND category_id = ? AND id <> ? LIMIT 1'
        );
        $stmt->execute([$slug, $categoryId, $exceptId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * A single image reference for a service, if any.
     *
     * @return array<string,mixed>|null
     */
    public static function image(int $itemId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, stored_filename, mime_type, file_size FROM service_images
              WHERE item_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Attach a managed image reference to a service, removing the prior one.
     *
     * @param array<string,mixed> $image e.g. ['stored_filename'=>...,'mime_type'=>...,'file_size'=>...]
     */
    public static function replaceImage(int $itemId, array $image): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM service_images WHERE item_id = ?')->execute([$itemId]);
            $stmt = $pdo->prepare(
                'INSERT INTO service_images (item_id, stored_filename, mime_type, file_size)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $itemId,
                $image['stored_filename'],
                $image['mime_type'],
                $image['file_size'],
            ]);
            $newId = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $newId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Fetch a single managed image row (for serving) by id.
     *
     * @return array<string,mixed>|null
     */
    public static function imageById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT im.*, i.is_active AS item_active, i.slug AS item_slug, i.name AS item_name,
                    c.is_active AS category_active
               FROM service_images im
               JOIN service_items i ON i.id = im.item_id
               JOIN service_categories c ON c.id = i.category_id
              WHERE im.id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Normalise pricing into a safe presentation map for API responses.
     * No filesystem paths or raw SQL ever leak here.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function presentPricing(array $row): array
    {
        $type = in_array((string) ($row['pricing_type'] ?? ''), self::PRICING_TYPES, true)
            ? (string) $row['pricing_type']
            : self::PRICING_QUOTE;

        $data = [
            'type'     => $type,
            'currency' => (string) ($row['currency'] ?? 'TZS') ?: 'TZS',
        ];

        if ($type === self::PRICING_RANGE) {
            $data['min'] = $row['min_price'] !== null ? (float) $row['min_price'] : null;
            $data['max'] = $row['max_price'] !== null ? (float) $row['max_price'] : null;
        } else {
            $data['value'] = $row['price'] !== null ? (float) $row['price'] : null;
        }

        return $data;
    }

    /**
     * The public image reference for a service: the managed service_image
     * served through the safe route if present, otherwise the legacy static
     * asset path stored in image_path.
     *
     * @return array<string,mixed>|string|null
     */
    public static function publicImage(int $itemId): array|string|null
    {
        $managed = self::image($itemId);
        if ($managed !== null) {
            return [
                'url'  => '/api/v1/service-images/' . (int) $managed['id'],
                'mime' => (string) $managed['mime_type'],
            ];
        }

        $row = self::findById($itemId);
        return $row !== null && $row['image_path'] !== null ? (string) $row['image_path'] : null;
    }
}
