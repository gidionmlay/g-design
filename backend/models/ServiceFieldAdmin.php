<?php
/**
 * Admin tooling for dynamic service requirement fields and their options.
 *
 * Fields live in service_fields; options in service_field_options. Historical
 * request values are keyed by field_key, so admin edits must NEVER reuse or
 * rename an existing key in a breaking way. This model provides create /
 * update / reorder / deactivate plus option management while leaving the
 * public read paths (ServiceField::forItems) intact.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceFieldAdmin
{
    /**
     * Field types the admin CMS may create. Ensure each `select`/`radio`
     * field has options before it is used.
     *
     * @var list<string>
     */
    public const TYPES = [
        'text',
        'textarea',
        'email',
        'tel',
        'number',
        'date',
        'select',
        'radio',
        'checkbox',
    ];

    /**
     * List a service's fields with their options, the admin presentation shape.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forItem(int $itemId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, field_key, label, type, required, placeholder, hint,
                    sort_order, show_when_json
               FROM service_fields
              WHERE item_id = ?
              ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$itemId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fieldIds = array_map(intval(...), array_column($rows, 'id'));
        $options  = ServiceFieldOption::forFields($fieldIds);

        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $out[] = [
                'id'          => $id,
                'key'         => (string) $row['field_key'],
                'label'       => (string) $row['label'],
                'type'        => (string) $row['type'],
                'required'    => (bool) $row['required'],
                'placeholder' => $row['placeholder'] !== null ? (string) $row['placeholder'] : null,
                'hint'        => $row['hint'] !== null ? (string) $row['hint'] : null,
                'sort_order'  => (int) $row['sort_order'],
                'options'     => $options[$id] ?? [],
            ];
        }
        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findById(int $id, ?int $itemId = null): ?array
    {
        $sql = 'SELECT * FROM service_fields WHERE id = ?';
        $params = [$id];
        if ($itemId !== null) {
            $sql .= ' AND item_id = ?';
            $params[] = $itemId;
        }
        $stmt = Database::pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Create a field. The unique (item_id, field_key) constraint protects
     * against duplicate keys; callers should pre-check for a friendlier error.
     *
     * @param array<string,mixed> $data
     * @return int new field id
     */
    public static function create(int $itemId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO service_fields
                (item_id, field_key, label, type, required, placeholder, hint, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $itemId,
            $data['field_key'],
            $data['label'],
            $data['type'],
            $data['required'] ? 1 : 0,
            $data['placeholder'] ?? null,
            $data['hint'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update a field's editable properties. field_key is intentionally NOT
     * updatable once set, to protect historical request data.
     *
     * @param array<string,mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE service_fields
                SET label = ?, type = ?, required = ?, placeholder = ?, hint = ?, sort_order = ?
              WHERE id = ?'
        );
        $stmt->execute([
            $data['label'],
            $data['type'],
            $data['required'] ? 1 : 0,
            $data['placeholder'] ?? null,
            $data['hint'] ?? null,
            $data['sort_order'] ?? 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM service_fields WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Whether a field_key is already taken within a service.
     */
    public static function keyExists(int $itemId, string $key, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM service_fields WHERE item_id = ? AND field_key = ? AND id <> ? LIMIT 1'
        );
        $stmt->execute([$itemId, $key, $exceptId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Add an option to a field (deduplicated, appended to the sort order).
     *
     * @return int option id
     */
    public static function addOption(int $fieldId, string $value): int
    {
        $max = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), -1) FROM service_field_options WHERE field_id = ?'
        );
        $max->execute([$fieldId]);
        $next = ((int) $max->fetchColumn()) + 1;

        $stmt = Database::pdo()->prepare(
            'INSERT INTO service_field_options (field_id, option_value, sort_order)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$fieldId, $value, $next]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Replace a field's full option set (admin "manage options").
     *
     * @param list<string> $values
     */
    public static function setOptions(int $fieldId, array $values): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM service_field_options WHERE field_id = ?')->execute([$fieldId]);
            $stmt = $pdo->prepare(
                'INSERT INTO service_field_options (field_id, option_value, sort_order) VALUES (?, ?, ?)'
            );
            foreach (array_values($values) as $i => $value) {
                if (trim((string) $value) === '') {
                    continue;
                }
                $stmt->execute([$fieldId, (string) $value, $i]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Remove one option by id (historical stored VALUES remain readable).
     */
    public static function removeOption(int $optionId, int $fieldId): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM service_field_options WHERE id = ? AND field_id = ?'
        );
        $stmt->execute([$optionId, $fieldId]);
        return $stmt->rowCount() > 0;
    }
}
