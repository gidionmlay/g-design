<?php
/**
 * requests data access.
 *
 * Includes the M4 status model and Admin query helpers.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RequestModel
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_REVIEWING   = 'reviewing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    /** @var list<string> Every administrable status, in canonical order. */
    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Controlled status transitions.
     *
     *   pending      → reviewing, cancelled
     *   reviewing    → in_progress, cancelled
     *   in_progress  → completed, cancelled
     *   completed    → (terminal)
     *   cancelled    → (terminal)
     *
     * @var array<string,list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_REVIEWING, self::STATUS_CANCELLED],
        self::STATUS_REVIEWING   => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED   => [],
        self::STATUS_CANCELLED   => [],
    ];

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::ALL_STATUSES, true);
    }

    /**
     * Whether moving a request from $from to $to is permitted by the model.
     */
    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Insert a new request.
     *
     * Expects to be called inside an existing transaction.
     *
     * @param array<string,mixed> $data
     * @return array{id:int,request_reference:string,status:string,created_at:string}
     */
    public static function create(array $data): array
    {
        $pdo = Database::pdo();

        $reference = self::generateReference($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO requests
                (request_reference, service_item_id, client_name, client_email,
                 client_phone, company_name, description, quantity,
                 requirements_data, status)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $reference,
            $data['service_item_id'],
            $data['client_name'],
            $data['client_email'],
            $data['client_phone'] ?? null,
            $data['company_name'] ?? null,
            $data['description'] ?? null,
            $data['quantity'] ?? null,
            $data['requirements_data'] !== null
                ? json_encode($data['requirements_data'], JSON_UNESCAPED_UNICODE)
                : null,
            $data['status'] ?? self::STATUS_PENDING,
        ]);

        $id = (int) $pdo->lastInsertId();

        return self::findById($id);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM requests WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function deleteById(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM requests WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByReference(string $reference): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM requests WHERE request_reference = ? LIMIT 1'
        );
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Dashboard overview: request statistics + recent requests.
     *
     * All values are computed from MySQL (never hardcoded).
     *
     * @param int $recentLimit how many recent requests to include
     * @return array{statistics:array<string,int>,recent_requests:list<array<string,mixed>>}
     */
    public static function overview(int $recentLimit = 6): array
    {
        $pdo  = Database::pdo();
        $stat = [
            'total_requests' => 0,
            'pending'        => 0,
            'reviewing'      => 0,
            'in_progress'    => 0,
            'completed'      => 0,
            'cancelled'      => 0,
        ];

        $stmt = $pdo->query('SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['status'];
            if (array_key_exists($key, $stat)) {
                $stat[$key] = (int) $row['cnt'];
                $stat['total_requests'] += (int) $row['cnt'];
            }
        }

        $recent = self::paginated(1, $recentLimit < 1 ? 1 : $recentLimit, [
            'status'     => null,
            'service_id' => null,
            'service'    => null,
            'search'     => null,
            'from'       => null,
            'to'         => null,
        ]);

        return [
            'statistics'     => $stat,
            'recent_requests' => $recent['items'],
        ];
    }

    /**
     * Paginated, searchable, filterable request list.
     *
     * Joins service + category and counts attachments without per-row queries.
     *
     * @param int $page @param int $limit
     * @param array{status?:?string,service_id?:?int,service?:?string,search?:?string,from?:?string,to?:?string} $filters
     * @return array{items:list<array<string,mixed>>,pagination:array{page:int,limit:int,total:int,pages:int}}
     */
    public static function paginated(int $page, int $limit, array $filters): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        [$where, $params] = self::buildListWhere($filters);

        $base = 'FROM requests r
                 JOIN service_items si ON si.id = r.service_item_id
                 JOIN service_categories sc ON sc.id = si.category_id';
        $whereSql = $where === '' ? '' : ' WHERE ' . $where;

        $count = Database::pdo()->prepare('SELECT COUNT(*) ' . $base . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = (int) ceil($total / $limit);

        $offset = ($page - 1) * $limit;
        $stmt = Database::pdo()->prepare(
            'SELECT r.id, r.request_reference, r.client_name, r.client_email,
                    r.client_phone, r.company_name, r.status, r.created_at, r.updated_at,
                    si.id AS service_item_id, si.name AS service_name, si.slug AS service_slug,
                    sc.name AS category_name, sc.slug AS category_slug,
                    (SELECT COUNT(*) FROM request_attachments ra WHERE ra.request_id = r.id)
                        AS attachments_count
             ' . $base . $whereSql . '
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = self::presentListRow($row);
        }

        return [
            'items'      => $items,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }

    /**
     * Full request detail (request + service + client + requirements raw map).
     * Attachments are fetched by the caller to keep ownership checks explicit.
     *
     * @return array<string,mixed>|null
     */
    public static function findForDetail(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, si.name AS service_name, si.slug AS service_slug, si.description AS service_description,
                    sc.name AS category_name, sc.slug AS category_slug
               FROM requests r
               JOIN service_items si ON si.id = r.service_item_id
               JOIN service_categories sc ON sc.id = si.category_id
              WHERE r.id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Update a request status.
     *
     * @return array<string,mixed>|null updated request row, or null if missing
     */
    public static function updateStatus(int $id, string $status): ?array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE requests SET status = ? WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
        return self::findById($id);
    }

    /**
     * Resolve stored requirements_data into labelled, typed entries using the
     * service field configuration. Historical keys that no longer exist in the
     * configuration are still returned with a sensible fallback label.
     *
     * @param array<string,mixed>|null $requirementsData decoded JSON
     * @param list<array<string,mixed>> $fields current service_field config
     * @return list<array{key:string,label:string,type:string,value:mixed}>
     */
    public static function resolveRequirements(?array $requirementsData, array $fields): array
    {
        $byKey = [];
        foreach ($fields as $field) {
            $byKey[(string) $field['key']] = $field;
        }

        $map = is_array($requirementsData) ? $requirementsData : [];
        if ($map === []) {
            return [];
        }

        $out = [];
        foreach ($map as $key => $value) {
            $key = (string) $key;
            $field = $byKey[$key] ?? null;
            $label = $field !== null ? (string) $field['label'] : self::fallbackLabel($key);
            $type  = $field !== null ? (string) $field['type'] : 'text';

            $out[] = [
                'key'   => $key,
                'label' => $label,
                'type'  => $type,
                'value' => self::presentRequirementValue($type, $value),
            ];
        }

        return $out;
    }

    /**
     * Humanise an unknown requirement key for a fallback label.
     */
    private static function fallbackLabel(string $key): string
    {
        $words = preg_split('/[_\-\s]+/', $key) ?: [$key];
        return implode(' ', array_map(
            static fn(string $w): string => $w === '' ? $w : ucfirst($w),
            $words
        ));
    }

    /**
     * Normalise a stored requirement value for display.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function presentRequirementValue(string $type, $value)
    {
        if ($type === 'checkbox') {
            return is_array($value) ? $value : [$value];
        }

        if ($type === 'sizegrid') {
            if (!is_array($value)) {
                return [];
            }
            $rows = [];
            foreach ($value as $size => $qty) {
                if ((string) $qty === '' || (string) $qty == '0') {
                    continue;
                }
                $rows[] = ['size' => (string) $size, 'quantity' => (int) $qty];
            }
            return $rows;
        }

        if (is_array($value)) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function presentListRow(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'reference'   => (string) $row['request_reference'],
            'client'      => [
                'name'   => (string) $row['client_name'],
                'email'  => (string) $row['client_email'],
                'phone'  => $row['client_phone'] !== null ? (string) $row['client_phone'] : null,
                'company'=> $row['company_name'] !== null ? (string) $row['company_name'] : null,
            ],
            'service'     => [
                'id'     => (int) $row['service_item_id'],
                'name'   => (string) $row['service_name'],
                'slug'   => (string) $row['service_slug'],
                'category' => isset($row['category_name']) ? (string) $row['category_name'] : null,
            ],
            'status'          => (string) $row['status'],
            'attachments_count' => (int) $row['attachments_count'],
            'created_at'      => (string) $row['created_at'],
            'updated_at'      => (string) $row['updated_at'],
        ];
    }

    /**
     * Build the WHERE fragment + bound params for list search/filtering.
     *
     * @param array{status?:?string,service_id?:?int,service?:?string,search?:?string,from?:?string,to?:?string} $filters
     * @return array{0:string,1:list<mixed>}
     */
    private static function buildListWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['status'])) {
            $clauses[] = 'r.status = ?';
            $params[]  = (string) $filters['status'];
        }

        if (!empty($filters['service_id'])) {
            $clauses[] = 'r.service_item_id = ?';
            $params[]  = (int) $filters['service_id'];
        }

        if (!empty($filters['service'])) {
            $clauses[] = 'si.slug = ?';
            $params[]  = (string) $filters['service'];
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $clauses[] = '(r.request_reference LIKE ? OR r.client_name LIKE ? OR r.client_email LIKE ?
                           OR r.client_phone LIKE ? OR r.company_name LIKE ? OR si.name LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        if (!empty($filters['from'])) {
            $clauses[] = 'r.created_at >= ?';
            $params[]  = (string) $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $clauses[] = 'r.created_at <= ?';
            $params[]  = (string) $filters['to'] . ' 23:59:59';
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Generate a unique reference: GDS-REQ-YYYYMMDD-XXXX
     *
     * Uses a date prefix + 4-char hex suffix. Retries on collision (max 5).
     */
    private static function generateReference(\PDO $pdo): string
    {
        $date = date('Ymd');
        $prefix = 'GDS-REQ-' . $date . '-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $reference = $prefix . $suffix;

            $stmt = $pdo->prepare(
                'SELECT 1 FROM requests WHERE request_reference = ? LIMIT 1'
            );
            $stmt->execute([$reference]);
            if ($stmt->fetch() === false) {
                return $reference;
            }
        }

        throw new \RuntimeException('Unable to generate a unique request reference.');
    }
}