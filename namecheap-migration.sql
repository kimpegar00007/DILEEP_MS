-- =====================================================
-- DOLE DILP Monitoring System - Namecheap Migration
-- =====================================================
-- Database: dilp_monitoring
-- Version: 1.0.0
-- Date: March 2026
-- 
-- INSTRUCTIONS:
-- 1. Create database in Namecheap cPanel > MySQL Databases
-- 2. Import this file via phpMyAdmin
-- 3. Update .env file with Namecheap credentials
-- 4. Test the connection
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- DROP EXISTING TABLES (if re-running migration)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `fieldwork_schedule`;
DROP TABLE IF EXISTS `proponent_associations`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `beneficiaries`;
DROP TABLE IF EXISTS `proponents`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABLE: users
-- =====================================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','encoder','user') DEFAULT 'user',
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: beneficiaries
-- =====================================================

CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `type_of_worker` varchar(100) DEFAULT NULL,
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
  CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `beneficiaries_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: proponents
-- =====================================================

CREATE TABLE `proponents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_type` enum('LGU-associated','Non-LGU-associated') NOT NULL,
  `date_received` date DEFAULT NULL,
  `noted_findings` text DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `number_of_copies` int(11) DEFAULT NULL,
  `date_copies_received` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `proponent_name` varchar(255) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `number_of_associations` int(11) DEFAULT NULL,
  `total_beneficiaries` int(11) NOT NULL,
  `male_beneficiaries` int(11) DEFAULT 0,
  `female_beneficiaries` int(11) DEFAULT 0,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL,
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
  CONSTRAINT `proponents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `proponents_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: proponent_associations
-- =====================================================

CREATE TABLE `proponent_associations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_id` int(11) NOT NULL,
  `association_name` varchar(255) NOT NULL,
  `association_address` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proponent_id` (`proponent_id`),
  KEY `idx_proponent_associations_proponent` (`proponent_id`),
  CONSTRAINT `proponent_associations_ibfk_1` FOREIGN KEY (`proponent_id`) REFERENCES `proponents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: activity_logs
-- =====================================================

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
  KEY `idx_activity_logs_table` (`table_name`,`record_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: fieldwork_schedule
-- =====================================================

CREATE TABLE `fieldwork_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `assigned_user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','ongoing','completed','missed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fieldwork_status` (`status`),
  KEY `idx_fieldwork_start_date` (`start_date`),
  KEY `idx_fieldwork_end_date` (`end_date`),
  KEY `idx_fieldwork_assigned_user` (`assigned_user_id`),
  KEY `idx_fieldwork_created_by` (`created_by`),
  CONSTRAINT `fieldwork_schedule_ibfk_1` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fieldwork_schedule_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: migrations (for tracking applied migrations)
-- =====================================================

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TRIGGERS: Liquidation Deadline Calculation
-- =====================================================
-- Note: Some shared hosting providers may not support triggers.
-- If trigger creation fails, you'll need to calculate
-- liquidation_deadline manually in your application code.
-- =====================================================

DELIMITER $$

-- Trigger for INSERT operations
DROP TRIGGER IF EXISTS `calculate_liquidation_deadline`$$
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

-- Trigger for UPDATE operations
DROP TRIGGER IF EXISTS `update_liquidation_deadline`$$
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

-- =====================================================
-- DEFAULT DATA: Admin User
-- =====================================================
-- Default admin credentials:
-- Username: admin
-- Password: admin123
-- IMPORTANT: Change this password immediately after first login!
-- =====================================================

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@dilp.gov.ph', '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm', 'admin', 'System Administrator', 1, current_timestamp(), current_timestamp());

-- =====================================================
-- MIGRATION TRACKING
-- =====================================================

INSERT INTO `migrations` (`migration_name`, `applied_at`) VALUES
('create_base_schema', current_timestamp()),
('create_proponent_associations', current_timestamp()),
('create_fieldwork_schedule', current_timestamp()),
('create_indexes', current_timestamp()),
('create_triggers', current_timestamp()),
('insert_default_data', current_timestamp());

-- =====================================================
-- AUTO INCREMENT VALUES
-- =====================================================

ALTER TABLE `users` AUTO_INCREMENT = 2;
ALTER TABLE `beneficiaries` AUTO_INCREMENT = 1;
ALTER TABLE `proponents` AUTO_INCREMENT = 1;
ALTER TABLE `proponent_associations` AUTO_INCREMENT = 1;
ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `fieldwork_schedule` AUTO_INCREMENT = 1;
ALTER TABLE `migrations` AUTO_INCREMENT = 7;

-- =====================================================
-- COMMIT TRANSACTION
-- =====================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- Next Steps:
-- 1. Verify all tables were created successfully
-- 2. Check if triggers were created (run: SHOW TRIGGERS)
-- 3. Test login with admin/admin123
-- 4. Change admin password immediately
-- 5. Configure .env file with Namecheap database credentials
-- 6. Test application functionality
-- =====================================================
