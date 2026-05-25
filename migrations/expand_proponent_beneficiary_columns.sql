-- Migration: Expand narrow VARCHAR(255) columns on `proponents` to TEXT.
--
-- Why: `beneficiary_full_name` stores a JSON-encoded array of full names
-- (commas in names like "AGRAVANTE, NENA ACEDERA" are preserved this way).
-- With ~10+ beneficiaries the JSON string easily exceeds 255 chars, so
-- MySQL silently truncates the value, json_decode() then returns NULL,
-- and the names "disappear" when re-opening via Edit.
--
-- `type_of_beneficiaries` and `type_of_workers` store comma-joined option
-- labels and face the same truncation risk when many checkboxes are ticked.
--
-- Idempotent: re-running this migration is a no-op (DATA_TYPE check).

-- ---------------------------------------------------------------------
-- 1. proponents.beneficiary_full_name  →  TEXT
-- ---------------------------------------------------------------------
SET @dt = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = 'proponents'
             AND COLUMN_NAME  = 'beneficiary_full_name');

SET @sql = IF(@dt IS NOT NULL AND @dt NOT IN ('text','mediumtext','longtext'),
    'ALTER TABLE proponents MODIFY COLUMN beneficiary_full_name TEXT NULL',
    'SELECT "proponents.beneficiary_full_name already TEXT-class or missing, skipping..." AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2. proponents.type_of_beneficiaries  →  TEXT
-- ---------------------------------------------------------------------
SET @dt = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = 'proponents'
             AND COLUMN_NAME  = 'type_of_beneficiaries');

SET @sql = IF(@dt IS NOT NULL AND @dt NOT IN ('text','mediumtext','longtext'),
    'ALTER TABLE proponents MODIFY COLUMN type_of_beneficiaries TEXT NULL',
    'SELECT "proponents.type_of_beneficiaries already TEXT-class or missing, skipping..." AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 3. proponents.type_of_workers  →  TEXT
-- ---------------------------------------------------------------------
SET @dt = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = 'proponents'
             AND COLUMN_NAME  = 'type_of_workers');

SET @sql = IF(@dt IS NOT NULL AND @dt NOT IN ('text','mediumtext','longtext'),
    'ALTER TABLE proponents MODIFY COLUMN type_of_workers TEXT NULL',
    'SELECT "proponents.type_of_workers already TEXT-class or missing, skipping..." AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
