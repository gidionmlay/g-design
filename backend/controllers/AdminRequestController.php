<?php
/**
 * Admin request management endpoints.
 *
 *   GET    /api/v1/admin/requests                          -> paginated list (search/filter)
 *   GET    /api/v1/admin/requests/{id}                     -> full request details
 *   PATCH  /api/v1/admin/requests/{id}/status              -> controlled status update
 *   GET    /api/v1/admin/requests/{id}/attachments         -> attachment metadata list
 *   GET    /api/v1/admin/requests/{id}/attachments/{aid}   -> secure file stream/preview
 *
 * Every endpoint requires an authenticated administrator.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Middleware\AuthMiddleware;
use App\Models\RequestAttachmentModel;
use App\Models\RequestModel;
use App\Models\ServiceField;

final class AdminRequestController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $http = Request::fromGlobals();

        $page  = self::queryPositiveInt($http, 'page', 1, 'page');
        $limit = self::queryPositiveInt($http, 'limit', 20, 'limit', 100);

        $status = $http->query('status');
        if ($status !== null && !RequestModel::isValidStatus($status)) {
            Response::validationError(['status' => 'Invalid status filter.']);
        }

        $serviceId = $http->query('service_id');
        if ($serviceId !== null && !self::validId($serviceId)) {
            Response::validationError(['service_id' => 'Invalid service identifier.']);
        }

        $service = $http->query('service');
        if ($service !== null && $service !== '' && !Validator::slug($service)) {
            Response::validationError(['service' => 'Invalid service identifier.']);
        }

        $from = $http->query('from');
        if ($from !== null && $from !== '' && !self::validDate($from)) {
            Response::validationError(['from' => 'Invalid start date (expected YYYY-MM-DD).']);
        }

        $to = $http->query('to');
        if ($to !== null && $to !== '' && !self::validDate($to)) {
            Response::validationError(['to' => 'Invalid end date (expected YYYY-MM-DD).']);
        }

        Response::ok(RequestModel::paginated($page, $limit, [
            'status'     => $status,
            'service_id' => $serviceId !== null ? (int) $serviceId : null,
            'service'    => $service,
            'search'     => $http->query('search'),
            'from'       => $from,
            'to'         => $to,
        ]));
    }

    public function show(string $id): void
    {
        AuthMiddleware::handle();

        $id = self::requireId($id, 'request');

        $row = RequestModel::findForDetail($id);
        if ($row === null) {
            Response::notFound('Request not found.');
        }

        $fields = ServiceField::forItems([(int) $row['service_item_id']])[(int) $row['service_item_id']] ?? [];
        $requirements = RequestModel::resolveRequirements(
            self::decodeJson($row['requirements_data']),
            $fields
        );

        $items = array_map(
            [RequestAttachmentModel::class, 'present'],
            RequestAttachmentModel::findByRequestId($id)
        );

        Response::ok([
            'id'           => (int) $row['id'],
            'reference'    => (string) $row['request_reference'],
            'status'       => (string) $row['status'],
            'description'  => $row['description'] !== null ? (string) $row['description'] : null,
            'quantity'     => $row['quantity'] !== null ? (int) $row['quantity'] : null,
            'created_at'   => (string) $row['created_at'],
            'updated_at'   => (string) $row['updated_at'],
            'client'       => [
                'name'    => (string) $row['client_name'],
                'email'   => (string) $row['client_email'],
                'phone'   => $row['client_phone'] !== null ? (string) $row['client_phone'] : null,
                'company' => $row['company_name'] !== null ? (string) $row['company_name'] : null,
            ],
            'service'      => [
                'name'        => (string) $row['service_name'],
                'slug'        => (string) $row['service_slug'],
                'category'    => (string) $row['category_name'],
                'category_slug' => (string) $row['category_slug'],
                'description' => $row['service_description'] !== null ? (string) $row['service_description'] : null,
            ],
            'requirements' => $requirements,
            'attachments'  => $items,
        ]);
    }

    public function updateStatus(string $id): void
    {
        AuthMiddleware::handle();

        $id = self::requireId($id, 'request');
        $body = self::readJsonBody();

        $status = $body['status'] ?? null;
        if (!is_string($status) || !RequestModel::isValidStatus($status)) {
            Response::validationError(['status' => 'Invalid status value.']);
        }

        $current = RequestModel::findById($id);
        if ($current === null) {
            Response::notFound('Request not found.');
        }

        $from = (string) $current['status'];

        if ($status !== $from && !RequestModel::canTransition($from, $status)) {
            Response::error(
                'INVALID_TRANSITION',
                sprintf('Request status cannot move from "%s" to "%s".', $from, $status),
                400
            );
        }

        $updated = RequestModel::updateStatus($id, $status);

        Response::ok([
            'request' => [
                'id'         => (int) $updated['id'],
                'reference'  => (string) $updated['request_reference'],
                'status'     => (string) $updated['status'],
                'updated_at' => (string) $updated['updated_at'],
            ],
        ]);
    }

    public function attachments(string $id): void
    {
        AuthMiddleware::handle();

        $id = self::requireId($id, 'request');

        if (RequestModel::findById($id) === null) {
            Response::notFound('Request not found.');
        }

        $items = array_map(
            [RequestAttachmentModel::class, 'present'],
            RequestAttachmentModel::findByRequestId($id)
        );

        Response::ok(['attachments' => $items]);
    }

    public function streamAttachment(string $id, string $attachmentId): never
    {
        AuthMiddleware::handle();

        $id  = self::requireId($id, 'request');
        $aid = self::requireId($attachmentId, 'attachment');

        $attachment = RequestAttachmentModel::findForRequest($aid, $id);
        if ($attachment === null) {
            Response::notFound('Attachment not found.');
        }

        $download = Request::fromGlobals()->query('download') === '1';

        Response::streamFile(
            (string) $attachment['storage_path'],
            (string) $attachment['mime_type'],
            (string) $attachment['original_filename'],
            $download
        );
    }

    private static function queryPositiveInt(Request $http, string $key, int $default, string $field = 'page', int $max = PHP_INT_MAX): int
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

    private static function validId(?string $value): bool
    {
        return $value !== null && preg_match('/^\d{1,10}$/', $value) && (int) $value >= 1;
    }

    private static function requireId(string $value, string $label): int
    {
        if (!preg_match('/^\d{1,10}$/', $value) || (int) $value < 1) {
            Response::error('INVALID_ID', 'Invalid ' . $label . ' identifier.');
        }
        return (int) $value;
    }

    private static function validDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    /** @return array<string,mixed> */
    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            Response::error('EMPTY_BODY', 'Request body is empty.', 400);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::error('INVALID_JSON', 'Request body must be valid JSON.', 400);
        }
        return $data;
    }

    /** @return array<string,mixed>|null */
    private static function decodeJson(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}