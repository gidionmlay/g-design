-- ============================================================
-- G DESIGN — M1: Requests table
-- Stores client quote/service requests submitted via the wizard.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS requests (
    id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    request_reference   VARCHAR(30)       NOT NULL,
    service_item_id     INT UNSIGNED      NOT NULL,
    client_name         VARCHAR(150)      NOT NULL,
    client_email        VARCHAR(255)      NOT NULL,
    client_phone        VARCHAR(50)       NULL,
    company_name        VARCHAR(200)      NULL,
    description         TEXT              NULL,
    quantity            INT UNSIGNED      NULL,
    requirements_data   JSON              NULL,
    status              ENUM('pending')   NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_requests_reference (request_reference),
    KEY idx_requests_service_item (service_item_id),
    KEY idx_requests_status (status),
    KEY idx_requests_created (created_at),
    CONSTRAINT fk_requests_service_item FOREIGN KEY (service_item_id)
        REFERENCES service_items (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
