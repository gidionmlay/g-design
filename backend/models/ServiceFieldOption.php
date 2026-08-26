<?php
/**
 * service_field_options data access (radio / checkbox choices).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceFieldOption
{
    /**
     * @param list<int> $fieldIds
     * @return array<int,array<int,string>> option values grouped by field_id
     */
    public static function forFields(array $fieldIds): array
    {
        return self::columnGrouped('service_field_options', 'option_value', $fieldIds);
    }

    /** @param list<int> $fieldIds @return array<int,array<int,string>> */
    protected static function columnGrouped(string $table, string $column, array $fieldIds): array
    {
        if ($fieldIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT field_id, {$column} AS value FROM {$table} WHERE field_id IN ($placeholders) ORDER BY field_id ASC, sort_order ASC, id ASC");
        $stmt->execute(array_map('intval', $fieldIds));

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['field_id']][] = (string) $row['value'];
        }
        return $grouped;
    }
}
