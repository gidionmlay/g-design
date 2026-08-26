# M0 — API Specification (v1)

Base URL (local dev): `http://127.0.0.1:8000/api/v1`

All responses are JSON (`Content-Type: application/json; charset=utf-8`).

## Envelope

Success:
```json
{ "ok": true, "data": { ... } }
```

Error:
```json
{ "ok": false, "error": { "code": "NOT_FOUND", "message": "Human readable message" } }
```

Error codes: `NOT_FOUND` (404), `METHOD_NOT_ALLOWED` (405, includes `Allow` header), `SERVER_ERROR` (500). `OPTIONS` on a known route → 204 with CORS headers when applicable.

## Endpoints

### GET /api/v1/services
Active catalog, ordered by category/item sort order.

```json
{
  "ok": true,
  "data": {
    "categories": [
      {
        "slug": "printing",
        "name": "Printing",
        "tag": "PRINTING",
        "description": "…",
        "image": "assets/images/service/03.webp",
        "items": [
          {
            "slug": "tshirt-cap",
            "name": "T-Shirt & Kofia Printing",
            "description": "DTF, embroidery and vinyl on apparel.",
            "image": null,
            "fields": [
              { "key": "size_breakdown", "label": "Size breakdown", "type": "sizegrid",
                "required": true, "sizes": ["S","M","L","XL","XXL"],
                "one_size_when": {"key":"garment","value":"Cap (Kofia)","label":"One Size"} },
              { "key": "budget", "type": "radio", "label": "Estimated budget", "required": true,
                "options": ["Below TZS 150,000/=", "…"] }
            ]
          }
        ]
      }
    ]
  }
}
```

Field object keys appear only when set: `placeholder`, `hint`, `options`, `sizes`, `show_when`, `one_size_when`. `key/label/type/required` are always present. Internal ids, timestamps, and `is_active` flags are intentionally not exposed.

`show_when` mirrors the wizard renderer contract: one of `{key, equals}` / `{key, notIn: []}` / `{key, value}`.

### GET /api/v1/services/{slug}
Slug resolution order:
1. **Category slug** (e.g. `/services/printing`) → `{ "ok":true, "data":{ "category": { …full category incl. items… } } }`
2. **Item slug** (e.g. `/services/tshirt-cap`) → `{ "ok":true, "data":{ "item": { …item + fields + parent category summary } } }`. Item slugs may repeat across categories; the first match in catalog order wins and its `category.slug/name` is included for disambiguation.
3. Unknown or inactive slug → 404 `NOT_FOUND`.

## CORS

Default deployment is same-origin (static site + API behind one host) — no CORS headers are emitted. Cross-origin clients must be listed in `.env`:

```
ALLOWED_ORIGINS=http://localhost:5500,https://staging.example
```

Matching origins receive `Access-Control-Allow-Origin: <origin>` (+ `Vary: Origin`, methods `GET, OPTIONS`). Wildcard `*` is deliberately unsupported.

## Routing notes

- Apache: `public/.htaccess` rewrites `/api/v1/**` → `public/api.php`.
- PHP built-in server: `php -S host:port -t public public/router.php`.
- Unsupported method on existing path → 405 with `Allow`.
