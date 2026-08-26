# M1 — Test Report

## Test Environment

- MySQL 8.x on localhost
- PHP 8.x built-in server on port 8000
- Database: `gdesign` (seeded with 6 categories, 29 items, 131 fields)

---

## API Tests

### TEST 1: Successful request creation

| | |
|---|---|
| **Input** | `service_slug: "logo-design"`, valid client info, all required fields |
| **Expected** | HTTP 201, `ok: true`, `request_reference` returned |
| **Actual** | HTTP 201, `ok: true`, reference `GDS-REQ-20260826-903A` |
| **Result** | PASS |

### TEST 2: Missing service_slug

| | |
|---|---|
| **Input** | No `service_slug` field |
| **Expected** | HTTP 400, `VALIDATION_ERROR` |
| **Actual** | HTTP 400, `VALIDATION_ERROR`, `"service_slug": "Service is required."` |
| **Result** | PASS |

### TEST 3: Invalid/nonexistent service

| | |
|---|---|
| **Input** | `service_slug: "nonexistent-service"` |
| **Expected** | HTTP 404, `NOT_FOUND` |
| **Actual** | HTTP 404, `"Service not found."` |
| **Result** | PASS |

### TEST 4: Missing required dynamic fields

| | |
|---|---|
| **Input** | Valid service but empty `requirements_data: {}` |
| **Expected** | HTTP 400, errors for each missing required field |
| **Actual** | HTTP 400, errors for `logo_name`, `package`, `colour_style`, `business`, `budget` |
| **Result** | PASS |

### TEST 5: Invalid select/radio option

| | |
|---|---|
| **Input** | `package: "INVALID OPTION"` |
| **Expected** | HTTP 400, invalid option error |
| **Actual** | HTTP 400, `"package": "Please select a valid option."` |
| **Result** | PASS |

### TEST 6: Unknown dynamic field

| | |
|---|---|
| **Input** | Extra field `unknown_field: "value"` in requirements |
| **Expected** | HTTP 400, unknown field rejected |
| **Actual** | HTTP 400, `"unknown_field": "This field is not applicable to the selected service."` |
| **Result** | PASS |

### TEST 7: Invalid email

| | |
|---|---|
| **Input** | `client_email: "not-an-email"` |
| **Expected** | HTTP 400, email validation error |
| **Actual** | HTTP 400, `"client_email": "Please enter a valid email address."` |
| **Result** | PASS |

### TEST 8: Invalid quantity

| | |
|---|---|
| **Input** | `quantity: -5` |
| **Expected** | HTTP 400, quantity range error |
| **Actual** | HTTP 400, `"quantity": "Quantity must be between 1 and 100,000."` |
| **Result** | PASS |

### TEST 9: Empty client name

| | |
|---|---|
| **Input** | `client_name: ""` |
| **Expected** | HTTP 400, name required error |
| **Actual** | HTTP 400, `"client_name": "Your name is required."` |
| **Result** | PASS |

### TEST 10: Valid flyer request

| | |
|---|---|
| **Input** | `service_slug: "flyer-brochures"`, all required fields |
| **Expected** | HTTP 201, `ok: true` |
| **Actual** | HTTP 201, reference `GDS-REQ-20260826-FA74` |
| **Result** | PASS |

### TEST 11: Valid T-shirt request (with sizegrid)

| | |
|---|---|
| **Input** | `service_slug: "tshirt-cap"`, sizegrid `size_breakdown: {"M":"5","L":"10","XL":"3"}` |
| **Expected** | HTTP 201, `ok: true` |
| **Actual** | HTTP 201, reference `GDS-REQ-20260826-8627` |
| **Result** | PASS |

---

## Database Verification

| Check | Result |
|---|---|
| All 3 requests present in `requests` table | PASS |
| `request_reference` format matches `GDS-REQ-YYYYMMDD-XXXX` | PASS |
| `requirements_data` JSON decodes correctly | PASS |
| `service_item_id` references valid service item | PASS |
| `status` defaults to `pending` | PASS |
| `created_at` populated automatically | PASS |

---

## M0 Regression

| Endpoint | Result |
|---|---|
| `GET /api/v1/services` | PASS — returns 6 categories |
| `GET /api/v1/services/logo-design` | PASS — returns item with fields |

---

## Summary

| | |
|---|---|
| **Total tests** | 14 (11 API + 3 regression) |
| **Passed** | 14 |
| **Failed** | 0 |
| **Status** | ALL PASS |
