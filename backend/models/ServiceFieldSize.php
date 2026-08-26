<?php
/**
 * service_field_sizes data access (sizegrid columns).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ServiceFieldSize
{
    /**
     * @param list<int> $fieldIds
     * @return array<int,array<int,string>> size labels grouped by field_id
     */
    public static function forFields(array $fieldIds): array
    {
        if ($fieldIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT field_id, size_label AS value FROM service_field_sizes
              WHERE field_id IN ($placeholders) ORDER BY field_id ASC, sort_order ASC, id ASC"
        );
        $stmt->execute(array_map('intval', $fieldIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['field_id']][] = (string) $row['value'];
        }
        return $grouped;
    }
}
