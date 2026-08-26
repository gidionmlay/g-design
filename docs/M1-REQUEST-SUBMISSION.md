# M1 — Request Submission & Data Persistence

## Architecture

Client submits a quote request through the 4-step wizard frontend. The request is validated server-side against the service field configuration in MySQL, then persisted to the `requests` table with a unique reference.

```
Browser → POST /api/v1/requests → RequestController → RequestModel → MySQL
```

## Database Structure

### Table: `requests`

| Column | Type | Description |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `request_reference` | VARCHAR(30) UNIQUE | Human-readable ID (e.g. `GDS-REQ-20260826-A8F3`) |
| `service_item_id` | INT UNSIGNED FK | References `service_items.id` |
| `client_name` | VARCHAR(150) | Client full name |
| `client_email` | VARCHAR(255) | Client email address |
| `client_phone` | VARCHAR(50) NULL | Client phone number |
| `company_name` | VARCHAR(200) NULL | Company/business name |
| `description` | TEXT NULL | Project description/notes |
| `quantity` | INT UNSIGNED NULL | Numeric quantity (if applicable) |
| `requirements_data` | JSON NULL | Dynamic field values as JSON object |
| `status` | ENUM('pending') | Request status |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### JSON: `requirements_data`

Stores all dynamic service field values as key-value pairs where keys are field keys from `service_fields.field_key`.

```json
{
    "logo_name": "TechCorp",
    "package": "New Logo Design & Brand Package",
    "colour_style": "Modern, minimal, blue accent",
    "budget": "TZS 200,000/= to TZS 500,000/="
}
```

## API Endpoint

### POST /api/v1/requests

**Request:**
```json
{
    "service_slug": "logo-design",
    "client_name": "John Doe",
    "client_email": "john@example.com",
    "client_phone": "+255712345678",
    "description": "Project notes",
    "quantity": 1,
    "requirements_data": {
        "logo_name": "TechCorp",
        "package": "New Logo Design & Brand Package",
        "colour_style": "Blue",
        "business": "Software company",
        "budget": "TZS 200,000/= to TZS 500,000/="
    }
}
```

**Required fields:** `service_slug`, `client_name`, `client_email`, `requirements_data` (with all required dynamic fields)

**Success (201):**
```json
{
    "ok": true,
    "data": {
        "request_reference": "GDS-REQ-20260826-A8F3",
        "status": "pending",
        "message": "Your request has been submitted successfully."
    }
}
```

**Validation Error (400):**
```json
{
    "ok": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Please correct the highlighted fields.",
        "fields": {
            "client_email": "Please enter a valid email address."
        }
    }
}
```

**Service Not Found (404):**
```json
{
    "ok": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Service not found."
    }
}
```

## Validation Rules

### Top-level fields
- `service_slug`: required, valid slug format, must match an active service item
- `client_name`: required, min 2 characters
- `client_email`: required, valid email format
- `client_phone`: optional, valid phone format if provided
- `quantity`: optional, 1–100,000 if provided

### Dynamic requirements
- Fields are validated against `service_fields` + `service_field_options` for the selected service
- Required fields must be present
- Radio/select values must match allowed options exactly
- Checkbox values must all be from allowed options
- Unknown fields are rejected
- Text fields limited to 5,000 characters
- Number fields validated for range

## Request Reference

Format: `GDS-REQ-YYYYMMDD-XXXX`

- `YYYYMMDD`: submission date
- `XXXX`: 4-character uppercase hex random suffix
- Generated server-side, never accepted from frontend
- Unique constraint enforced at database level

## Files Created

- `database/migrations/2026-08-26-m1-requests-table.sql` — migration
- `backend/models/RequestModel.php` — data access layer
- `backend/controllers/RequestController.php` — request handling + validation

## Files Modified

- `backend/core/Response.php` — added `validationError()`, `created()`; updated CORS for POST
- `backend/models/ServiceField.php` — added `id` to presented fields
- `backend/routes/api.php` — added POST route
- `public/api.php` — updated docblock
- `public/assets/js/quote.js` — replaced console.log with API submission

## Known Limitations

- File uploads are not persisted in M1 (stripped from payload). M2 will handle attachments.
- `company_name` and `quantity` columns exist in the schema but are not currently populated by the frontend wizard.
- No rate limiting on the POST endpoint.
- Request status is always `pending` — no status management yet.
