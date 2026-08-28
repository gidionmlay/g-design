-- ============================================================
-- G DESIGN — M4: Request statuses
-- Widens requests.status from ENUM('pending') to the full
-- controlled status set used by the Admin Dashboard:
--
--   pending → reviewing → in_progress → completed
--   cancelled may be applied from any non-terminal state.
--
-- Safe to re-run (idempotent via CHANGE column redefinition).
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE requests
    MODIFY COLUMN status ENUM('pending','reviewing','in_progress','completed','cancelled')
        NOT NULL DEFAULT 'pending';