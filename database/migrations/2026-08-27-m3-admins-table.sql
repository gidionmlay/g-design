-- ============================================================
-- G DESIGN — M3: Admin authentication
-- `admins` table: secure administrator accounts.
-- Passwords are stored ONLY as password_hash() output (bcrypt/argon2).
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    username       VARCHAR(100)      NOT NULL,
    email          VARCHAR(190)      NOT NULL,
    password_hash  VARCHAR(255)      NOT NULL,
    full_name      VARCHAR(190)      NULL,
    role           VARCHAR(50)       NOT NULL DEFAULT 'admin',
    is_active      TINYINT(1)        NOT NULL DEFAULT 1,
    last_login_at  TIMESTAMP         NULL DEFAULT NULL,
    created_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
