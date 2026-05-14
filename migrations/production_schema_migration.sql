-- ============================================================================
-- DILP MONITORING SYSTEM - PRODUCTION SCHEMA MIGRATION
-- ============================================================================
-- Purpose: Fix "Database Error" by updating production schema to match code
-- Date: May 13, 2026
-- Strategy: Preserve all data, update schema incrementally
-- 
-- CRITICAL SCHEMA MISMATCHES FIXED:
-- 1. users.role ENUM - Add super_admin, regional_director
-- 2. users.province column - Add for multi-province support
-- 3. Missing tables - org_chart, provinces, user_provinces, province_access_audit
-- 4. Data migration - Promote admin user, assign provinces
-- 
-- BACKUP REQUIRED: Run mysqldump before executing this script!
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PHASE 1: PRE-MIGRATION VALIDATION
-- ============================================================================

-- Check if we're running on the correct database
SELECT 'Starting migration on database:' AS status, DATABASE() AS db_name;

-- Verify critical tables exist before migration
SELECT 
    CASE 
        WHEN COUNT(*) = 6 THEN 'PASS: All core tables exist'
        ELSE CONCAT('FAIL: Missing tables. Found ', COUNT(*), ' of 6 expected tables')
    END AS validation_status
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN ('users', 'beneficiaries', 'proponents', 'activity_logs', 'proponent_associations', 'proponent_returns');

-- ============================================================================
-- PHASE 2: CREATE MISSING TABLES (NON-BREAKING CHANGES)
-- ============================================================================

-- Create provinces reference table
CREATE TABLE IF NOT EXISTS `provinces` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE NOT NULL COMMENT 'Province code: NO, NOR, SIQ',
    `name` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Full province name',
    `region_code` VARCHAR(10) COMMENT 'Region code: VI, VII, VIII',
    `region_name` VARCHAR(100) COMMENT 'Region name for reference',
    `is_active` BOOLEAN DEFAULT TRUE,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`),
    KEY `idx_name` (`name`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Reference table for provinces in Region VI';

-- Create org_chart table
CREATE TABLE IF NOT EXISTS `org_chart` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `tier` TINYINT NOT NULL DEFAULT 0 COMMENT '0=Regional Dir, 1=Field Office Head, 2=DILEEP Focal, 3=Staff',
    `sort_order` TINYINT NOT NULL DEFAULT 0 COMMENT 'Order within tier (0-4)',
    `position_order` INT NOT NULL DEFAULT 0 COMMENT 'Legacy sort field',
    `position_title` VARCHAR(255) NOT NULL,
    `person_name` VARCHAR(255) DEFAULT NULL COMMENT 'Name of person in this position',
    `province` VARCHAR(100) DEFAULT NULL COMMENT 'Province assignment for position',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_tier_sort` (`tier`, `sort_order`),
    KEY `idx_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Organizational chart structure with multi-person tier support';

-- Create user_provinces mapping table
CREATE TABLE IF NOT EXISTS `user_provinces` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `province_id` INT NOT NULL,
    `role` ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user') DEFAULT 'user' COMMENT 'Role in this province',
    `is_default` BOOLEAN DEFAULT FALSE COMMENT 'User default province',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_province` (`user_id`, `province_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_province` (`province_id`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Maps users to provinces they can access';

-- Create province access audit table
CREATE TABLE IF NOT EXISTS `province_access_audit` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'create, read, update, delete',
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` INT,
    `province_accessed` VARCHAR(100),
    `allowed` BOOLEAN DEFAULT TRUE,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_province` (`province_accessed`),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_allowed` (`allowed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Audit trail for province-based access control';

-- ============================================================================
-- PHASE 3: ADD MISSING COLUMNS TO EXISTING TABLES
-- ============================================================================

-- Add province column to users table (nullable for now)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) DEFAULT NULL 
COMMENT 'User assigned province (NULL = all provinces for super_admin/regional_director)'
AFTER `role`;

-- Add province column to activity_logs (if missing)
ALTER TABLE `activity_logs` 
ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) DEFAULT NULL
COMMENT 'Province context of the activity'
AFTER `description`;

-- Ensure province columns exist in beneficiaries (should already exist)
ALTER TABLE `beneficiaries` 
MODIFY COLUMN `province` VARCHAR(100) DEFAULT 'Negros Occidental'
COMMENT 'Province where beneficiary is located';

-- Ensure province column exists in proponents (should already exist)
ALTER TABLE `proponents` 
MODIFY COLUMN `province` VARCHAR(100) DEFAULT 'Negros Occidental'
COMMENT 'Province where proponent is located';

-- ============================================================================
-- PHASE 4: POPULATE REFERENCE DATA
-- ============================================================================

