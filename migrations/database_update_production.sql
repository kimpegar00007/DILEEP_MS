-- DOLE DILP Monitoring System - Production Database Update Script
-- Database: dilp_monitoring
-- Purpose: Safe migration for production databases (handles both existing and new tables)
-- This script can be used for:
--   1. Updating existing production databases (adds missing columns/tables/indexes)
--   2. Creating missing tables in partially migrated databases
-- For fresh installs on empty databases, use database_migrations.sql instead
-- Date: May 12, 2026

-- ============================================================================
-- THIS SCRIPT IS IDEMPOTENT - Safe to run multiple times
-- It will create missing tables and add missing indexes without modifying existing data
-- ============================================================================

-- ----------------------------------------------------------------------------
-- CREATE CORE TABLES (IF NOT EXISTS)
-- ----------------------------------------------------------------------------

-- Create Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'encoder', 'user', 'super_admin') NOT NULL DEFAULT 'user',
    province ENUM('Negros Occidental', 'Negros Oriental', 'Siquijor') DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Beneficiaries table
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    suffix VARCHAR(20),
    gender ENUM('Male', 'Female') NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    province ENUM('Negros Occidental', 'Negros Oriental', 'Siquijor') DEFAULT NULL,
    contact_number VARCHAR(20),
    project_name VARCHAR(255) NOT NULL,
    type_of_worker VARCHAR(100),
    amount_worth DECIMAL(15,2) NOT NULL,
    noted_findings TEXT,
    date_complied_by_proponent DATE,
    date_forwarded_to_ro6 DATE,
    rpmt_findings TEXT,
    date_approved DATE,
    date_forwarded_to_nofo DATE,
    date_turnover DATE,
    date_monitoring DATE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending', 'approved', 'implemented', 'monitored') DEFAULT 'pending',
    source_of_funds VARCHAR(255) DEFAULT NULL,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create Proponents table
CREATE TABLE IF NOT EXISTS proponents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proponent_type ENUM('LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others') NOT NULL,
    date_received DATE,
    noted_findings TEXT,
    control_number VARCHAR(50) UNIQUE,
    number_of_copies INT,
    date_copies_received DATE,
    district VARCHAR(100),
    province ENUM('Negros Occidental', 'Negros Oriental', 'Siquijor') DEFAULT NULL,
    proponent_name VARCHAR(255) NOT NULL,
    project_title VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    number_of_associations INT,
    total_beneficiaries INT NOT NULL,
    male_beneficiaries INT DEFAULT 0,
    female_beneficiaries INT DEFAULT 0,
    type_of_beneficiaries VARCHAR(255),
    category ENUM('Formation', 'Enhancement', 'Restoration') NOT NULL,
    recipient_barangays TEXT,
    letter_of_intent_date DATE,
    date_forwarded_to_ro6 DATE,
    rpmt_findings TEXT,
    date_complied_by_proponent DATE,
    date_complied_by_proponent_nofo DATE,
    date_forwarded_to_nofo DATE,
    date_approved DATE,
    date_check_release DATE,
    check_number VARCHAR(50),
    check_date_issued DATE,
    or_number VARCHAR(50),
    or_date_issued DATE,
    date_turnover DATE,
    date_implemented DATE,
    date_liquidated DATE,
    liquidation_deadline DATE,
    date_monitoring DATE,
    source_of_funds VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending', 'approved', 'implemented', 'liquidated', 'monitored') DEFAULT 'pending',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create Activity Log table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create Proponent Associations table
CREATE TABLE IF NOT EXISTS proponent_associations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proponent_id INT NOT NULL,
    association_name VARCHAR(255) NOT NULL,
    association_address VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
);

-- Create Fieldwork Schedule table
CREATE TABLE IF NOT EXISTS fieldwork_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(500),
    province ENUM('Negros Occidental', 'Negros Oriental', 'Siquijor') DEFAULT NULL,
    assigned_user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('pending', 'ongoing', 'completed', 'missed') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ----------------------------------------------------------------------------
-- ADD MISSING INDEXES (with error handling for duplicates)
-- ----------------------------------------------------------------------------

-- Helper procedure to create index if not exists
DELIMITER //
CREATE PROCEDURE CreateIndexIfNotExists(
    IN p_table_name VARCHAR(100),
    IN p_index_name VARCHAR(100),
    IN p_column_list VARCHAR(255)
)
BEGIN
    SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
        AND table_name = p_table_name 
        AND index_name = p_index_name);
    
    IF @idx_exists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', p_index_name, ' ON ', p_table_name, ' (', p_column_list, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Create indexes
CALL CreateIndexIfNotExists('beneficiaries', 'idx_beneficiaries_municipality', 'municipality');
CALL CreateIndexIfNotExists('beneficiaries', 'idx_beneficiaries_barangay', 'barangay');
CALL CreateIndexIfNotExists('beneficiaries', 'idx_beneficiaries_status', 'status');
CALL CreateIndexIfNotExists('beneficiaries', 'idx_beneficiaries_date_approved', 'date_approved');
CALL CreateIndexIfNotExists('beneficiaries', 'idx_beneficiaries_province', 'province');

CALL CreateIndexIfNotExists('proponents', 'idx_proponents_type', 'proponent_type');
CALL CreateIndexIfNotExists('proponents', 'idx_proponents_district', 'district');
CALL CreateIndexIfNotExists('proponents', 'idx_proponents_status', 'status');
CALL CreateIndexIfNotExists('proponents', 'idx_proponents_control_number', 'control_number');
CALL CreateIndexIfNotExists('proponents', 'idx_proponents_date_approved', 'date_approved');
CALL CreateIndexIfNotExists('proponents', 'idx_proponents_province', 'province');

CALL CreateIndexIfNotExists('proponent_associations', 'idx_proponent_associations_proponent', 'proponent_id');

CALL CreateIndexIfNotExists('activity_logs', 'idx_activity_logs_user', 'user_id');
CALL CreateIndexIfNotExists('activity_logs', 'idx_activity_logs_table', 'table_name, record_id');

CALL CreateIndexIfNotExists('users', 'idx_users_province', 'province');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_status', 'status');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_start_date', 'start_date');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_end_date', 'end_date');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_assigned_user', 'assigned_user_id');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_created_by', 'created_by');
CALL CreateIndexIfNotExists('fieldwork_schedule', 'idx_fieldwork_province', 'province');

-- Drop the helper procedure
DROP PROCEDURE IF EXISTS CreateIndexIfNotExists;

-- ----------------------------------------------------------------------------
-- INSERT DEFAULT ADMIN USER (only if no super_admin exists)
-- ----------------------------------------------------------------------------

INSERT INTO users (username, email, password, role, province, full_name)
SELECT 'admin', 'admin@dilp.gov.ph', '$2y$12$cxubeCJxgDoHaci9zO4Ud.b7uJ7PQQpWfOafrfLY2efdUQGNuRDLi', 'super_admin', NULL, 'System Administrator'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'super_admin' LIMIT 1);

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
