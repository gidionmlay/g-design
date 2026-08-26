-- ============================================================
-- G DESIGN — M2: Request attachments table
-- Stores metadata for files uploaded with service requests.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS request_attachments (
    id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    request_id          INT UNSIGNED      NOT NULL,
    original_filename   VARCHAR(255)      NOT NULL,
    stored_filename     VARCHAR(255)      NOT NULL,
    storage_path        VARCHAR(500)      NOT NULL,
    mime_type           VARCHAR(127)      NOT NULL,
    file_extension      VARCHAR(20)       NOT NULL,
    file_size           INT UNSIGNED      NOT NULL,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attachments_stored (stored_filename),
    KEY idx_attachments_request (request_id),
    CONSTRAINT fk_attachments_request FOREIGN KEY (request_id)
        REFERENCES requests (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
