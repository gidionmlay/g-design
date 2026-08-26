<?php
/**
 * Request submission endpoint.
 *
 *   POST /api/v1/requests  -> create a request with optional file attachments
 *
 * Accepts multipart/form-data (preferred) or application/json.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\FileUpload;
use App\Core\Response;
use App\Core\Validator;
use App\Models\RequestAttachmentModel;
use App\Models\RequestModel;
use App\Models\ServiceField;
use App\Models\ServiceFieldOption;
use App\Models\ServiceItem;

final class RequestController
{
    public function create(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'multipart/form-data')) {
            $body = self::readFormData();
            $files = self::readFiles();
        } elseif (str_contains($contentType, 'application/json')) {
            $body = self::readJsonBody();
            $files = [];
        } else {
            Response::error('INVALID_CONTENT_TYPE', 'Content-Type must be multipart/form-data or application/json.', 415);
        }

        $errors = self::validate($body);
        if ($errors !== []) {
            Response::validationError($errors);
        }

        $item = ServiceItem::findActiveBySlug($body['service_slug']);
        if ($item === []) {
            Response::notFound('Service not found.');
        }
        $serviceItem = $item[0];

        $requirements = $body['requirements_data'] ?? [];
        $reqErrors = self::validateRequirements((int) $serviceItem['id'], $requirements);
        if ($reqErrors !== []) {
            Response::validationError($reqErrors);
        }

        $fileErrors = self::validateFiles($files);
        if ($fileErrors !== []) {
            Response::fileValidationError($fileErrors);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $storedFiles = [];

        try {
            $data = [
                'service_item_id'   => (int) $serviceItem['id'],
                'client_name'       => trim($body['client_name']),
                'client_email'      => strtolower(trim($body['client_email'])),
                'client_phone'      => isset($body['client_phone']) ? trim($body['client_phone']) : null,
                'company_name'      => isset($body['company_name']) ? trim($body['company_name']) : null,
                'description'       => isset($body['description']) ? trim($body['description']) : null,
                'quantity'          => isset($body['quantity']) ? (int) $body['quantity'] : null,
                'requirements_data' => $requirements !== [] ? $requirements : null,
                'status'            => 'pending',
            ];

            $created = RequestModel::create($data);
            $requestId = (int) $created['id'];

            foreach ($files as $file) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    $meta = FileUpload::process($file, $requestId);
                } catch (\RuntimeException $e) {
                    $pdo->rollBack();
                    RequestModel::deleteById($requestId);
                    foreach ($storedFiles as $m) {
                        FileUpload::remove($m['storage_path']);
                    }
                    Response::fileValidationError([[
                        'name'  => $file['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]]);
                }
                RequestAttachmentModel::create(array_merge($meta, ['request_id' => $requestId]));
                $storedFiles[] = $meta;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            foreach ($storedFiles as $meta) {
                FileUpload::remove($meta['storage_path']);
            }
            error_log('[GDESIGN] Request creation failed: ' . $e->getMessage());
            Response::error('SERVER_ERROR', 'Failed to submit your request. Please try again.', 500);
        }

        $attachments = array_map(static fn(array $m): array => [
            'original_filename' => $m['original_filename'],
            'size'              => $m['file_size'],
            'mime_type'         => $m['mime_type'],
        ], $storedFiles);

        Response::created([
            'request_reference' => $created['request_reference'],
            'status'            => $created['status'],
            'attachments'       => $attachments,
            'message'           => 'Your request has been submitted successfully.',
        ]);
    }

    /**
     * Read form data from $_POST, parsing JSON fields.
     *
     * @return array<string,mixed>
     */
    private static function readFormData(): array
    {
        $body = [];
        foreach ($_POST as $key => $value) {
            $body[$key] = $value;
        }

        if (isset($body['requirements_data']) && is_string($body['requirements_data'])) {
            $decoded = json_decode($body['requirements_data'], true);
            $body['requirements_data'] = is_array($decoded) ? $decoded : [];
        }

        return $body;
    }

    /**
     * Read uploaded files from $_FILES, normalizing single/multiple uploads.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function readFiles(): array
    {
        if (!isset($_FILES['files']) || !is_array($_FILES['files'])) {
            return [];
        }

        $raw = $_FILES['files'];
        $count = is_array($raw['name']) ? count($raw['name']) : 1;
        $files = [];

        for ($i = 0; $i < $count; $i++) {
            $files[] = [
                'name'     => $raw['name'][$i]     ?? '',
                'type'     => $raw['type'][$i]     ?? '',
                'tmp_name' => $raw['tmp_name'][$i] ?? '',
                'error'    => $raw['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $raw['size'][$i]     ?? 0,
            ];
        }

        return $files;
    }

    /**
     * Parse and return the JSON request body.
     *
     * @return array<string,mixed>
     */
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

    /**
     * Validate top-level required fields.
     *
     * @param array<string,mixed> $body
     * @return array<string,string> field => message
     */
    private static function validate(array $body): array
    {
        $errors = [];

        if (empty($body['service_slug']) || !is_string($body['service_slug'])) {
            $errors['service_slug'] = 'Service is required.';
        } elseif (!Validator::slug($body['service_slug'])) {
            $errors['service_slug'] = 'Invalid service identifier.';
        }

        if (empty($body['client_name']) || !is_string($body['client_name'])) {
            $errors['client_name'] = 'Your name is required.';
        } elseif (mb_strlen(trim($body['client_name'])) < 2) {
            $errors['client_name'] = 'Name must be at least 2 characters.';
        }

        if (empty($body['client_email']) || !is_string($body['client_email'])) {
            $errors['client_email'] = 'Email address is required.';
        } elseif (!filter_var($body['client_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Please enter a valid email address.';
        }

        if (isset($body['client_phone']) && is_string($body['client_phone']) && $body['client_phone'] !== '') {
            $phone = preg_replace('/[\s\-\(\)]/', '', $body['client_phone']);
            if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
                $errors['client_phone'] = 'Please enter a valid phone number.';
            }
        }

        if (isset($body['quantity']) && $body['quantity'] !== null) {
            $qty = $body['quantity'];
            if (!is_numeric($qty) || (int) $qty < 1 || (int) $qty > 100000) {
                $errors['quantity'] = 'Quantity must be between 1 and 100,000.';
            }
        }

        if (!isset($body['requirements_data'])) {
            $body['requirements_data'] = [];
        } elseif (!is_array($body['requirements_data'])) {
            $errors['requirements_data'] = 'Requirements must be an object.';
        }

        return $errors;
    }

    /**
     * Validate submitted dynamic requirements against the service field config.
     *
     * @param array<string,mixed> $submitted
     * @return array<string,string> field => message
     */
    private static function validateRequirements(int $itemId, array $submitted): array
    {
        $errors = [];
        $fields = ServiceField::forItems([$itemId])[$itemId] ?? [];

        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[$field['key']] = $field;
        }

        $optionGroups = ServiceFieldOption::forFields(
            array_map(fn(array $f): int => (int) $f['id'] ?? 0, $fields)
        );

        foreach ($submitted as $key => $value) {
            if (!isset($fieldMap[$key])) {
                $errors[$key] = 'This field is not applicable to the selected service.';
                continue;
            }

            $field = $fieldMap[$key];
            $fieldErrors = self::validateSingleField($field, $value, $optionGroups[(int) $field['id']] ?? []);
            $errors = array_merge($errors, $fieldErrors);
        }

        foreach ($fieldMap as $key => $field) {
            if ($field['required'] && !array_key_exists($key, $submitted)) {
                $errors[$key] = 'This field is required.';
            }
        }

        return $errors;
    }

    /**
     * Validate a single submitted field value.
     *
     * @param array<string,mixed> $field
     * @param mixed $value
     * @param array<int,string> $allowedOptions
     * @return array<string,string>
     */
    private static function validateSingleField(array $field, $value, array $allowedOptions): array
    {
        $errors = [];
        $key = $field['key'];
        $type = $field['type'];

        if ($value === null || $value === '') {
            if ($field['required']) {
                $errors[$key] = 'This field is required.';
            }
            return $errors;
        }

        switch ($type) {
            case 'radio':
            case 'select':
                if (!in_array($value, $allowedOptions, true)) {
                    $errors[$key] = 'Please select a valid option.';
                }
                break;

            case 'checkbox':
                if (!is_array($value)) {
                    $errors[$key] = 'This field must be an array of selections.';
                    break;
                }
                foreach ($value as $item) {
                    if (!in_array($item, $allowedOptions, true)) {
                        $errors[$key] = 'One or more selections are invalid.';
                        break;
                    }
                }
                break;

            case 'text':
            case 'textarea':
                if (!is_string($value)) {
                    $errors[$key] = 'This field must be text.';
                } elseif (mb_strlen($value) > 5000) {
                    $errors[$key] = 'Text is too long (max 5,000 characters).';
                }
                break;

            case 'email':
                if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$key] = 'Please enter a valid email address.';
                }
                break;

            case 'tel':
                if (is_string($value) && $value !== '') {
                    $phone = preg_replace('/[\s\-\(\)]/', '', $value);
                    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
                        $errors[$key] = 'Please enter a valid phone number.';
                    }
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $errors[$key] = 'This field must be a number.';
                } elseif ((int) $value < 0 || (int) $value > 100000) {
                    $errors[$key] = 'Number must be between 0 and 100,000.';
                }
                break;

            case 'date':
                if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $errors[$key] = 'Please enter a valid date (YYYY-MM-DD).';
                }
                break;

            case 'sizegrid':
                if (!is_array($value)) {
                    $errors[$key] = 'Size breakdown must be an object.';
                    break;
                }
                foreach ($value as $sizeLabel => $qty) {
                    if (!is_string($qty) && !is_numeric($qty)) {
                        $errors[$key] = 'Invalid quantity for size: ' . $sizeLabel;
                    } elseif ((int) $qty < 0) {
                        $errors[$key] = 'Quantity cannot be negative for size: ' . $sizeLabel;
                    }
                }
                break;

            case 'upload':
                break;
        }

        return $errors;
    }

    /**
     * Pre-validate uploaded files before transaction.
     *
     * @param array<int,array<string,mixed>> $files
     * @return array<int,array{name:string,error:string}>
     */
    private static function validateFiles(array $files): array
    {
        $errors = [];
        $validCount = 0;

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $validCount++;

            if ($validCount > FileUpload::maxFiles()) {
                $errors[] = [
                    'name'  => $file['name'] ?? 'unknown',
                    'error' => 'Too many files. Maximum is ' . FileUpload::maxFiles() . '.',
                ];
                continue;
            }

            if ((int) $file['size'] > FileUpload::maxFileSize()) {
                $errors[] = [
                    'name'  => $file['name'],
                    'error' => 'File exceeds the 10 MB size limit.',
                ];
            }
        }

        return $errors;
    }
}
