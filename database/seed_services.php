<?php
/**
 * G DESIGN — idempotent service catalog seeder.
 *
 * Usage:  php database/seed_services.php
 *
 * Imports database/catalog.php (extracted from the original QUOTE_CONFIG)
 * into MySQL. Safe to run repeatedly: rows are matched by natural keys
 * (category slug, item slug within category, field key within item,
 * option value / size label within field) and updated in place — never
 * duplicated. Options/sizes removed from the source catalog are pruned.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/backend/bootstrap.php';

$catalog = require __DIR__ . '/catalog.php';
if (!is_array($catalog)) {
    fwrite(STDERR, "catalog.php did not return an array.\n");
    exit(1);
}

try {
    $pdo = App\Core\Database::pdo();
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: {$e->getMessage()}\n");
    fwrite(STDERR, "Check your .env DB_* settings and that MySQL is running.\n");
    exit(1);
}

$stats = ['categories' => 0, 'items' => 0, 'fields' => 0, 'options' => 0, 'sizes' => 0];

function upsert(PDO $pdo, string $table, array $uniqueCols, array $data, array $updateCols): int
{
    $where = implode(' AND ', array_map(static fn($c) => "`$c` = ?", $uniqueCols));
    $select = $pdo->prepare("SELECT id FROM `$table` WHERE $where LIMIT 1");
    $select->execute(array_values(array_intersect_key($data, array_flip($uniqueCols))));
    $existing = $select->fetchColumn();

    if ($existing !== false) {
        if ($updateCols !== []) {
            $set = implode(', ', array_map(static fn($c) => "`$c` = ?", $updateCols));
            $values = array_map(static fn($c) => $data[$c], $updateCols);
            $values[] = $existing;
            $pdo->prepare("UPDATE `$table` SET $set WHERE id = ?")->execute($values);
        }
        return (int) $existing;
    }

    $cols = array_keys($data);
    $pdo->prepare(sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        '`' . implode('`, `', $cols) . '`',
        implode(', ', array_fill(0, count($cols), '?'))
    ))->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}

/** Prune child rows whose value is no longer present in the source catalog. */
function syncChildren(PDO $pdo, string $table, string $valueCol, string $fkCol, int $fkId, array $rows): void
{
    $keep = array_column($rows, 'id');
    if ($keep === []) {
        $pdo->prepare("DELETE FROM `$table` WHERE `$fkCol` = ?")->execute([$fkId]);
        return;
    }
    $placeholders = implode(',', array_fill(0, count($keep), '?'));
    $params = $keep;
    $params[] = $fkId;
    $pdo->prepare("DELETE FROM `$table` WHERE id NOT IN ($placeholders) AND `$fkCol` = ?")->execute($params);
}

$pdo->beginTransaction();

try {
    foreach ($catalog as $cIndex => $category) {
        $categoryId = upsert(
            $pdo,
            'service_categories',
            ['slug'],
            [
                'slug'        => $category['slug'],
                'name'        => $category['name'],
                'tag'         => $category['tag'],
                'description' => $category['description'],
                'image_path'  => $category['image_path'],
                'sort_order'  => $cIndex,
                'is_active'   => 1,
            ],
            ['name', 'tag', 'description', 'image_path', 'sort_order']
        );
        $stats['categories']++;

        foreach ($category['items'] as $iIndex => $item) {
            $itemId = upsert(
                $pdo,
                'service_items',
                ['category_id', 'slug'],
                [
                    'category_id' => $categoryId,
                    'slug'        => $item['slug'],
                    'name'        => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order'  => $iIndex,
                    'is_active'   => 1,
                ],
                ['name', 'description', 'sort_order']
            );
            $stats['items']++;

            $fieldRows = [];
            foreach ($item['fields'] as $fIndex => $field) {
                $fieldId = upsert(
                    $pdo,
                    'service_fields',
                    ['item_id', 'field_key'],
                    [
                        'item_id'             => $itemId,
                        'field_key'           => $field['field_key'],
                        'label'               => $field['label'],
                        'type'                => $field['type'],
                        'required'            => $field['required'] ? 1 : 0,
                        'placeholder'         => $field['placeholder'],
                        'hint'                => $field['hint'],
                        'sort_order'          => $field['sort_order'] ?? $fIndex,
                        'show_when_json'      => $field['show_when'] ? json_encode($field['show_when'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                        'one_size_when_json'  => $field['one_size_when'] ? json_encode($field['one_size_when'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    ],
                    ['label', 'type', 'required', 'placeholder', 'hint', 'sort_order', 'show_when_json', 'one_size_when_json']
                );
                $fieldRows[] = ['id' => $fieldId, 'field_key' => $field['field_key']];
                $stats['fields']++;
                // Options
                $optionRows = [];
                foreach (($field['options'] ?? []) as $oIndex => $optionValue) {
                    $optionRows[] = ['id' => upsert(
                        $pdo,
                        'service_field_options',
                        ['field_id', 'option_value'],
                        [
                            'field_id'     => $fieldId,
                            'option_value' => $optionValue,
                            'sort_order'   => $oIndex,
                        ],
                        ['sort_order']
                    ), 'option_value' => $optionValue];
                    $stats['options']++;
                }
                syncChildren($pdo, 'service_field_options', 'option_value', 'field_id', $fieldId, $optionRows);

                // Sizes (sizegrid fields only)
                $sizeRows = [];
                foreach (($field['sizes'] ?? []) as $sIndex => $sizeLabel) {
                    $sizeRows[] = ['id' => upsert(
                        $pdo,
                        'service_field_sizes',
                        ['field_id', 'size_label'],
                        [
                            'field_id'   => $fieldId,
                            'size_label' => $sizeLabel,
                            'sort_order' => $sIndex,
                        ],
                        ['sort_order']
                    ), 'size_label' => $sizeLabel];
                    $stats['sizes']++;
                }
                syncChildren($pdo, 'service_field_sizes', 'size_label', 'field_id', $fieldId, $sizeRows);
            }

            // Prune fields removed from the source catalog (options/sizes cascade).
            syncChildren($pdo, 'service_fields', 'field_key', 'item_id', $itemId, $fieldRows);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed failed, rolled back: {$e->getMessage()}\n");
    exit(1);
}

printf(
    "Seeded: %d categories, %d items, %d fields, %d options, %d sizes.\n",
    $stats['categories'],
    $stats['items'],
    $stats['fields'],
    $stats['options'],
    $stats['sizes']
);
