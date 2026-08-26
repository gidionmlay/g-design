<?php
/**
 * service_fields data access.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceField
{
    private const SELECT_SQL =
        'SELECT id, item_id, field_key, label, type, required, placeholder, hint,
                show_when_json, one_size_when_json
           FROM service_fields';

    /**
     * @param list<int> $itemIds
     * @return array<int,array<int,array<string,mixed>>> fields grouped by item_id
     */
    public static function forItems(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = Database::pdo()->prepare(
            self::SELECT_SQL . " WHERE item_id IN ($placeholders) ORDER BY item_id ASC, sort_order ASC, id ASC"
        );
        $stmt->execute(array_map('intval', $itemIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fieldIds    = array_map(intval(...), array_column($rows, 'id'));
        $optionGroups = ServiceFieldOption::forFields($fieldIds);
        $sizeGroups   = ServiceFieldSize::forFields($fieldIds);

        $grouped = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $grouped[(int) $row['item_id']][] = self::present(
                $row,
                $optionGroups[$id] ?? [],
                $sizeGroups[$id] ?? []
            );
        }
        return $grouped;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function present(array $row, array $options, array $sizes): array
    {
        $field = [
            'id'       => (int) $row['id'],
            'key'      => (string) $row['field_key'],
            'label'    => (string) $row['label'],
            'type'     => (string) $row['type'],
            'required' => (bool) $row['required'],
        ];
        foreach (['placeholder', 'hint'] as $col) {
            if ($row[$col] !== null && $row[$col] !== '') {
                $field[$col] = (string) $row[$col];
            }
        }
        if ($options !== []) {
            $field['options'] = $options;
        }
        if ($sizes !== []) {
            $field['sizes'] = $sizes;
        }
        foreach (['show_when_json' => 'show_when', 'one_size_when_json' => 'one_size_when'] as $col => $key) {
            if ($row[$col] !== null && $row[$col] !== '') {
                $decoded = json_decode((string) $row[$col], true);
                if (is_array($decoded)) {
                    if (isset($decoded['not_in'])) {
                        $decoded['notIn'] = $decoded['not_in'];
                        unset($decoded['not_in']);
                    }
                    $field[$key] = $decoded;
                }
            }
        }
        return $field;
    }
}
