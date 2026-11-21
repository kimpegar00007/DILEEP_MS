-- Migration: Shared Budget module
-- Some livelihood projects have several Proponents and Beneficiaries that share
-- one budget. The budget is split into a peso share per party, and each share is
-- stored in the amount column the record already has (proponents.amount and
-- beneficiaries.amount_worth), so every existing total still counts the money once.
--
--   * shared_budgets            : one row per shared budget (title, province, total)
--   * proponents.shared_budget_id / beneficiaries.shared_budget_id : nullable link
--
-- Idempotent: safe to run more than once (works on MariaDB and MySQL 8).

CREATE TABLE IF NOT EXISTS `shared_budgets` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `province` VARCHAR(100) NOT NULL,
    `total_budget` DECIMAL(15,2) NOT NULL,
    `source_of_funds` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_shared_budgets_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- proponents.shared_budget_id
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'proponents'
                   AND COLUMN_NAME = 'shared_budget_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE proponents ADD COLUMN shared_budget_id INT(11) UNSIGNED NULL DEFAULT NULL',
    'SELECT "proponents.shared_budget_id already exists, skipping..." AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'proponents'
                   AND INDEX_NAME = 'idx_proponents_shared_budget_id');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE proponents ADD INDEX idx_proponents_shared_budget_id (shared_budget_id)',
    'SELECT "idx_proponents_shared_budget_id already exists, skipping..." AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- beneficiaries.shared_budget_id
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'beneficiaries'
                   AND COLUMN_NAME = 'shared_budget_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE beneficiaries ADD COLUMN shared_budget_id INT(11) UNSIGNED NULL DEFAULT NULL',
    'SELECT "beneficiaries.shared_budget_id already exists, skipping..." AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'beneficiaries'
                   AND INDEX_NAME = 'idx_beneficiaries_shared_budget_id');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE beneficiaries ADD INDEX idx_beneficiaries_shared_budget_id (shared_budget_id)',
    'SELECT "idx_beneficiaries_shared_budget_id already exists, skipping..." AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
