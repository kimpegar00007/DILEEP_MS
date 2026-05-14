-- ============================================================================
-- HOTFIX: Add Missing Columns to Production Database
-- ============================================================================
-- Purpose: Fix schema mismatches after running fresh_install_production.sql v2.0
-- Date: May 13, 2026
-- 
-- This script is IDEMPOTENT — safe to run multiple times.
-- It adds columns only if they don't already exist.
--
-- USAGE:
--   mysql -u root -p dilp_monitoring < hotfix_add_missing_columns.sql
--
-- FIXES:
--   1. beneficiaries.type_of_beneficiaries  — missing → INSERT/UPDATE fails
--   2. beneficiaries.source_of_funds        — missing → INSERT/UPDATE fails
--   3. fieldwork_schedule.province           — missing → "Database error" for non-super_admin
--   4. fieldwork_schedule.manual_override    — missing → auto-status updates may fail
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SELECT 'Starting hotfix: adding missing columns...' AS status;

-- ============================================================================
-- FIX 1: beneficiaries.type_of_beneficiaries
-- ============================================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'beneficiaries'
    AND COLUMN_NAME = 'type_of_beneficiaries'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `beneficiaries` ADD COLUMN `type_of_beneficiaries` VARCHAR(255) DEFAULT NULL COMMENT ''Types of beneficiaries (e.g., Farmers, Former PDL)'' AFTER `type_of_worker`',
    'SELECT ''SKIP: beneficiaries.type_of_beneficiaries already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- FIX 2: beneficiaries.source_of_funds
-- ============================================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'beneficiaries'
    AND COLUMN_NAME = 'source_of_funds'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `beneficiaries` ADD COLUMN `source_of_funds` VARCHAR(255) DEFAULT NULL COMMENT ''Funding source (e.g., GAA, Centrally Managed Fund)'' AFTER `longitude`',
    'SELECT ''SKIP: beneficiaries.source_of_funds already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- FIX 3: fieldwork_schedule.province
-- ============================================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fieldwork_schedule'
    AND COLUMN_NAME = 'province'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `fieldwork_schedule` ADD COLUMN `province` ENUM(''Negros Occidental'',''Negros Oriental'',''Siquijor'') DEFAULT NULL COMMENT ''Province assignment (NULL for cross-province roles)'' AFTER `location`',
    'SELECT ''SKIP: fieldwork_schedule.province already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on province if column was just added
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fieldwork_schedule'
    AND INDEX_NAME = 'idx_fieldwork_province'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `fieldwork_schedule` ADD KEY `idx_fieldwork_province` (`province`)',
    'SELECT ''SKIP: idx_fieldwork_province index already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- FIX 4: fieldwork_schedule.manual_override
-- ============================================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fieldwork_schedule'
    AND COLUMN_NAME = 'manual_override'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `fieldwork_schedule` ADD COLUMN `manual_override` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = status was manually set; skip auto-update until next natural transition'' AFTER `status`',
    'SELECT ''SKIP: fieldwork_schedule.manual_override already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- VALIDATION
-- ============================================================================
SELECT '============================================' AS '';
SELECT 'HOTFIX VALIDATION' AS status;
SELECT '============================================' AS '';

SELECT
    CASE WHEN COUNT(*) = 2
        THEN 'PASS: beneficiaries has type_of_beneficiaries and source_of_funds'
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' of 2 columns found in beneficiaries')
    END AS beneficiaries_check
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'beneficiaries'
AND COLUMN_NAME IN ('type_of_beneficiaries', 'source_of_funds');

SELECT
    CASE WHEN COUNT(*) = 2
        THEN 'PASS: fieldwork_schedule has province and manual_override'
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' of 2 columns found in fieldwork_schedule')
    END AS fieldwork_check
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'fieldwork_schedule'
AND COLUMN_NAME IN ('province', 'manual_override');

SELECT 'HOTFIX COMPLETED SUCCESSFULLY' AS status;
