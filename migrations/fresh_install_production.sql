-- ============================================================================
-- DILP MONITORING SYSTEM - FRESH INSTALLATION MIGRATION
-- ============================================================================
-- Purpose: Complete fresh installation for production environment
-- Version: 2.0
-- Date: May 13, 2026
-- Database: dilp_monitoring
-- 
-- WARNING: This script DROPS ALL EXISTING TABLES and recreates them.
--          ALL EXISTING DATA WILL BE LOST. Back up first if needed!
-- 
-- INCLUDES:
-- - All 12 database tables with updated schema
-- - Indexes and foreign key constraints
-- - Database triggers for automated calculations
-- - Seed data: admin user, provinces, org chart, system settings
-- 
-- USAGE:
-- CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- mysql -u root -p dilp_monitoring < fresh_install_production.sql
-- 
-- DEFAULT CREDENTIALS:
-- Username: admin
-- Password: admin123 (CHANGE IMMEDIATELY AFTER FIRST LOGIN!)
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Disable foreign key checks temporarily for clean installation
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PHASE 0: DROP ALL EXISTING TABLES (clean slate)
-- ============================================================================
-- Order matters: drop child tables (with foreign keys) before parent tables

DROP TABLE IF EXISTS `province_access_audit`;
DROP TABLE IF EXISTS `user_provinces`;
DROP TABLE IF EXISTS `proponent_returns`;
DROP TABLE IF EXISTS `proponent_associations`;
DROP TABLE IF EXISTS `fieldwork_schedule`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `beneficiaries`;
DROP TABLE IF EXISTS `proponents`;
DROP TABLE IF EXISTS `org_chart`;
DROP TABLE IF EXISTS `provinces`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- Drop existing triggers
DROP TRIGGER IF EXISTS `calculate_liquidation_deadline`;
DROP TRIGGER IF EXISTS `update_liquidation_deadline`;

-- ============================================================================
-- PHASE 1: CREATE CORE APPLICATION TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: users
-- Description: User accounts with multi-province support and updated roles
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user') NOT NULL DEFAULT 'user',
    `province` VARCHAR(100) DEFAULT NULL COMMENT 'User assigned province (NULL = all provinces for super_admin)',
    `full_name` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_province` (`province`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='User accounts with role-based access control';

-- ----------------------------------------------------------------------------
-- Table: activity_logs
-- Description: System activity tracking with province context
-- ----------------------------------------------------------------------------
CREATE TABLE `activity_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL COMMENT 'Province context of the activity',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user` (`user_id`),
    KEY `idx_activity_logs_table` (`table_name`, `record_id`),
    KEY `idx_activity_logs_province` (`province`),
    KEY `idx_activity_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Audit trail for all system activities';

