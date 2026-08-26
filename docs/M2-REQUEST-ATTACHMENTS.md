# M2 — Request Attachments & Secure File Uploads

## Architecture

Clients can now attach reference files (images, PDFs) when submitting a quote request. Files are validated server-side, stored with randomized filenames in a private directory, and linked to the request via database records.

```
Browser (FormData) → POST /api/v1/requests → RequestController
  → validate request fields
  → validate service + dynamic requirements
  → validate files (MIME, size, type)
  → BEGIN transaction
    → create request record
    → store each file securely
    → create attachment records
  → COMMIT (or ROLLBACK on failure)
  → return success with attachment metadata
```

## Storage Architecture

```
project/
├── public/          ← web-accessible
│   └── api.php
└── storage/         ← NOT web-accessible
    └── uploads/
        ├── req_4_a1b2c3d4.jpg
        ├── req_5_e5f6g7h8.png
        └── req_6_i9j0k1l2.pdf
```

Files are stored outside the public document root. They cannot be accessed via direct URL.

## Secure Filenames

Original: `my-logo-reference.png`
Stored: `req_4_8f31c9a2e4b7c0d5.png`

Format: `req_{requestId}_{16-random-hex}.{extension}`

- Random bytes generated via `random_bytes(16)`
- Original filename preserved in database only
- Path traversal in original filename has no effect on storage path

## Allowed File Types

| MIME Type | Extension |
|---|---|
| `image/jpeg` | jpg |
| `image/png` | png |
| `image/webp` | webp |
| `application/pdf` | pdf |

**Blocked explicitly:** PHP, HTML, JS, SVG, EXE, SH, BAT, SQL, and all executable/script types.

## File Size & Limits

- **Max file size:** 10 MB per file
- **Max files per request:** 5
- **Upload field:** optional (not required)

## Database Structure

### Table: `request_attachments`

| Column | Type | Description |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `request_id` | INT UNSIGNED FK | References `requests.id` ON DELETE CASCADE |
| `original_filename` | VARCHAR(255) | Client's original filename |
| `stored_filename` | VARCHAR(255) UNIQUE | Server-side randomized filename |
| `storage_path` | VARCHAR(500) | Absolute path on disk |
| `mime_type` | VARCHAR(127) | Detected MIME type (finfo) |
| `file_extension` | VARCHAR(20) | Normalized extension |
| `file_size` | INT UNSIGNED | Size in bytes |
| `created_at` | TIMESTAMP | Creation timestamp |

## API Behavior

### POST /api/v1/requests (multipart/form-data)

**Fields:**
- `service_slug` — required
- `client_name` — required
- `client_email` — required
- `client_phone` — optional
- `description` — optional
- `requirements_data` — JSON string of dynamic field values
- `files[]` — optional file uploads (multiple)

**Success (201):**
```json
{
    "ok": true,
    "data": {
        "request_reference": "GDS-REQ-20260826-A8F3",
        "status": "pending",
        "attachments": [
            {
                "original_filename": "logo-reference.jpg",
                "size": 245678,
                "mime_type": "image/jpeg"
            }
        ],
        "message": "Your request has been submitted successfully."
    }
}
```

**File validation error (400):**
```json
{
    "ok": false,
    "error": {
        "code": "FILE_VALIDATION_ERROR",
        "message": "One or more files could not be uploaded.",
        "files": [
            {
                "name": "malware.exe",
                "error": "File type \".exe\" is not allowed."
            }
        ]
    }
}
```

The endpoint also accepts `application/json` for backward compatibility (no file uploads).

## Security Measures

- **MIME validation:** Server-side via `finfo_open(FILEINFO_MIME_TYPE)`, not client-provided
- **Extension validation:** Blocked extensions list enforced before MIME check
- **Size validation:** 10 MB per file, checked before processing
- **Randomized filenames:** `random_bytes(16)` → hex string
- **Private storage:** `storage/uploads/` is outside `public/` document root
- **Path traversal protection:** Storage path constructed from requestId + random name, never from user input
- **No executable extensions:** PHP, HTML, JS, SVG, EXE, SH, BAT, SQL all blocked
- **Transaction safety:** Request + attachments + files are atomic — rollback removes DB records and stored files

## Transaction Rollback

If any file fails validation after the request record is created:
1. Database transaction rolls back (request record removed)
2. Previously stored files for that request are deleted from disk
3. No orphan files or orphan records are left

## Files Created

- `database/migrations/2026-08-26-m2-request-attachments.sql`
- `backend/models/RequestAttachmentModel.php`
- `backend/core/FileUpload.php`

## Files Modified

- `backend/controllers/RequestController.php` — multipart/form-data support, file handling, transaction rollback
- `backend/core/Response.php` — added `fileValidationError()` method
- `backend/models/RequestModel.php` — removed internal transaction (controller manages it), added `deleteById()`
- `public/assets/js/quote.js` — FormData submission, updated ACCEPT types, file error display

## Known Limitations

- No file preview or download endpoint (admin will need this later)
- No file deduplication (same file uploaded twice = two storage copies)
- finfo may not detect all disguised file types (e.g., EXE renamed to .jpg with minimal content)
- No virus scanning
