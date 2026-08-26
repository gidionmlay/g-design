-- ============================================================
-- G DESIGN — Migration 2026-08-24
-- 1) Extend service_fields.type with 'select' (dropdown)
-- 2) Remove catalog rows superseded by the catalog update:
--      - branding/logo-design        (moved to graphic-design)
--    Run AFTER updating database/catalog.php, then reseed:
--      php database/seed_services.php
-- ============================================================

USE gdesign;

-- 1) ENUM extension (idempotent in effect; re-running is a no-op change)
ALTER TABLE service_fields
    MODIFY type ENUM('radio','checkbox','text','email','tel','number','date','textarea','select','sizegrid','upload') NOT NULL;

-- 2) Orphaned item from the Logo Design move (fields/options cascade)
DELETE FROM service_items
WHERE slug = 'logo-design'
  AND category_id = (SELECT id FROM service_categories WHERE slug = 'branding');