-- ----------------------------------------------------------------------------
-- Table: beneficiaries
-- Description: Individual beneficiary records with location tracking
-- ----------------------------------------------------------------------------
CREATE TABLE `beneficiaries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `last_name` VARCHAR(100) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) DEFAULT NULL,
    `suffix` VARCHAR(20) DEFAULT NULL,
    `gender` ENUM('Male', 'Female') NOT NULL,
    `barangay` VARCHAR(100) NOT NULL,
    `municipality` VARCHAR(100) NOT NULL,
    `province` VARCHAR(100) DEFAULT 'Negros Occidental',
    `contact_number` VARCHAR(20) DEFAULT NULL,
    `project_name` VARCHAR(255) NOT NULL,
    `type_of_worker` VARCHAR(100) DEFAULT NULL,
    `type_of_beneficiaries` VARCHAR(255) DEFAULT NULL COMMENT 'Types of beneficiaries (e.g., Farmers, Former PDL)',
    `amount_worth` DECIMAL(15,2) NOT NULL,
    `noted_findings` TEXT DEFAULT NULL,
    `date_complied_by_proponent` DATE DEFAULT NULL,
    `date_forwarded_to_ro6` DATE DEFAULT NULL,
    `rpmt_findings` TEXT DEFAULT NULL,
    `date_approved` DATE DEFAULT NULL,
    `date_forwarded_to_nofo` DATE DEFAULT NULL,
    `date_turnover` DATE DEFAULT NULL,
    `date_monitoring` DATE DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `source_of_funds` VARCHAR(255) DEFAULT NULL COMMENT 'Funding source (e.g., GAA, Centrally Managed Fund)',
    `status` ENUM('pending', 'approved', 'implemented', 'monitored') DEFAULT 'pending',
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    KEY `idx_beneficiaries_municipality` (`municipality`),
    KEY `idx_beneficiaries_barangay` (`barangay`),
    KEY `idx_beneficiaries_status` (`status`),
    KEY `idx_beneficiaries_date_approved` (`date_approved`),
    KEY `idx_beneficiaries_province` (`province`),
    KEY `idx_beneficiaries_province_status` (`province`, `status`),
    KEY `idx_beneficiaries_province_municipality` (`province`, `municipality`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Individual beneficiary records';

-- ----------------------------------------------------------------------------
-- Table: proponents
-- Description: Proponent/organization records with enhanced tracking
-- ----------------------------------------------------------------------------
CREATE TABLE `proponents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proponent_type` ENUM('LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others') NOT NULL,
    `date_received` DATE DEFAULT NULL,
    `noted_findings` TEXT DEFAULT NULL,
    `control_number` VARCHAR(50) DEFAULT NULL,
    `number_of_copies` INT(11) DEFAULT NULL,
    `date_copies_received` DATE DEFAULT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT 'Negros Occidental',
    `proponent_name` VARCHAR(255) NOT NULL,
    `project_title` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `number_of_associations` INT(11) DEFAULT NULL,
    `total_beneficiaries` INT(11) NOT NULL,
    `beneficiary_full_name` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated list of beneficiary names',
    `male_beneficiaries` INT(11) DEFAULT 0,
    `female_beneficiaries` INT(11) DEFAULT 0,
    `type_of_beneficiaries` VARCHAR(255) DEFAULT NULL COMMENT 'Types of beneficiaries (e.g., Farmers, Former PDL)',
    `type_of_workers` VARCHAR(255) DEFAULT NULL COMMENT 'Worker classifications',
    `category` ENUM('Formation', 'Enhancement', 'Restoration') NOT NULL,
    `recipient_barangays` TEXT DEFAULT NULL,
    `letter_of_intent_date` DATE DEFAULT NULL,
    `date_forwarded_to_ro6` DATE DEFAULT NULL,
    `rpmt_findings` TEXT DEFAULT NULL,
    `date_complied_by_proponent` DATE DEFAULT NULL,
    `date_complied_by_proponent_nofo` DATE DEFAULT NULL,
    `date_forwarded_to_nofo` DATE DEFAULT NULL,
    `date_approved` DATE DEFAULT NULL,
    `date_check_release` DATE DEFAULT NULL,
    `check_number` VARCHAR(50) DEFAULT NULL,
    `check_date_issued` DATE DEFAULT NULL,
    `or_number` VARCHAR(50) DEFAULT NULL,
    `or_date_issued` DATE DEFAULT NULL,
    `date_turnover` DATE DEFAULT NULL,
    `date_implemented` DATE DEFAULT NULL,
    `date_liquidated` DATE DEFAULT NULL,
    `liquidation_deadline` DATE DEFAULT NULL COMMENT 'Auto-calculated based on proponent type',
    `date_monitoring` DATE DEFAULT NULL,
    `source_of_funds` VARCHAR(255) DEFAULT NULL COMMENT 'Funding source (e.g., GAA, TUPAD)',
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'implemented', 'liquidated', 'monitored') DEFAULT 'pending',
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `control_number` (`control_number`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    KEY `idx_proponents_type` (`proponent_type`),
    KEY `idx_proponents_district` (`district`),
    KEY `idx_proponents_status` (`status`),
    KEY `idx_proponents_control_number` (`control_number`),
    KEY `idx_proponents_date_approved` (`date_approved`),
    KEY `idx_proponents_province` (`province`),
    KEY `idx_proponents_province_status` (`province`, `status`),
    KEY `idx_proponents_province_type` (`province`, `proponent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Proponent and organization records';

