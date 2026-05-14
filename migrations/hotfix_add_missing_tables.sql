-- ============================================================================
-- HOTFIX: Add missing optional tables
-- ============================================================================
-- Creates: provinces, user_provinces, province_access_audit
-- These tables are referenced in Auth.php but may not be critical
-- Date: May 13, 2026
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;

-- Create provinces reference table
CREATE TABLE IF NOT EXISTS `provinces` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE NOT NULL COMMENT 'Province code: NO, NOR, SIQ',
    `name` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Full province name',
    `region_code` VARCHAR(10) COMMENT 'Region code',
    `region_name` VARCHAR(100) COMMENT 'Region name',
    `is_active` BOOLEAN DEFAULT TRUE,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_code` (`code`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Populate provinces
INSERT IGNORE INTO `provinces` (`code`, `name`, `region_code`, `region_name`, `is_active`, `display_order`)
VALUES
    ('NO', 'Negros Occidental', 'VI', 'Western Visayas', TRUE, 1),
    ('NOR', 'Negros Oriental', 'VII', 'Central Visayas', TRUE, 2),
    ('SIQ', 'Siquijor', 'VII', 'Central Visayas', TRUE, 3);

-- Create user_provinces mapping table
CREATE TABLE IF NOT EXISTS `user_provinces` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `province_id` INT NOT NULL,
    `role` ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user') DEFAULT 'user',
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_province` (`user_id`, `province_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_province` (`province_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create province access audit table
CREATE TABLE IF NOT EXISTS `province_access_audit` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` INT,
    `province_accessed` VARCHAR(100),
    `allowed` BOOLEAN DEFAULT TRUE,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_province` (`province_accessed`),
    KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Populate user_provinces for existing users
INSERT IGNORE INTO `user_provinces` (`user_id`, `province_id`, `role`, `is_default`)
SELECT 
    u.`id`,
    p.`id` AS province_id,
    u.`role`,
    TRUE
FROM `users` u
CROSS JOIN `provinces` p
WHERE u.`province` = p.`name`
AND NOT EXISTS (
    SELECT 1 FROM `user_provinces` up 
    WHERE up.`user_id` = u.`id` AND up.`province_id` = p.`id`
);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- Verification
SELECT 'Tables created successfully' AS status;
SELECT 'Provinces:' AS '';
SELECT * FROM provinces;
SELECT 'User-Province Mappings:' AS '';
SELECT u.username, u.role, p.name AS province 
FROM user_provinces up
JOIN users u ON up.user_id = u.id
JOIN provinces p ON up.province_id = p.id
ORDER BY u.username;
