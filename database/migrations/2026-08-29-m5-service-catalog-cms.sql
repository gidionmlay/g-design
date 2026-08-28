-- ============================================================
-- G DESIGN — M5: Admin Service & Catalog CMS
--
-- 1) Extend service_items with:
--      short_description : one-line summary shown on cards/lists
--      pricing_type      : fixed | starting_from | range | quote
--      price             : single price for `fixed` / `starting_from`
--      min_price         : lower bound for `range`
--      max_price         : upper bound for `range`
--      currency          : ISO code, default 'TZS' (frontend formats display)
--
--    Values are stored as raw numbers only (never formatted currency strings).
--
-- 2) service_images : managed image references.
--    Files live OUTSIDE the public document root (storage/service-images/).
--    The database stores only the randomized stored filename + MIME; the API
--    serves images through a controller route, so no filesystem paths leak.
--
-- Safe to re-run (idempotent via ADD COLUMN IF NOT EXISTS / CREATE TABLE IF NOT EXISTS).
-- ============================================================

SET NAMES utf8mb4;

-- 1) Pricing + short description on services.
ALTER TABLE service_items
    ADD COLUMN IF NOT EXISTS short_description VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS pricing_type ENUM('fixed','starting_from','range','quote')
        NOT NULL DEFAULT 'quote' AFTER image_path,
    ADD COLUMN IF NOT EXISTS price DECIMAL(14,2) NULL AFTER pricing_type,
    ADD COLUMN IF NOT EXISTS min_price DECIMAL(14,2) NULL AFTER price,
    ADD COLUMN IF NOT EXISTS max_price DECIMAL(14,2) NULL AFTER min_price,
    ADD COLUMN IF NOT EXISTS currency VARCHAR(3) NOT NULL DEFAULT 'TZS' AFTER max_price;

-- Enforce sane pricing combinations.
--   fixed / starting_from : price required
--   range                 : min_price + max_price required, min <= max
--   quote                 : none required
ALTER TABLE service_items
    DROP CONSTRAINT IF EXISTS chk_items_pricing,
    ADD CONSTRAINT chk_items_pricing CHECK (
        (pricing_type = 'quote')
        OR (pricing_type IN ('fixed','starting_from') AND price IS NOT NULL)
        OR (pricing_type = 'range' AND min_price IS NOT NULL AND max_price IS NOT NULL AND min_price <= max_price)
    );

-- 2) Managed service image catalog.
CREATE TABLE IF NOT EXISTS service_images (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    item_id        INT UNSIGNED      NOT NULL,
    stored_filename VARCHAR(255)     NOT NULL,
    mime_type      VARCHAR(50)       NOT NULL,
    file_size      INT UNSIGNED      NOT NULL DEFAULT 0,
    created_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_images_item (item_id),
    CONSTRAINT fk_images_item FOREIGN KEY (item_id)
        REFERENCES service_items (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point existing seeded image_path values at the managed table where sensible
-- is intentionally NOT done here: seeded paths are static asset paths under
-- public/assets/images and remain the responsibility of the seed/bundled catalog.