-- Populate provinces table
INSERT INTO `provinces` (`code`, `name`, `region_code`, `region_name`, `is_active`, `display_order`)
VALUES
    ('NO', 'Negros Occidental', 'VI', 'Western Visayas', TRUE, 1),
    ('NOR', 'Negros Oriental', 'VII', 'Central Visayas', TRUE, 2),
    ('SIQ', 'Siquijor', 'VII', 'Central Visayas', TRUE, 3)
ON DUPLICATE KEY UPDATE
    `is_active` = VALUES(`is_active`),
    `region_name` = VALUES(`region_name`),
    `display_order` = VALUES(`display_order`);

-- ============================================================================
-- PHASE 5: DATA MIGRATION - ASSIGN PROVINCES TO EXISTING USERS
-- ============================================================================

-- Set all existing users to Negros Occidental province
UPDATE `users` 
SET `province` = 'Negros Occidental'
WHERE `province` IS NULL;

-- Ensure all beneficiaries have province set
UPDATE `beneficiaries`
SET `province` = 'Negros Occidental'
WHERE `province` IS NULL OR `province` = '';

-- Ensure all proponents have province set
UPDATE `proponents`
SET `province` = 'Negros Occidental'
WHERE `province` IS NULL OR `province` = '';

-- ============================================================================
-- PHASE 6: UPDATE USERS.ROLE ENUM (BREAKING CHANGE - REQUIRES CAREFUL HANDLING)
-- ============================================================================

