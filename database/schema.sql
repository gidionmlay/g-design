-- ============================================================
-- G DESIGN — M0 Database Schema
-- MySQL / MariaDB (InnoDB, utf8mb4)
-- Import:  mysql -u <user> -p gdesign < database/schema.sql
-- Safe to re-run (idempotent CREATE TABLE IF NOT EXISTS).
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Service catalog: categories -> items -> fields -> options/sizes
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS service_categories (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(100)     NOT NULL,
    name          VARCHAR(150)     NOT NULL,
    tag           VARCHAR(50)      NULL,
    description   TEXT             NULL,
    image_path    VARCHAR(255)     NULL,
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_items (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    category_id   INT UNSIGNED      NOT NULL,
    slug          VARCHAR(100)      NOT NULL,
    name          VARCHAR(150)      NOT NULL,
    description   TEXT              NULL,
    image_path    VARCHAR(255)      NULL,
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active     TINYINT(1)        NOT NULL DEFAULT 1,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_category_slug (category_id, slug),
    KEY idx_items_slug (slug),
    KEY idx_items_active_sort (is_active, sort_order),
    CONSTRAINT fk_items_category FOREIGN KEY (category_id)
        REFERENCES service_categories (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_fields (
    id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    item_id             INT UNSIGNED      NOT NULL,
    field_key           VARCHAR(100)      NOT NULL,
    label               VARCHAR(190)      NOT NULL,
    type                ENUM('radio','checkbox','text','email','tel','number','date','textarea','select','sizegrid','upload') NOT NULL,
    required            TINYINT(1)        NOT NULL DEFAULT 0,
    placeholder         VARCHAR(255)      NULL,
    hint                VARCHAR(255)      NULL,
    sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    show_when_json      LONGTEXT          NULL,
    one_size_when_json  LONGTEXT          NULL,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fields_item_key (item_id, field_key),
    KEY idx_fields_sort (item_id, sort_order),
    CONSTRAINT fk_fields_item FOREIGN KEY (item_id)
        REFERENCES service_items (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_fields_show_when CHECK (show_when_json IS NULL OR JSON_VALID(show_when_json)),
    CONSTRAINT chk_fields_one_size_when CHECK (one_size_when_json IS NULL OR JSON_VALID(one_size_when_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_field_options (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    field_id      INT UNSIGNED      NOT NULL,
    option_value  VARCHAR(255)      NOT NULL,
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_options_field_value (field_id, option_value),
    KEY idx_options_sort (field_id, sort_order),
    CONSTRAINT fk_options_field FOREIGN KEY (field_id)
        REFERENCES service_fields (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only populated for fields of type 'sizegrid'
CREATE TABLE IF NOT EXISTS service_field_sizes (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    field_id    INT UNSIGNED      NOT NULL,
    size_label  VARCHAR(50)       NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sizes_field_label (field_id, size_label),
    KEY idx_sizes_sort (field_id, sort_order),
    CONSTRAINT fk_sizes_field FOREIGN KEY (field_id)
        REFERENCES service_fields (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
