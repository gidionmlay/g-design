<?php
/**
 * Admin Service & Catalog CMS endpoints (M5).
 *
 *   Categories:
 *     GET    /api/v1/admin/service-categories            -> list (all, incl. inactive)
 *     POST   /api/v1/admin/service-categories            -> create
 *     GET    /api/v1/admin/service-categories/{id}       -> details
 *     PATCH  /api/v1/admin/service-categories/{id}       -> update
 *     PATCH  /api/v1/admin/service-categories/{id}/status-> activate/deactivate
 *
 *   Services:
 *     GET    /api/v1/admin/services                      -> list (search/filter/paginate)
 *     POST   /api/v1/admin/services                      -> create
 *     GET    /api/v1/admin/services/{id}                 -> details + fields
 *     PATCH  /api/v1/admin/services/{id}                 -> update
 *     PATCH  /api/v1/admin/services/{id}/status          -> activate/deactivate
 *
 *   Dynamic requirements (fields + options):
 *     POST   /api/v1/admin/services/{id}/fields          -> create field
 *     PATCH  /api/v1/admin/services/{id}/fields/{fid}    -> update field
 *     DELETE /api/v1/admin/services/{id}/fields/{fid}    -> remove field
 *     GET    /api/v1/admin/services/{id}/fields          -> list fields (with options)
 *     POST   /api/v1/admin/services/{id}/fields/{fid}/options  -> replace option set
 *
 *   Images:
 *     POST   /api/v1/admin/services/{id}/image           -> upload/replace service image
 *
 * Every endpoint requires an authenticated administrator.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\ServiceImageUpload;
use App\Core\Validator;
use App\Middleware\AuthMiddleware;
use App\Models\ServiceCategory;
use App\Models\ServiceFieldAdmin;
use App\Models\ServiceItem;

final class AdminCatalogController
{
    /* ================= Categories ================= */

    public function categories(): void
    {
        AuthMiddleware::handle();
        $items = array_map([self::class, 'presentCategory'], ServiceCategory::allForAdmin());
        Response::ok(['categories' => array_values($items)]);
    }

    public function createCategory(): void
    {
        AuthMiddleware::handle();
        $body = self::jsonBody();
        self::validateCategory($body);

        try {
            $cat = ServiceCategory::create([
                'slug'        => $body['slug'],
                'name'        => $body['name'],
                'tag'         => $body['tag'] ?? null,
                'description' => $body['description'] ?? null,
                'sort_order'  => $body['sort_order'] ?? 0,
                'is_active'   => $body['is_active'] ?? true,
            ]);
        } catch (\Throwable) {
            Response::error('DUPLICATE_SLUG', 'That category slug is already in use.', 400);
        }
        Response::created(['category' => self::presentCategory($cat)]);
    }

    public function showCategory(string $id): void
    {
        AuthMiddleware::handle();
        $cat = ServiceCategory::findById(self::id($id, 'category'));
        if ($cat === null) {
            Response::notFound('Category not found.');
        }
        Response::ok(['category' => self::presentCategory($cat)]);
    }

    public function updateCategory(string $id): void
    {
        AuthMiddleware::handle();
        $id  = self::id($id, 'category');
        $cat = ServiceCategory::findById($id);
        if ($cat === null) {
            Response::notFound('Category not found.');
        }

        $body = self::jsonBody();
        self::validateCategory($body, $id);

        try {
            $updated = ServiceCategory::update($id, [
                'slug'        => $body['slug'],
                'name'        => $body['name'],
                'tag'         => $body['tag'] ?? null,
                'description' => $body['description'] ?? null,
            ]);
        } catch (\Throwable) {
            Response::error('DUPLICATE_SLUG', 'That category slug is already in use.', 400);
        }
        Response::ok(['category' => self::presentCategory($updated)]);
    }

    public function categoryStatus(string $id): void
    {
        AuthMiddleware::handle();
        $id  = self::id($id, 'category');
        if (ServiceCategory::findById($id) === null) {
            Response::notFound('Category not found.');
        }
        $body = self::jsonBody();
        if (!isset($body['is_active']) || !is_bool($body['is_active'])) {
            Response::validationError(['is_active' => 'is_active must be a boolean.']);
        }
        ServiceCategory::setActive($id, $body['is_active']);
        Response::ok(['category' => self::presentCategory(ServiceCategory::findById($id))]);
    }

    public function categoryOrder(string $id): void
    {
        AuthMiddleware::handle();
        $id  = self::id($id, 'category');
        if (ServiceCategory::findById($id) === null) {
            Response::notFound('Category not found.');
        }
        $body = self::jsonBody();
        if (!isset($body['sort_order']) || !is_int($body['sort_order']) || $body['sort_order'] < 0) {
            Response::validationError(['sort_order' => 'sort_order must be a non-negative integer.']);
        }
        ServiceCategory::setSortOrder($id, $body['sort_order']);
        Response::ok(['category' => self::presentCategory(ServiceCategory::findById($id))]);
    }

    /* ================= Services ================= */

    public function services(): void
    {
        AuthMiddleware::handle();
        $http = Request::fromGlobals();

        $page  = self::positiveInt($http, 'page', 1, 'page');
        $limit = self::positiveInt($http, 'limit', 20, 'limit', 100);

        $categoryId = $http->query('category_id');
        if ($categoryId !== null && $categoryId !== '' && !self::isDigits($categoryId)) {
            Response::validationError(['category_id' => 'Invalid category identifier.']);
        }

        $status = $http->query('status');
        if ($status !== null && $status !== '' && !in_array($status, ['active', 'inactive'], true)) {
            Response::validationError(['status' => 'Invalid status filter.']);
        }

        $result = ServiceItem::paginatedForAdmin($page, $limit, [
            'search'      => $http->query('search'),
            'category_id' => $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null,
            'status'      => $status,
        ]);

        $result['items'] = array_map([self::class, 'presentService'], $result['items']);
        Response::ok($result);
    }

    public function createService(): void
    {
        AuthMiddleware::handle();
        $body = self::jsonBody();
        self::validateService($body);

        if (ServiceCategory::findById($body['category_id']) === null) {
            Response::error('INVALID_CATEGORY', 'The selected category does not exist.', 400);
        }
        if (ServiceItem::slugExistsInCategory($body['slug'], $body['category_id'])) {
            Response::validationError(['slug' => 'A service with this slug already exists in that category.']);
        }

        try {
            $item = ServiceItem::create(self::serviceFields($body));
        } catch (\Throwable) {
            Response::error('INVALID_PRICING', 'The provided pricing values are invalid.', 400);
        }
        Response::created(['service' => self::presentService($item)]);
    }

    public function showService(string $id): void
    {
        AuthMiddleware::handle();
        $id   = self::id($id, 'service');
        $item = ServiceItem::findById($id);
        if ($item === null) {
            Response::notFound('Service not found.');
        }
        $fields = ServiceFieldAdmin::forItem($id);
        $image  = ServiceItem::image($id);
        Response::ok([
            'service' => self::presentService($item),
            'image'   => self::presentImage($image),
            'fields'  => $fields,
        ]);
    }

    public function updateService(string $id): void
    {
        AuthMiddleware::handle();
        $id   = self::id($id, 'service');
        $item = ServiceItem::findById($id);
        if ($item === null) {
            Response::notFound('Service not found.');
        }

        $body = self::jsonBody();
        self::validateService($body, $id);

        if (ServiceCategory::findById($body['category_id']) === null) {
            Response::error('INVALID_CATEGORY', 'The selected category does not exist.', 400);
        }
        if (ServiceItem::slugExistsInCategory($body['slug'], $body['category_id'], $id)) {
            Response::validationError(['slug' => 'A service with this slug already exists in that category.']);
        }

        try {
            $updated = ServiceItem::update($id, self::serviceFields($body));
        } catch (\Throwable) {
            Response::error('INVALID_PRICING', 'The provided pricing values are invalid.', 400);
        }
        Response::ok(['service' => self::presentService($updated)]);
    }

    public function serviceStatus(string $id): void
    {
        AuthMiddleware::handle();
        $id   = self::id($id, 'service');
        $item = ServiceItem::findById($id);
        if ($item === null) {
            Response::notFound('Service not found.');
        }
        $body = self::jsonBody();
        if (!isset($body['is_active']) || !is_bool($body['is_active'])) {
            Response::validationError(['is_active' => 'is_active must be a boolean.']);
        }
        ServiceItem::setActive($id, $body['is_active']);
        Response::ok(['service' => self::presentService(ServiceItem::findById($id))]);
    }

    public function serviceOrder(string $id): void
    {
        AuthMiddleware::handle();
        $id   = self::id($id, 'service');
        $item = ServiceItem::findById($id);
        if ($item === null) {
            Response::notFound('Service not found.');
        }
        $body = self::jsonBody();
        if (!isset($body['sort_order']) || !is_int($body['sort_order']) || $body['sort_order'] < 0) {
            Response::validationError(['sort_order' => 'sort_order must be a non-negative integer.']);
        }
        ServiceItem::setSortOrder($id, $body['sort_order']);
        Response::ok(['service' => self::presentService(ServiceItem::findById($id))]);
    }

    /* ================= Dynamic fields ================= */

    public function fields(string $serviceId): void
    {
        AuthMiddleware::handle();
        $id = self::id($serviceId, 'service');
        self::requireService($id);
        Response::ok(['fields' => ServiceFieldAdmin::forItem($id)]);
    }

    public function createField(string $serviceId): void
    {
        AuthMiddleware::handle();
        $id   = self::id($serviceId, 'service');
        self::requireService($id);

        $body = self::jsonBody();
        self::validateField($body, $id);

        $fieldId = ServiceFieldAdmin::create($id, [
            'field_key'   => $body['field_key'],
            'label'       => $body['label'],
            'type'        => $body['type'],
            'required'    => $body['required'] ?? false,
            'placeholder' => $body['placeholder'] ?? null,
            'hint'        => $body['hint'] ?? null,
            'sort_order'  => $body['sort_order'] ?? 0,
        ]);

        // Persist options for select/radio/checkbox.
        $values = $body['options'] ?? null;
        if ($values !== null) {
            ServiceFieldAdmin::setOptions($fieldId, $values);
        }

        Response::created(['field' => self::findField($fieldId, $id)]);
    }

    public function updateField(string $serviceId, string $fieldId): void
    {
        AuthMiddleware::handle();
        $sid = self::id($serviceId, 'service');
        self::requireService($sid);
        $fid = self::id($fieldId, 'field');

        $field = ServiceFieldAdmin::findById($fid, $sid);
        if ($field === null) {
            Response::notFound('Field not found for this service.');
        }

        $body = self::jsonBody();
        self::validateField($body, $sid, $fid);

        ServiceFieldAdmin::update($fid, [
            'label'       => $body['label'],
            'type'        => $body['type'],
            'required'    => $body['required'] ?? false,
            'placeholder' => $body['placeholder'] ?? null,
            'hint'        => $body['hint'] ?? null,
            'sort_order'  => $body['sort_order'] ?? 0,
        ]);

        $values = $body['options'] ?? null;
        if ($values !== null) {
            ServiceFieldAdmin::setOptions($fid, $values);
        }

        Response::ok(['field' => self::findField($fid, $sid)]);
    }

    public function deleteField(string $serviceId, string $fieldId): void
    {
        AuthMiddleware::handle();
        $sid = self::id($serviceId, 'service');
        self::requireService($sid);
        $fid = self::id($fieldId, 'field');

        if (ServiceFieldAdmin::findById($fid, $sid) === null) {
            Response::notFound('Field not found for this service.');
        }
        ServiceFieldAdmin::delete($fid);
        Response::ok(['message' => 'Field removed. Historical request data is preserved.']);
    }

    public function options(string $serviceId, string $fieldId): void
    {
        AuthMiddleware::handle();
        $sid = self::id($serviceId, 'service');
        $fid = self::id($fieldId, 'field');
        self::requireService($sid);
        if (ServiceFieldAdmin::findById($fid, $sid) === null) {
            Response::notFound('Field not found for this service.');
        }
        $body = self::jsonBody();
        $values = $body['options'] ?? null;
        if (!is_array($values)) {
            Response::validationError(['options' => 'options must be a list of strings.']);
        }
        foreach ($values as $v) {
            if (!is_string($v) || $v === '') {
                Response::validationError(['options' => 'options must be non-empty strings.']);
            }
        }
        ServiceFieldAdmin::setOptions($fid, array_values($values));
        Response::ok(['field' => self::findField($fid, $sid)]);
    }

    /* ================= Images ================= */

    public function uploadImage(string $serviceId): void
    {
        AuthMiddleware::handle();
        $id = self::id($serviceId, 'service');
        self::requireService($id);

        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            Response::error('NO_IMAGE', 'Provide an image file as field "image".', 400);
        }

        try {
            $meta = ServiceImageUpload::process($file);
        } catch (\RuntimeException $e) {
            Response::error('INVALID_IMAGE', $e->getMessage(), 400);
        }

        // Remove previous managed image (old file + row) before tying the new one.
        $old = ServiceItem::image($id);
        if ($old !== null) {
            ServiceImageUpload::remove((string) $old['stored_filename']);
        }

        try {
            $newId = ServiceItem::replaceImage($id, $meta);
        } catch (\Throwable) {
            ServiceImageUpload::remove($meta['stored_filename']);
            Response::error('SERVER_ERROR', 'Failed to save the image.', 500);
        }

        Response::created([
            'image' => [
                'id'        => $newId,
                'url'       => '/api/v1/service-images/' . $newId,
                'mime_type' => $meta['mime_type'],
                'file_size' => $meta['file_size'],
            ],
        ]);
    }

    /* ================= Helpers ================= */

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function presentCategory(array $row): array
    {
        return [
            'id'            => (int) $row['id'],
            'slug'          => (string) $row['slug'],
            'name'          => (string) $row['name'],
            'tag'           => $row['tag'] !== null ? (string) $row['tag'] : null,
            'description'   => $row['description'] !== null ? (string) $row['description'] : null,
            'sort_order'    => (int) $row['sort_order'],
            'is_active'     => (int) $row['is_active'] === 1,
            'service_count' => isset($row['service_count']) ? (int) $row['service_count'] : null,
        ];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function presentService(array $row): array
    {
        return [
            'id'           => (int) $row['id'],
            'category_id'  => (int) $row['category_id'],
            'category'     => isset($row['category_name']) ? (string) $row['category_name'] : null,
            'category_slug'=> isset($row['category_slug']) ? (string) $row['category_slug'] : null,
            'slug'         => (string) $row['slug'],
            'name'         => (string) $row['name'],
            'short_description' => $row['short_description'] !== null ? (string) $row['short_description'] : null,
            'description'  => $row['description'] !== null ? (string) $row['description'] : null,
            'sort_order'   => (int) $row['sort_order'],
            'is_active'    => (int) $row['is_active'] === 1,
            'pricing'      => ServiceItem::presentPricing($row),
            'image'        => ServiceItem::image((int) $row['id']),
        ];
    }

    /** @param array<string,mixed>|null $image @return array<string,mixed>|null */
    private static function presentImage(?array $image): ?array
    {
        if ($image === null) {
            return null;
        }
        return [
            'id'        => (int) $image['id'],
            'url'       => '/api/v1/service-images/' . (int) $image['id'],
            'mime_type' => (string) $image['mime_type'],
            'file_size' => (int) $image['file_size'],
        ];
    }

    /** @param array<string,mixed> $body @return array<string,mixed> */
    private static function serviceFields(array $body): array
    {
        return [
            'category_id'      => $body['category_id'],
            'slug'             => $body['slug'],
            'name'             => $body['name'],
            'short_description'=> $body['short_description'] ?? null,
            'description'      => $body['description'] ?? null,
            'pricing_type'     => $body['pricing']['type'] ?? 'quote',
            'price'            => $body['pricing']['value'] ?? ($body['price'] ?? null),
            'min_price'        => $body['pricing']['min'] ?? null,
            'max_price'        => $body['pricing']['max'] ?? null,
            'currency'         => $body['pricing']['currency'] ?? 'TZS',
            'sort_order'       => $body['sort_order'] ?? 0,
            'is_active'        => $body['is_active'] ?? true,
        ];
    }

    /** @param array<string,mixed> $body */
    private static function validateCategory(array $body, int $exceptId = 0): void
    {
        $errs = [];

        if (!isset($body['name']) || !is_string($body['name']) || trim($body['name']) === '') {
            $errs['name'] = 'Name is required.';
        } elseif (mb_strlen($body['name']) > 150) {
            $errs['name'] = 'Name must be 150 characters or fewer.';
        }

        if (!isset($body['slug']) || !is_string($body['slug']) || !Validator::slug($body['slug'])) {
            $errs['slug'] = 'Slug must be lowercase letters, numbers and hyphens.';
        } elseif (ServiceCategory::slugExists($body['slug'], $exceptId)) {
            $errs['slug'] = 'That slug is already in use.';
        }

        if (isset($body['tag']) && $body['tag'] !== null && mb_strlen((string) $body['tag']) > 50) {
            $errs['tag'] = 'Tag must be 50 characters or fewer.';
        }

        if (isset($body['sort_order']) && (!is_int($body['sort_order']) || $body['sort_order'] < 0)) {
            $errs['sort_order'] = 'sort_order must be a non-negative integer.';
        }
        if (isset($body['is_active']) && !is_bool($body['is_active'])) {
            $errs['is_active'] = 'is_active must be a boolean.';
        }

        if ($errs !== []) {
            Response::validationError($errs);
        }
    }

    /** @param array<string,mixed> $body */
    private static function validateService(array $body, int $exceptId = 0): void
    {
        $errs = [];

        if (!isset($body['category_id']) || !is_int($body['category_id']) || $body['category_id'] < 1) {
            $errs['category_id'] = 'Category is required.';
        }

        if (!isset($body['name']) || !is_string($body['name']) || trim($body['name']) === '') {
            $errs['name'] = 'Service name is required.';
        } elseif (mb_strlen($body['name']) > 150) {
            $errs['name'] = 'Service name must be 150 characters or fewer.';
        }

        if (!isset($body['slug']) || !is_string($body['slug']) || !Validator::slug($body['slug'])) {
            $errs['slug'] = 'Slug must be lowercase letters, numbers and hyphens.';
        } else {
            $catId = isset($body['category_id']) && is_int($body['category_id']) ? $body['category_id'] : 0;
            if ($catId > 0 && ServiceItem::slugExistsInCategory($body['slug'], $catId, $exceptId)) {
                $errs['slug'] = 'A service with this slug already exists in that category.';
            }
        }

        $pricing = $body['pricing'] ?? [];
        if (!is_array($pricing)) {
            $errs['pricing'] = 'pricing must be an object.';
            $pricing = [];
        }
        $type = $pricing['type'] ?? 'quote';
        if (!in_array($type, ServiceItem::PRICING_TYPES, true)) {
            $errs['pricing.type'] = 'Invalid pricing type.';
        } else {
            self::validatePricing($type, $pricing, $errs);
        }

        if (isset($body['sort_order']) && (!is_int($body['sort_order']) || $body['sort_order'] < 0)) {
            $errs['sort_order'] = 'sort_order must be a non-negative integer.';
        }
        if (isset($body['is_active']) && !is_bool($body['is_active'])) {
            $errs['is_active'] = 'is_active must be a boolean.';
        }

        if ($errs !== []) {
            Response::validationError($errs);
        }
    }

    /** @param array<string,mixed> $pricing @param array<string,string> $errs */
    private static function validatePricing(string $type, array $pricing, array &$errs): void
    {
        $valid = static function ($v): bool {
            return is_int($v) || is_float($v) || (is_string($v) && is_numeric($v));
        };

        $currency = $pricing['currency'] ?? 'TZS';
        if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/', $currency)) {
            $errs['pricing.currency'] = 'Currency must be a 3-letter code (e.g. TZS).';
        }

        if ($type === 'fixed' || $type === 'starting_from') {
            $value = $pricing['value'] ?? null;
            if (!$valid($value) || (float) $value < 0) {
                $errs['pricing.value'] = 'Price must be a non-negative number for this pricing type.';
            }
        } elseif ($type === 'range') {
            $min = $pricing['min'] ?? null;
            $max = $pricing['max'] ?? null;
            if (!$valid($min) || (float) $min < 0) {
                $errs['pricing.min'] = 'Minimum price must be a non-negative number.';
            }
            if (!$valid($max) || (float) $max < 0) {
                $errs['pricing.max'] = 'Maximum price must be a non-negative number.';
            }
            if ($valid($min) && $valid($max) && (float) $min > (float) $max) {
                $errs['pricing.max'] = 'Maximum price must be greater than or equal to minimum.';
            }
        }
    }

    /** @param array<string,mixed> $body */
    private static function validateField(array $body, int $itemId, int $exceptFieldId = 0): void
    {
        $errs = [];

        if (!isset($body['label']) || !is_string($body['label']) || trim($body['label']) === '') {
            $errs['label'] = 'Label is required.';
        } elseif (mb_strlen($body['label']) > 190) {
            $errs['label'] = 'Label must be 190 characters or fewer.';
        }

        if (!isset($body['type']) || !in_array($body['type'], ServiceFieldAdmin::TYPES, true)) {
            $errs['type'] = 'Invalid field type.';
        }
        if (isset($body['required']) && !is_bool($body['required'])) {
            $errs['required'] = 'required must be a boolean.';
        }

        // field_key is only validated on create (immutable afterwards).
        if ($exceptFieldId === 0) {
            if (!isset($body['field_key']) || !is_string($body['field_key'])
                || !preg_match('/^[a-z][a-z0-9_]*$/', $body['field_key'])
                || strlen($body['field_key']) > 100) {
                $errs['field_key'] = 'Field key must be lowercase letters, digits and underscores, starting with a letter.';
            } elseif (ServiceFieldAdmin::keyExists($itemId, $body['field_key'])) {
                $errs['field_key'] = 'This field key is already in use for this service.';
            }
        }

        $options = $body['options'] ?? null;
        if ($options !== null && !is_array($options)) {
            $errs['options'] = 'options must be a list of strings.';
        }

        if ($errs !== []) {
            Response::validationError($errs);
        }
    }

    /**
     * @param int $fieldId @param int $itemId
     * @return array<string,mixed>
     */
    private static function findField(int $fieldId, int $itemId): array
    {
        foreach (ServiceFieldAdmin::forItem($itemId) as $field) {
            if ((int) $field['id'] === $fieldId) {
                return $field;
            }
        }
        Response::notFound('Field not found.');
    }

    private static function requireService(int $id): void
    {
        if (ServiceItem::findById($id) === null) {
            Response::notFound('Service not found.');
        }
    }

    private static function id(string $value, string $label): int
    {
        if (!self::isDigits($value) || (int) $value < 1) {
            Response::error('INVALID_ID', 'Invalid ' . $label . ' identifier.');
        }
        return (int) $value;
    }

    private static function isDigits(string $value): bool
    {
        return (bool) preg_match('/^\d{1,10}$/', $value);
    }

    private static function positiveInt(Request $http, string $key, int $default, string $field = 'page', int $max = PHP_INT_MAX): int
    {
        $raw = $http->query($key);
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (!preg_match('/^\d{1,10}$/', $raw) || (int) $raw < 1) {
            Response::validationError([$field => 'Invalid ' . $field . ' value.']);
        }
        return min((int) $raw, $max);
    }

    /** @return array<string,mixed> */
    private static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '' || trim($raw) === '') {
            Response::error('EMPTY_BODY', 'Request body is empty.', 400);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::error('INVALID_JSON', 'Request body must be valid JSON.', 400);
        }
        return $data;
    }
}
