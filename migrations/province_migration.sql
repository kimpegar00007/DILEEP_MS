-- =====================================================
-- DOLE DILP Monitoring System
-- Province Migration — v2.0
-- =====================================================
-- Run this script on any EXISTING installation to add
-- multi-province support (Negros Occidental,
-- Negros Oriental, Siquijor).
--
-- Safe to run multiple times — all column additions are
-- guarded by information_schema checks.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: Extend users.role ENUM to include super_admin
-- =====================================================
-- Modifying an ENUM is always safe for existing rows;
-- MySQL/MariaDB will not alter stored values.
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('admin','encoder','user','super_admin') NOT NULL DEFAULT 'user';

-- =====================================================
-- STEP 2: Add province column to users
-- (NULL = super_admin with cross-province access)
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'province'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `province` ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL AFTER `role`",
    "SELECT 'users.province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 3: Add province column to beneficiaries
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'beneficiaries'
      AND COLUMN_NAME  = 'province'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE `beneficiaries` ADD COLUMN `province` ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL AFTER `municipality`",
    "SELECT 'beneficiaries.province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 4: Add province column to proponents
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'proponents'
      AND COLUMN_NAME  = 'province'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE `proponents` ADD COLUMN `province` ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL AFTER `district`",
    "SELECT 'proponents.province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 5: Add province column to fieldwork_schedule
-- =====================================================
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fieldwork_schedule'
      AND COLUMN_NAME  = 'province'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE `fieldwork_schedule` ADD COLUMN `province` ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL AFTER `location`",
    "SELECT 'fieldwork_schedule.province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 6: Add province indexes (IF NOT EXISTS)
-- =====================================================
-- users
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND INDEX_NAME   = 'idx_users_province'
);
SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE `users` ADD INDEX `idx_users_province` (`province`)",
    "SELECT 'idx_users_province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- beneficiaries
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'beneficiaries'
      AND INDEX_NAME   = 'idx_beneficiaries_province'
);
SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE `beneficiaries` ADD INDEX `idx_beneficiaries_province` (`province`)",
    "SELECT 'idx_beneficiaries_province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- proponents
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'proponents'
      AND INDEX_NAME   = 'idx_proponents_province'
);
SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE `proponents` ADD INDEX `idx_proponents_province` (`province`)",
    "SELECT 'idx_proponents_province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fieldwork_schedule
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fieldwork_schedule'
      AND INDEX_NAME   = 'idx_fieldwork_province'
);
SET @sql = IF(@idx_exists = 0,
    "ALTER TABLE `fieldwork_schedule` ADD INDEX `idx_fieldwork_province` (`province`)",
    "SELECT 'idx_fieldwork_province already exists — skipping' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 7: Tag all existing data as Negros Occidental
-- Only updates rows where province is still NULL
-- (Safe to re-run — will not overwrite assigned values)
-- =====================================================
UPDATE `beneficiaries`
    SET `province` = 'Negros Occidental'
    WHERE `province` IS NULL;

UPDATE `proponents`
    SET `province` = 'Negros Occidental'
    WHERE `province` IS NULL;

UPDATE `fieldwork_schedule`
    SET `province` = 'Negros Occidental'
    WHERE `province` IS NULL;

-- =====================================================
-- STEP 8: Promote existing admin to super_admin
-- and clear their province (cross-province access)
-- =====================================================
UPDATE `users`
    SET `role` = 'super_admin',
        `province` = NULL
    WHERE `username` = 'admin'
      AND `role` = 'admin';

-- =====================================================
-- STEP 9: Assign province to all other existing users
-- Default: Negros Occidental (adjust manually after)
-- =====================================================
UPDATE `users`
    SET `province` = 'Negros Occidental'
    WHERE `role` != 'super_admin'
      AND `province` IS NULL;

-- =====================================================
-- STEP 10: Verify results
-- =====================================================
SELECT 'Migration complete. Verification:' AS status;

SELECT
    'users' AS tbl,
    SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS super_admins,
    SUM(CASE WHEN province IS NULL AND role != 'super_admin' THEN 1 ELSE 0 END) AS unassigned_province,
    COUNT(*) AS total
FROM users;

SELECT
    'beneficiaries' AS tbl,
    province,
    COUNT(*) AS total
FROM beneficiaries
GROUP BY province;

SELECT
    'proponents' AS tbl,
    province,
    COUNT(*) AS total
FROM proponents
GROUP BY province;

SET FOREIGN_KEY_CHECKS = 1;