-- ----------------------------------------------------------------------------
-- Table: proponent_associations
-- Description: Association mappings for proponents
-- ----------------------------------------------------------------------------
CREATE TABLE `proponent_associations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proponent_id` INT(11) NOT NULL,
    `association_name` VARCHAR(255) NOT NULL,
    `association_address` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `proponent_id` (`proponent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Association mappings for proponents';

-- ----------------------------------------------------------------------------
-- Table: proponent_returns
-- Description: Return tracking for proponents
-- ----------------------------------------------------------------------------
CREATE TABLE `proponent_returns` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proponent_id` INT(11) NOT NULL,
    `return_date` DATE NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `returned_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `returned_by` (`returned_by`),
    KEY `idx_proponent_returns_proponent` (`proponent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Proponent return tracking';

-- ----------------------------------------------------------------------------
-- Table: fieldwork_schedule
-- Description: Fieldwork scheduling and tracking system
-- ----------------------------------------------------------------------------
CREATE TABLE `fieldwork_schedule` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `location` VARCHAR(500) DEFAULT NULL,
    `province` ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL COMMENT 'Province assignment (NULL for cross-province roles)',
    `assigned_user_id` INT(11) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('pending', 'ongoing', 'completed', 'missed') DEFAULT 'pending',
    `manual_override` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = status was manually set; skip auto-update until next natural transition',
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fieldwork_status` (`status`),
    KEY `idx_fieldwork_start_date` (`start_date`),
    KEY `idx_fieldwork_end_date` (`end_date`),
    KEY `idx_fieldwork_assigned_user` (`assigned_user_id`),
    KEY `idx_fieldwork_created_by` (`created_by`),
    KEY `idx_fieldwork_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Fieldwork scheduling system';

-- ----------------------------------------------------------------------------
-- Table: system_settings
-- Description: Application configuration settings
-- ----------------------------------------------------------------------------
CREATE TABLE `system_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(191) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='System configuration settings';

-- ============================================================================
-- PHASE 2: CREATE MULTI-PROVINCE SUPPORT TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: provinces
-- Description: Province reference table for multi-province support
-- ----------------------------------------------------------------------------
CREATE TABLE `provinces` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE NOT NULL COMMENT 'Province code: NO, NOR, SIQ',
    `name` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Full province name',
    `region_code` VARCHAR(10) COMMENT 'Region code: VI, VII',
    `region_name` VARCHAR(100) COMMENT 'Region name for reference',
    `is_active` BOOLEAN DEFAULT TRUE,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`),
    KEY `idx_name` (`name`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Province reference table for Region VI coverage';

-- ----------------------------------------------------------------------------
-- Table: user_provinces
-- Description: User-province access mapping for multi-province support
-- ----------------------------------------------------------------------------
CREATE TABLE `user_provinces` (
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

-- ----------------------------------------------------------------------------
-- Table: province_access_audit
-- Description: Audit trail for province-based access control
-- ----------------------------------------------------------------------------
CREATE TABLE `province_access_audit` (
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

-- ----------------------------------------------------------------------------
-- Table: org_chart
-- Description: Organizational chart with tier-based structure
-- ----------------------------------------------------------------------------
CREATE TABLE `org_chart` (
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

-- ============================================================================
-- PHASE 3: CREATE FOREIGN KEY CONSTRAINTS
-- ============================================================================

ALTER TABLE `activity_logs`
    ADD CONSTRAINT `activity_logs_ibfk_1` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `beneficiaries`
    ADD CONSTRAINT `beneficiaries_ibfk_1` 
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `beneficiaries_ibfk_2` 
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `fieldwork_schedule`
    ADD CONSTRAINT `fieldwork_schedule_ibfk_1` 
    FOREIGN KEY (`assigned_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fieldwork_schedule_ibfk_2` 
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE;

ALTER TABLE `proponents`
    ADD CONSTRAINT `proponents_ibfk_1` 
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `proponents_ibfk_2` 
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `proponent_associations`
    ADD CONSTRAINT `proponent_associations_ibfk_1` 
    FOREIGN KEY (`proponent_id`) REFERENCES `proponents`(`id`) ON DELETE CASCADE;

ALTER TABLE `proponent_returns`
    ADD CONSTRAINT `proponent_returns_ibfk_1` 
    FOREIGN KEY (`proponent_id`) REFERENCES `proponents`(`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `proponent_returns_ibfk_2` 
    FOREIGN KEY (`returned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `user_provinces`
    ADD CONSTRAINT `fk_user_provinces_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_user_provinces_province` 
    FOREIGN KEY (`province_id`) REFERENCES `provinces`(`id`) ON DELETE CASCADE;

ALTER TABLE `province_access_audit`
    ADD CONSTRAINT `fk_province_audit_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- ============================================================================
-- PHASE 4: CREATE DATABASE TRIGGERS
-- ============================================================================

-- Trigger: Auto-calculate liquidation deadline on INSERT
DELIMITER $$
CREATE TRIGGER `calculate_liquidation_deadline` 
BEFORE INSERT ON `proponents` 
FOR EACH ROW 
BEGIN
    IF NEW.date_turnover IS NOT NULL THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END$$
DELIMITER ;

-- Trigger: Auto-update liquidation deadline on UPDATE
DELIMITER $$
CREATE TRIGGER `update_liquidation_deadline` 
BEFORE UPDATE ON `proponents` 
FOR EACH ROW 
BEGIN
    IF NEW.date_turnover IS NOT NULL AND (
        OLD.date_turnover IS NULL OR 
        NEW.date_turnover != OLD.date_turnover OR 
        NEW.proponent_type != OLD.proponent_type
    ) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- PHASE 5: INSERT SEED DATA
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Seed: Default Admin User
-- Default credentials: admin / admin123
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `province`, `full_name`, `is_active`)
VALUES (
    1,
    'admin',
    'admin@dilp.gov.ph',
    '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm',
    'super_admin',
    NULL,
    'System Administrator',
    1
);

-- ----------------------------------------------------------------------------
-- Seed: Province Reference Data
-- ----------------------------------------------------------------------------
INSERT INTO `provinces` (`code`, `name`, `region_code`, `region_name`, `is_active`, `display_order`)
VALUES
    ('NO', 'Negros Occidental', 'VI', 'Western Visayas', TRUE, 1),
    ('NOR', 'Negros Oriental', 'VII', 'Central Visayas', TRUE, 2),
    ('SIQ', 'Siquijor', 'VII', 'Central Visayas', TRUE, 3);

-- ----------------------------------------------------------------------------
-- Seed: User-Province Mappings (Admin access to all provinces)
-- ----------------------------------------------------------------------------
INSERT INTO `user_provinces` (`user_id`, `province_id`, `role`, `is_default`)
SELECT 
    1 AS user_id,
    p.`id` AS province_id,
    'super_admin' AS role,
    CASE WHEN p.`code` = 'NO' THEN TRUE ELSE FALSE END AS is_default
FROM `provinces` p;

-- ----------------------------------------------------------------------------
-- Seed: Organizational Chart Structure
-- ----------------------------------------------------------------------------
INSERT INTO `org_chart` (`tier`, `sort_order`, `position_order`, `position_title`, `person_name`, `province`)
VALUES
    -- Tier 0: Regional Director
    (0, 0, 1, 'Regional Director', NULL, NULL),
    -- Tier 1: Field Office Heads
    (1, 0, 2, 'Field Office Head - Negros Occidental', NULL, 'Negros Occidental'),
    (1, 1, 3, 'Field Office Head - Negros Oriental', NULL, 'Negros Oriental'),
    (1, 2, 4, 'Field Office Head - Siquijor', NULL, 'Siquijor'),
    -- Tier 2: DILEEP Focal Persons
    (2, 0, 5, 'DILEEP Focal Person - Negros Occidental', NULL, 'Negros Occidental'),
    (2, 1, 6, 'DILEEP Focal Person - Negros Oriental', NULL, 'Negros Oriental'),
    (2, 2, 7, 'DILEEP Focal Person - Siquijor', NULL, 'Siquijor');

-- ----------------------------------------------------------------------------
-- Seed: System Settings
-- ----------------------------------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`)
VALUES ('maintenance_mode', '0');

-- ============================================================================
-- PHASE 6: POST-INSTALLATION VALIDATION
-- ============================================================================

SELECT '============================================' AS '';
SELECT 'FRESH INSTALLATION VALIDATION' AS status;
SELECT '============================================' AS '';

-- Verify all tables were created
SELECT 
    CASE 
        WHEN COUNT(*) = 12 THEN 'PASS: All 12 tables created successfully'
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' of 12 tables found')
    END AS table_validation
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN (
    'users', 'activity_logs', 'beneficiaries', 'proponents', 
    'proponent_associations', 'proponent_returns', 'fieldwork_schedule', 
    'system_settings', 'provinces', 'user_provinces', 
    'province_access_audit', 'org_chart'
);

-- Verify users.role ENUM includes new roles
SELECT 
    CASE 
        WHEN COLUMN_TYPE LIKE '%super_admin%' AND COLUMN_TYPE LIKE '%regional_director%' 
        THEN 'PASS: users.role ENUM includes super_admin and regional_director'
        ELSE 'FAIL: users.role ENUM not configured correctly'
    END AS role_enum_validation
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'role';

-- Verify admin user was created
SELECT 
    CASE 
        WHEN COUNT(*) = 1 THEN 'PASS: Admin user created successfully'
        ELSE 'FAIL: Admin user not found'
    END AS admin_user_validation,
    MAX(username) AS username,
    MAX(role) AS role,
    MAX(province) AS province
FROM users 
WHERE id = 1 AND username = 'admin';

-- Verify provinces were populated
SELECT 
    CASE 
        WHEN COUNT(*) = 3 THEN CONCAT('PASS: ', COUNT(*), ' provinces populated')
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' provinces found')
    END AS province_validation
FROM provinces;

-- Verify user-province mappings
SELECT 
    CASE 
        WHEN COUNT(*) = 3 THEN CONCAT('PASS: ', COUNT(*), ' user-province mappings created')
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' mappings found')
    END AS mapping_validation
FROM user_provinces;

-- Verify org chart structure
SELECT 
    CASE 
        WHEN COUNT(*) >= 7 THEN CONCAT('PASS: ', COUNT(*), ' org chart positions created')
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' positions found')
    END AS orgchart_validation
FROM org_chart;

-- Verify triggers were created
SELECT 
    CASE 
        WHEN COUNT(*) >= 2 THEN CONCAT('PASS: ', COUNT(*), ' triggers created')
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' triggers found')
    END AS trigger_validation
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = DATABASE()
AND TRIGGER_NAME IN ('calculate_liquidation_deadline', 'update_liquidation_deadline');

