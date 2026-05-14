-- ============================================================================
-- DILP MONITORING SYSTEM - PRODUCTION DEPLOYMENT (FRESH INSTALL)
-- ============================================================================
-- This script performs a fresh installation for production deployment
-- Tables: activity_logs, beneficiaries, proponents, proponent_associations,
--         proponent_returns
-- PRESERVES: users table (production accounts remain intact)
-- WARNING: This will DROP and recreate all data tables - use with caution!
-- ============================================================================
-- Generated: May 2026
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- STEP 1: DROP EXISTING TABLES (IF THEY EXIST)
-- ============================================================================
-- Note: Must drop in correct order - child tables first, then parent tables

DROP TABLE IF EXISTS `proponent_returns`;
DROP TABLE IF EXISTS `proponent_associations`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `beneficiaries`;
DROP TABLE IF EXISTS `proponents`;

-- ============================================================================
-- STEP 2: CREATE TABLE STRUCTURES
-- ============================================================================

-- Create activity_logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_table` (`table_name`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create beneficiaries table
CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `type_of_worker` varchar(100) DEFAULT NULL,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL,
  `amount_worth` decimal(15,2) NOT NULL,
  `noted_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_monitoring` date DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','implemented','monitored') DEFAULT 'pending',
  `source_of_funds` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_beneficiaries_municipality` (`municipality`),
  KEY `idx_beneficiaries_barangay` (`barangay`),
  KEY `idx_beneficiaries_status` (`status`),
  KEY `idx_beneficiaries_date_approved` (`date_approved`),
  KEY `idx_beneficiaries_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create proponents table
CREATE TABLE `proponents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_type` enum('LGU-associated','Non-LGU-associated','By Administration','Others') NOT NULL,
  `date_received` date DEFAULT NULL,
  `noted_findings` text DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `number_of_copies` int(11) DEFAULT NULL,
  `date_copies_received` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `proponent_name` varchar(255) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `number_of_associations` int(11) DEFAULT NULL,
  `total_beneficiaries` int(11) NOT NULL,
  `beneficiary_full_name` varchar(255) DEFAULT NULL,
  `male_beneficiaries` int(11) DEFAULT 0,
  `female_beneficiaries` int(11) DEFAULT 0,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL,
  `type_of_workers` varchar(255) DEFAULT NULL,
  `category` enum('Formation','Enhancement','Restoration') NOT NULL,
  `recipient_barangays` text DEFAULT NULL,
  `letter_of_intent_date` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_complied_by_proponent_nofo` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_check_release` date DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `check_date_issued` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date_issued` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_implemented` date DEFAULT NULL,
  `date_liquidated` date DEFAULT NULL,
  `liquidation_deadline` date DEFAULT NULL,
  `date_monitoring` date DEFAULT NULL,
  `source_of_funds` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','implemented','liquidated','monitored') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `control_number` (`control_number`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_proponents_type` (`proponent_type`),
  KEY `idx_proponents_district` (`district`),
  KEY `idx_proponents_status` (`status`),
  KEY `idx_proponents_control_number` (`control_number`),
  KEY `idx_proponents_date_approved` (`date_approved`),
  KEY `idx_proponents_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create proponent_associations table
CREATE TABLE `proponent_associations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_id` int(11) NOT NULL,
  `association_name` varchar(255) NOT NULL,
  `association_address` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proponent_id` (`proponent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create proponent_returns table
CREATE TABLE `proponent_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `returned_by` (`returned_by`),
  KEY `idx_proponent_returns_proponent` (`proponent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- STEP 3: IMPORT PRODUCTION DATA - PROPONENTS
-- ============================================================================
-- IMPORTANT: Insert your production proponent data below
-- Example format:
-- INSERT INTO `proponents` (...) VALUES
-- (1, 'LGU-associated', '2026-01-15', ..., 'Negros Occidental', ...),
-- (2, 'Non-LGU-associated', '2026-02-20', ..., 'Negros Oriental', ...);

-- [INSERT PROPONENTS DATA HERE]

-- ============================================================================
-- STEP 4: IMPORT PRODUCTION DATA - BENEFICIARIES
-- ============================================================================
-- IMPORTANT: Insert your production beneficiary data below
-- Example format:
-- INSERT INTO `beneficiaries` (...) VALUES
-- (1, 'Doe', 'John', 'A', NULL, 'Male', 'Brgy 1', 'Bacolod', 'Negros Occidental', ...),
-- (2, 'Smith', 'Jane', 'B', NULL, 'Female', 'Brgy 2', 'Dumaguete', 'Negros Oriental', ...);

-- [INSERT BENEFICIARIES DATA HERE]

-- ============================================================================
-- STEP 5: IMPORT PRODUCTION DATA - PROPONENT ASSOCIATIONS
-- ============================================================================
-- IMPORTANT: Insert your production association data below
-- Example format:
-- INSERT INTO `proponent_associations` (`id`, `proponent_id`, `association_name`, `association_address`, `sort_order`, `created_at`) VALUES
-- (1, 1, 'Farmers Association of Bacolod', 'Bacolod City', 0, '2026-01-15 10:00:00');

-- [INSERT PROPONENT ASSOCIATIONS DATA HERE]

-- ============================================================================
-- STEP 6: IMPORT PRODUCTION DATA - PROPONENT RETURNS
-- ============================================================================
-- IMPORTANT: Insert your production return records below
-- Example format:
-- INSERT INTO `proponent_returns` (`id`, `proponent_id`, `return_date`, `reason`, `returned_by`, `created_at`) VALUES
-- (1, 1, '2026-02-01', 'Incomplete documents', 1, '2026-02-01 14:30:00');

-- [INSERT PROPONENT RETURNS DATA HERE]

-- ============================================================================
-- STEP 7: IMPORT PRODUCTION DATA - ACTIVITY LOGS
-- ============================================================================
-- IMPORTANT: Insert your production activity log data below
-- Example format:
-- INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
-- (1, 1, 'login', 'users', 1, 'User logged in', '192.168.1.1', '2026-01-01 08:00:00');

-- [INSERT ACTIVITY LOGS DATA HERE]

-- ============================================================================
-- STEP 8: RESET AUTO_INCREMENT VALUES
-- ============================================================================
-- Reset auto-increment to maintain sequential IDs after data import
-- Update these values based on your actual data max IDs

-- ALTER TABLE `proponents` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `beneficiaries` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `proponent_associations` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `proponent_returns` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `activity_logs` AUTO_INCREMENT = [MAX_ID + 1];

-- ============================================================================
-- STEP 9: COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- DEPLOYMENT COMPLETE
-- ============================================================================
-- Tables created and data imported successfully.
-- Remember to:
-- 1. Verify all data was imported correctly
-- 2. Update AUTO_INCREMENT values to match your data
-- 3. Test application functionality
-- 4. Create a backup of the production database
-- ============================================================================
