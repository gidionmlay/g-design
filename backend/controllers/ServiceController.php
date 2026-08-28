<?php
/**
 * Public service catalog endpoints.
 *
 *   GET /api/v1/services          -> all active categories with items + fields
 *   GET /api/v1/services/{slug}   -> one active category (preferred) or item
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Validator;
use App\Models\ServiceCategory;
use App\Models\ServiceField;
use App\Models\ServiceItem;

final class ServiceController
{
    public function index(): void
    {
        $categories = ServiceCategory::allActive();
        $items      = ServiceItem::allActive();
        $fieldMap   = ServiceField::forItems(array_map(intval(...), array_column($items, 'id')));

        Response::ok([
            'categories' => self::presentCategories($categories, $items, $fieldMap),
        ]);
    }

    public function show(string $slug): void
    {
        if (!Validator::slug($slug)) {
            Response::notFound('Unknown service slug.');
        }

        // {slug} resolves to a category first (the wizard's primary identifiers).
        $category = ServiceCategory::findActiveBySlug($slug);
        if ($category !== null) {
            $items   = ServiceItem::activeByCategory((int) $category['id']);
            $fieldMap = ServiceField::forItems(array_map(intval(...), array_column($items, 'id')));
            Response::ok([
                'category' => self::presentCategories([$category], $items, $fieldMap)[0],
            ]);
        }

        // Fallback: an item slug. Item slugs may repeat across categories;
        // the first match in catalog order wins and its parent is included.
        $matches = ServiceItem::findActiveBySlug($slug);
        if ($matches !== []) {
            $item = $matches[0];
            $fields = ServiceField::forItems([(int) $item['id']])[(int) $item['id']] ?? [];
            $category = ServiceCategory::findActiveById((int) $item['category_id']);

            $presented = self::presentItem($item, [$item['id'] => $fields]);

            Response::ok([
                'item' => [
                    'slug'             => (string) $item['slug'],
                    'name'             => (string) $item['name'],
                    'short_description'=> $item['short_description'] ?? null,
                    'description'      => $item['description'] ?? null,
                    'image'            => ServiceItem::publicImage((int) $item['id']),
                    'pricing'          => ServiceItem::presentPricing($item),
                    'category'         => $category === null ? null : [
                        'slug' => (string) $category['slug'],
                        'name' => (string) $category['name'],
                    ],
                    'fields'           => $presented['fields'],
                ],
            ]);
        }

        Response::notFound('Unknown service slug.');
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<int,array<string,mixed>> $items
     * @param array<int,array<int,array<string,mixed>>> $fieldMap
     * @return array<int,array<string,mixed>>
     */
    private static function presentCategories(array $categories, array $items, array $fieldMap): array
    {
        $byCategory = [];
        foreach ($items as $item) {
            $byCategory[(int) $item['category_id']][] = $item;
        }

        $out = [];
        foreach ($categories as $category) {
            $cid = (int) $category['id'];
            $out[] = [
                'slug'        => (string) $category['slug'],
                'name'        => (string) $category['name'],
                'tag'         => $category['tag'] ?? null,
                'description' => $category['description'] ?? null,
                'image'       => $category['image_path'] ?? null,
                'items'       => array_map(
                    static fn(array $item): array => self::presentItem($item, $fieldMap),
                    $byCategory[$cid] ?? []
                ),
            ];
        }
        return $out;
    }

    /** @param array<string,mixed> $item @return array<string,mixed> */
    private static function presentItem(array $item, array $fieldMap): array
    {
        return [
            'slug'             => (string) $item['slug'],
            'name'             => (string) $item['name'],
            'short_description'=> $item['short_description'] ?? null,
            'description'      => $item['description'] ?? null,
            'image'            => ServiceItem::publicImage((int) $item['id']),
            'pricing'          => ServiceItem::presentPricing($item),
            'fields'           => $fieldMap[(int) $item['id']] ?? [],
        ];
    }
}
