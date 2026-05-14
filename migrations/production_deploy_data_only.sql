-- ============================================================================
-- DILP MONITORING SYSTEM - PRODUCTION DEPLOYMENT (DATA-ONLY)
-- ============================================================================
-- This script imports production data while preserving existing table structures
-- Tables: activity_logs, beneficiaries, proponents, proponent_associations,
--         proponent_returns
-- PRESERVES: users table (production accounts remain intact)
-- USE CASE: Existing production database with current schema - refresh data only
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
-- STEP 1: TRUNCATE EXISTING DATA (PRESERVE STRUCTURE)
-- ============================================================================
-- This removes all data while keeping table structures intact
-- Order matters: child tables first, then parent tables

TRUNCATE TABLE `proponent_returns`;
TRUNCATE TABLE `proponent_associations`;
TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `beneficiaries`;
TRUNCATE TABLE `proponents`;

-- ============================================================================
-- STEP 2: IMPORT PRODUCTION DATA - PROPONENTS
-- ============================================================================
-- IMPORTANT: Insert your production proponent data below
-- Example format:
-- INSERT INTO `proponents` (...) VALUES
-- (1, 'LGU-associated', '2026-01-15', ..., 'Negros Occidental', ...),
-- (2, 'Non-LGU-associated', '2026-02-20', ..., 'Negros Oriental', ...);

-- [INSERT PROPONENTS DATA HERE]

-- ============================================================================
-- STEP 3: IMPORT PRODUCTION DATA - BENEFICIARIES
-- ============================================================================
-- IMPORTANT: Insert your production beneficiary data below
-- Example format:
-- INSERT INTO `beneficiaries` (...) VALUES
-- (1, 'Doe', 'John', 'A', NULL, 'Male', 'Brgy 1', 'Bacolod', 'Negros Occidental', ...),
-- (2, 'Smith', 'Jane', 'B', NULL, 'Female', 'Brgy 2', 'Dumaguete', 'Negros Oriental', ...);

-- [INSERT BENEFICIARIES DATA HERE]

-- ============================================================================
-- STEP 4: IMPORT PRODUCTION DATA - PROPONENT ASSOCIATIONS
-- ============================================================================
-- IMPORTANT: Insert your production association data below
-- Example format:
-- INSERT INTO `proponent_associations` (`id`, `proponent_id`, `association_name`, `association_address`, `sort_order`, `created_at`) VALUES
-- (1, 1, 'Farmers Association of Bacolod', 'Bacolod City', 0, '2026-01-15 10:00:00');

-- [INSERT PROPONENT ASSOCIATIONS DATA HERE]

-- ============================================================================
-- STEP 5: IMPORT PRODUCTION DATA - PROPONENT RETURNS
-- ============================================================================
-- IMPORTANT: Insert your production return records below
-- Example format:
-- INSERT INTO `proponent_returns` (`id`, `proponent_id`, `return_date`, `reason`, `returned_by`, `created_at`) VALUES
-- (1, 1, '2026-02-01', 'Incomplete documents', 1, '2026-02-01 14:30:00');

-- [INSERT PROPONENT RETURNS DATA HERE]

-- ============================================================================
-- STEP 6: IMPORT PRODUCTION DATA - ACTIVITY LOGS
-- ============================================================================
-- IMPORTANT: Insert your production activity log data below
-- Example format:
-- INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
-- (1, 1, 'login', 'users', 1, 'User logged in', '192.168.1.1', '2026-01-01 08:00:00');

-- [INSERT ACTIVITY LOGS DATA HERE]

-- ============================================================================
-- STEP 7: RESET AUTO_INCREMENT VALUES
-- ============================================================================
-- Reset auto-increment to maintain sequential IDs after data import
-- Update these values based on your actual data max IDs

-- ALTER TABLE `proponents` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `beneficiaries` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `proponent_associations` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `proponent_returns` AUTO_INCREMENT = [MAX_ID + 1];
-- ALTER TABLE `activity_logs` AUTO_INCREMENT = [MAX_ID + 1];

-- ============================================================================
-- STEP 8: COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- DATA IMPORT COMPLETE
-- ============================================================================
-- Data imported successfully into existing table structures.
-- Remember to:
-- 1. Verify all data was imported correctly
-- 2. Update AUTO_INCREMENT values to match your data
-- 3. Test application functionality
-- 4. Create a backup of the production database
-- ============================================================================