-- Display summary
SELECT '============================================' AS '';
SELECT 'INSTALLATION SUMMARY' AS '';
SELECT '============================================' AS '';

SELECT 
    'Database' AS component,
    DATABASE() AS name,
    'Ready' AS status
UNION ALL
SELECT 
    'Tables',
    CAST(COUNT(*) AS CHAR),
    'Created'
FROM information_schema.tables 
WHERE table_schema = DATABASE()
UNION ALL
SELECT 
    'Admin User',
    'admin',
    'Created (Password: admin123)'
UNION ALL
SELECT 
    'Provinces',
    CAST(COUNT(*) AS CHAR),
    'Populated'
FROM provinces
UNION ALL
SELECT 
    'Org Chart Positions',
    CAST(COUNT(*) AS CHAR),
    'Created'
FROM org_chart
UNION ALL
SELECT 
    'System Settings',
    'maintenance_mode',
    'Disabled (0)';

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- INSTALLATION COMPLETE
-- ============================================================================

SELECT '============================================' AS '';
SELECT 'FRESH INSTALLATION COMPLETED SUCCESSFULLY' AS status;
SELECT NOW() AS completed_at;
SELECT '============================================' AS '';

SELECT 'IMPORTANT: Next Steps' AS action, 'Please complete the following:' AS description
UNION ALL
SELECT '1. Login', 'Use credentials: admin / admin123'
UNION ALL
SELECT '2. Change Password', 'IMMEDIATELY change the default admin password'
UNION ALL
SELECT '3. Create Users', 'Add additional user accounts as needed'
UNION ALL
SELECT '4. Update Org Chart', 'Replace "To Be Assigned" with actual personnel names'
UNION ALL
SELECT '5. Configure Settings', 'Review and update system settings as needed'
UNION ALL
SELECT '6. Test Application', 'Verify all features work correctly'
UNION ALL
SELECT '7. Backup Database', 'Create initial backup: mysqldump -u root -p dilp_monitoring > backup.sql';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
