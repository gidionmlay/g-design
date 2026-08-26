# M0 — Database Design

Engine: MariaDB/MySQL ≥ 10.4, InnoDB, `utf8mb4_unicode_ci`.

## Entity relationships

```
service_categories (1)
   └─< service_items (N)            FK ON DELETE CASCADE
          └─< service_fields (N)         FK ON DELETE CASCADE
                 ├─< service_field_options   FK ON DELETE CASCADE
                 └─< service_field_sizes     FK ON DELETE CASCADE
```

## Tables

### service_categories
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AI | |
| slug | VARCHAR(100) UNIQUE | stable public identifier (`branding`) |
| name | VARCHAR(150) | |
| tag | VARCHAR(50) NULL | uppercase label shown on cards |
| description | TEXT NULL | card text (`desc` in JS config) |
| image_path | VARCHAR(255) NULL | root-relative asset path |
| sort_order | SMALLINT UNSIGNED | display order |
| is_active | TINYINT(1) default 1 | API returns active rows only |
| created_at / updated_at | TIMESTAMP | auto |

Indexes: `uq_categories_slug`, `idx_categories_active_sort(is_active,sort_order)`.

### service_items
As categories plus `category_id` (FK → service_categories CASCADE).
Unique: `(category_id, slug)` — item slugs may repeat across categories (e.g. `brand-strategy`). Separate non-unique index on `slug` supports the detail lookup.

### service_fields
`item_id` FK CASCADE; `field_key` VARCHAR(100); unique `(item_id, field_key)`.
- `type` ENUM: radio, checkbox, text, email, tel, number, date, textarea, select, sizegrid, upload
- `required` TINYINT(1)
- `placeholder`, `hint` VARCHAR NULL
- `show_when_json` LONGTEXT NULL — conditional visibility rule, JSON-validated via CHECK:
  `{"key":"…","equals":…}` or `{"key":"…","not_in":[…]}` or `{"key":"…","value":…}`
- `one_size_when_json` LONGTEXT NULL — sizegrid single-size override:
  `{"key":"garment","value":"Cap (Kofia)","label":"One Size"}`

Current data uses `one_size_when_json` once (T-Shirt & Kofia); no field ships a `show_when` yet — the column exists because the wizard's renderer fully supports it and future CMS entries will use it.

### service_field_options
`field_id` FK CASCADE; `option_value` VARCHAR(255); unique `(field_id, option_value)`; ordered by `sort_order`. Populated for radio/checkbox fields.

### service_field_sizes
`field_id` FK CASCADE; `size_label` VARCHAR(50); unique `(field_id, size_label)`. Populated only for `sizegrid` fields (currently 2 fields: S–XXL garment grid, A5–A1 photo sizes).

## Seeded content (from original QUOTE_CONFIG)

6 categories · 26 items · 116 fields · 163 options · 10 size labels.

Type distribution: radio 53, textarea 31, text 20, number 15, checkbox 5, select 5, sizegrid 2.
(email/tel/date/upload exist only in the wizard's hardcoded common-contact section, which stays client-side.)

## Import & seeding

```bash
mysql -h127.0.0.1 -u gdesign -p gdesign < database/schema.sql
php database/seed_services.php      # repeatable; upserts by natural keys
```

Re-running the seeder never duplicates rows; options/sizes removed from `catalog.php` are pruned so the DB tracks the source exactly.
