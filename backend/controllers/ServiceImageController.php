<?php
/**
 * Public service image serving.
 *
 *   GET /api/v1/service-images/{id}
 *
 * Images are stored outside the public root in storage/service-images/ and
 * referenced only by database id. The controller resolves the id to its stored
 * row, verifies the owning service AND category are active (so inactive
 * services stop serving publicly), and streams the file with safe headers.
 * No filesystem paths are ever exposed.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\ServiceImageUpload;
use App\Models\ServiceItem;

final class ServiceImageController
{
    public function show(string $id): never
    {
        if (!preg_match('/^\d{1,10}$/', $id) || (int) $id < 1) {
            Response::notFound('Image not found.');
        }

        $image = ServiceItem::imageById((int) $id);
        if ($image === null) {
            Response::notFound('Image not found.');
        }

        // Only serve images whose owning service + category are active.
        if ((int) $image['item_active'] !== 1 || (int) $image['category_active'] !== 1) {
            Response::notFound('Image not found.');
        }

        $path = ServiceImageUpload::resolvePath((string) $image['stored_filename']);
        if ($path === '') {
            Response::notFound('Image not found.');
        }

        Response::streamFile(
            $path,
            (string) $image['mime_type'],
            'service-image',
            false
        );
    }
}
