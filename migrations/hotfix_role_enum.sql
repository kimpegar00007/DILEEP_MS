-- ============================================================================
-- HOTFIX: Update users.role ENUM to include regional_director
-- ============================================================================
-- Issue: Database has enum('admin','encoder','user','super_admin')
-- Required: enum('super_admin','admin','regional_director','encoder','user')
-- Date: May 13, 2026
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Backup current role values
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role_backup` VARCHAR(50);
UPDATE `users` SET `role_backup` = `role`;

-- Update the ENUM to include regional_director in correct order
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user') 
NOT NULL DEFAULT 'user';

-- Verify the change
SELECT 'Role ENUM updated successfully' AS status;
SHOW COLUMNS FROM users LIKE 'role';

-- Drop backup column
ALTER TABLE `users` DROP COLUMN IF EXISTS `role_backup`;

COMMIT;

-- Verification
SELECT 'Verification: All users and their roles' AS '';
SELECT id, username, role, province FROM users ORDER BY id;
