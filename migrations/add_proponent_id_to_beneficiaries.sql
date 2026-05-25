-- Migration: Add proponent_id nullable FK to beneficiaries table
-- This allows linking individual beneficiaries to group proponents.
-- Idempotent: safe to run multiple times (checks if column exists first).

-- Add column only if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'beneficiaries' 
                   AND COLUMN_NAME = 'proponent_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE beneficiaries ADD COLUMN proponent_id INT(11) UNSIGNED NULL DEFAULT NULL AFTER province',
    'SELECT "Column proponent_id already exists, skipping..." AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index only if it doesn't exist
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'beneficiaries' 
                   AND INDEX_NAME = 'idx_beneficiaries_proponent_id');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE beneficiaries ADD INDEX idx_beneficiaries_proponent_id (proponent_id)',
    'SELECT "Index idx_beneficiaries_proponent_id already exists, skipping..." AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