-- Step 1: Add temporary column to store current roles
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role_backup` VARCHAR(50) DEFAULT NULL;

-- Step 2: Backup current role values
UPDATE `users` SET `role_backup` = `role`;

-- Step 3: Modify the role ENUM to include new roles
-- This is the critical change that fixes the "Database Error"
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user') 
NOT NULL DEFAULT 'user';

-- Step 4: Promote user 'admin' (id=1) to super_admin role
UPDATE `users` 
SET `role` = 'super_admin' 
WHERE `id` = 1 AND `username` = 'admin';

-- Step 5: Set super_admin province to NULL (sees all provinces)
UPDATE `users` 
SET `province` = NULL 
WHERE `role` = 'super_admin';

-- Step 6: Drop backup column after successful migration
ALTER TABLE `users` DROP COLUMN IF EXISTS `role_backup`;

-- ============================================================================
-- PHASE 7: CREATE FOREIGN KEY CONSTRAINTS FOR NEW TABLES
-- ============================================================================

-- Add foreign key for user_provinces -> users
ALTER TABLE `user_provinces`
ADD CONSTRAINT `fk_user_provinces_user` 
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Add foreign key for user_provinces -> provinces
ALTER TABLE `user_provinces`
ADD CONSTRAINT `fk_user_provinces_province` 
FOREIGN KEY (`province_id`) REFERENCES `provinces`(`id`) ON DELETE CASCADE;

-- Add foreign key for province_access_audit -> users
ALTER TABLE `province_access_audit`
ADD CONSTRAINT `fk_province_audit_user` 
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- ============================================================================
-- PHASE 8: POPULATE USER-PROVINCE MAPPINGS
-- ============================================================================

-- Get Negros Occidental province ID
SET @negros_occ_id = (SELECT `id` FROM `provinces` WHERE `name` = 'Negros Occidental' LIMIT 1);

-- Assign all existing users to Negros Occidental province
INSERT INTO `user_provinces` (`user_id`, `province_id`, `role`, `is_default`)
SELECT 
    u.`id`, 
    @negros_occ_id, 
    u.`role`, 
    TRUE
FROM `users` u
WHERE NOT EXISTS (
    SELECT 1 FROM `user_provinces` up 
    WHERE up.`user_id` = u.`id` AND up.`province_id` = @negros_occ_id
);

-- ============================================================================
-- PHASE 9: CREATE PERFORMANCE INDEXES
-- ============================================================================

-- Beneficiaries province indexes (if not exist)
CREATE INDEX IF NOT EXISTS `idx_beneficiaries_province_status` 
ON `beneficiaries`(`province`, `status`);

CREATE INDEX IF NOT EXISTS `idx_beneficiaries_province_municipality` 
ON `beneficiaries`(`province`, `municipality`);

-- Proponents province indexes (if not exist)
CREATE INDEX IF NOT EXISTS `idx_proponents_province_status` 
ON `proponents`(`province`, `status`);

CREATE INDEX IF NOT EXISTS `idx_proponents_province_type` 
ON `proponents`(`province`, `proponent_type`);

-- Users province index
CREATE INDEX IF NOT EXISTS `idx_users_province` 
ON `users`(`province`);

-- ============================================================================
-- PHASE 10: SEED INITIAL ORG CHART DATA (OPTIONAL)
-- ============================================================================

-- Insert default org chart structure (can be customized later via UI)
INSERT INTO `org_chart` (`tier`, `sort_order`, `position_order`, `position_title`, `person_name`, `province`)
VALUES
    (0, 0, 1, 'Regional Director', 'To Be Assigned', NULL),
    (1, 0, 2, 'Field Office Head - Negros Occidental', 'To Be Assigned', 'Negros Occidental'),
    (2, 0, 3, 'DILEEP Focal Person - Negros Occidental', 'To Be Assigned', 'Negros Occidental'),
    (3, 0, 4, 'Staff Member', 'To Be Assigned', 'Negros Occidental')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================================
-- PHASE 11: POST-MIGRATION VALIDATION
-- ============================================================================

-- Verify all new tables were created
SELECT 
    CASE 
        WHEN COUNT(*) >= 4 THEN 'PASS: All new tables created'
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' of 4 new tables found')
    END AS validation_status
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN ('provinces', 'org_chart', 'user_provinces', 'province_access_audit');

-- Verify users.role ENUM was updated
SELECT 
    CASE 
        WHEN COLUMN_TYPE LIKE '%super_admin%' AND COLUMN_TYPE LIKE '%regional_director%' 
        THEN 'PASS: users.role ENUM updated successfully'
        ELSE 'FAIL: users.role ENUM not updated correctly'
    END AS validation_status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'role';

-- Verify users.province column exists
SELECT 
    CASE 
        WHEN COUNT(*) = 1 THEN 'PASS: users.province column exists'
        ELSE 'FAIL: users.province column missing'
    END AS validation_status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'province';

-- Verify admin user was promoted to super_admin
SELECT 
    CASE 
        WHEN role = 'super_admin' THEN 'PASS: Admin user promoted to super_admin'
        ELSE CONCAT('FAIL: Admin user role is ', role)
    END AS validation_status,
    username,
    role,
    province
FROM users 
WHERE id = 1;

-- Verify provinces were populated
SELECT 
    CASE 
        WHEN COUNT(*) >= 3 THEN CONCAT('PASS: ', COUNT(*), ' provinces populated')
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' provinces found')
    END AS validation_status
FROM provinces;

-- Verify user-province mappings were created
SELECT 
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM users) 
        THEN CONCAT('PASS: ', COUNT(*), ' user-province mappings created')
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' mappings for ', (SELECT COUNT(*) FROM users), ' users')
    END AS validation_status
FROM user_provinces;

-- Show summary of users and their provinces
SELECT 
    u.id,
    u.username,
    u.role,
    u.province AS direct_province,
    GROUP_CONCAT(p.name ORDER BY p.name) AS mapped_provinces
FROM users u
LEFT JOIN user_provinces up ON u.id = up.user_id
LEFT JOIN provinces p ON up.province_id = p.id
GROUP BY u.id, u.username, u.role, u.province
ORDER BY u.id;

-- ============================================================================
-- PHASE 12: OPTIMIZE TABLES
-- ============================================================================

ANALYZE TABLE `users`;
ANALYZE TABLE `beneficiaries`;
ANALYZE TABLE `proponents`;
ANALYZE TABLE `provinces`;
ANALYZE TABLE `user_provinces`;
ANALYZE TABLE `org_chart`;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================

SELECT 
    '============================================' AS '',
    'MIGRATION COMPLETED SUCCESSFULLY' AS status,
    NOW() AS completed_at,
    '============================================' AS '';

SELECT 'Next Steps:' AS action, 'Test user login, verify province filtering, check application functionality' AS description
UNION ALL
SELECT 'Backup:', 'Create a new backup of the migrated database'
UNION ALL
SELECT 'Monitor:', 'Check application logs for any errors'
UNION ALL
SELECT 'Verify:', 'Test all CRUD operations on proponents and beneficiaries';

-- ============================================================================
-- ROLLBACK INSTRUCTIONS (IF NEEDED)
-- ============================================================================
/*
IF THIS MIGRATION FAILS OR CAUSES ISSUES:

1. IMMEDIATE ROLLBACK:
   ROLLBACK;
   -- This will undo all changes if still in transaction

2. RESTORE FROM BACKUP:
   mysql -u root -p dilp_monitoring < backup_before_migration.sql

3. MANUAL ROLLBACK (if transaction already committed):
   -- Drop new tables
   DROP TABLE IF EXISTS province_access_audit;
   DROP TABLE IF EXISTS user_provinces;
   DROP TABLE IF EXISTS org_chart;
   DROP TABLE IF EXISTS provinces;
   
   -- Revert users.role ENUM
   ALTER TABLE users MODIFY COLUMN role ENUM('admin','encoder','user') DEFAULT 'user';
   
   -- Remove province column
   ALTER TABLE users DROP COLUMN province;
   ALTER TABLE activity_logs DROP COLUMN province;
   
   -- Restore admin user role
   UPDATE users SET role = 'admin' WHERE id = 1;

4. CONTACT SUPPORT:
   - Review error logs
   - Document the issue
   - Restore from backup
   - Contact database administrator
*/
