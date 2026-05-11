-- =====================================================
-- DOLE DILP Multi-Province Support Migration
-- =====================================================
-- Database: dilp_monitoring
-- Created: May 11, 2026
-- Purpose: Add province-based data segregation for 3 provinces
--
-- IMPORTANT: Backup database before running this script!
-- =====================================================

-- ============================================================================
-- STEP 1: Create provinces reference table
-- ============================================================================
CREATE TABLE IF NOT EXISTS provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Province code: NO, AO, EB, etc.',
    name VARCHAR(100) UNIQUE NOT NULL COMMENT 'Full province name',
    region_code VARCHAR(10) COMMENT 'Region code: VI, VII, VIII, etc.',
    region_name VARCHAR(100) COMMENT 'Region name for reference',
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_code (code),
    KEY idx_name (name),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Reference table for provinces';

-- ============================================================================
-- STEP 2: Create user-province mapping table
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    province_id INT NOT NULL,
    role ENUM('admin', 'encoder', 'user') DEFAULT 'user' COMMENT 'Role in this specific province',
    is_default BOOLEAN DEFAULT FALSE COMMENT 'User default province',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_province (user_id, province_id),
    KEY idx_user (user_id),
    KEY idx_province (province_id),
    KEY idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maps users to provinces they can access';

-- ============================================================================
-- STEP 3: Add province-related columns to users table
-- ============================================================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS default_province_id INT COMMENT 'User default province for UI context';
ALTER TABLE users ADD CONSTRAINT fk_users_default_province
    FOREIGN KEY (default_province_id) REFERENCES provinces(id) ON DELETE SET NULL;

-- ============================================================================
-- STEP 4: Ensure province columns exist in beneficiaries table
-- ============================================================================
ALTER TABLE beneficiaries
MODIFY COLUMN IF EXISTS province VARCHAR(100) DEFAULT 'Negros Occidental'
COMMENT 'Province where beneficiary is located';

-- Add if doesn't exist
ALTER TABLE beneficiaries ADD COLUMN IF NOT EXISTS province VARCHAR(100)
DEFAULT 'Negros Occidental' COMMENT 'Province where beneficiary is located';

-- ============================================================================
-- STEP 5: Ensure province column exists in proponents table
-- ============================================================================
ALTER TABLE proponents
MODIFY COLUMN IF EXISTS province VARCHAR(100) DEFAULT 'Negros Occidental'
COMMENT 'Province where proponent is located';

-- Add if doesn't exist
ALTER TABLE proponents ADD COLUMN IF NOT EXISTS province VARCHAR(100)
DEFAULT 'Negros Occidental' COMMENT 'Province where proponent is located';

-- ============================================================================
-- STEP 6: Create performance indexes for province-based queries
-- ============================================================================
-- Beneficiaries indexes
CREATE INDEX IF NOT EXISTS idx_beneficiaries_province
    ON beneficiaries(province);

CREATE INDEX IF NOT EXISTS idx_beneficiaries_province_status
    ON beneficiaries(province, status);

CREATE INDEX IF NOT EXISTS idx_beneficiaries_province_municipality
    ON beneficiaries(province, municipality);

-- Proponents indexes
CREATE INDEX IF NOT EXISTS idx_proponents_province
    ON proponents(province);

CREATE INDEX IF NOT EXISTS idx_proponents_province_status
    ON proponents(province, status);

CREATE INDEX IF NOT EXISTS idx_proponents_province_type
    ON proponents(province, proponent_type);

-- User provinces indexes
CREATE INDEX IF NOT EXISTS idx_user_provinces_user
    ON user_provinces(user_id);

CREATE INDEX IF NOT EXISTS idx_user_provinces_province
    ON user_provinces(province_id);

-- ============================================================================
-- STEP 7: Populate provinces table (customize for your regions)
-- ============================================================================
INSERT INTO provinces (code, name, region_code, region_name, is_active, display_order)
VALUES
    ('NO', 'Negros Occidental', 'VI', 'Western Visayas', TRUE, 1),
    ('AO', 'Antique', 'VI', 'Western Visayas', TRUE, 2),
    ('EB', 'Eastern Samar', 'VIII', 'Eastern Visayas', TRUE, 3)
ON DUPLICATE KEY UPDATE
    is_active = VALUES(is_active),
    region_name = VALUES(region_name),
    display_order = VALUES(display_order);

-- ============================================================================
-- STEP 8: Backfill existing users with default province (Negros Occidental)
-- ============================================================================
-- Get province ID
SET @default_province_id = (SELECT id FROM provinces WHERE name = 'Negros Occidental' LIMIT 1);

-- Create temporary table to hold user IDs that need assignment
CREATE TEMPORARY TABLE temp_users_to_assign AS
SELECT u.id
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM user_provinces up
    WHERE up.user_id = u.id
    AND up.province_id = @default_province_id
);

-- Assign all users to Negros Occidental (their current operating province)
INSERT INTO user_provinces (user_id, province_id, role, is_default)
SELECT u.id, @default_province_id, u.role, TRUE
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM user_provinces up
    WHERE up.user_id = u.id
);

-- ============================================================================
-- STEP 9: Set default province for existing users
-- ============================================================================
UPDATE users u
SET default_province_id = @default_province_id
WHERE default_province_id IS NULL;

-- ============================================================================
-- STEP 10: Update beneficiaries to have correct province (data integrity)
-- ============================================================================
-- This query assumes you want to keep all current data as Negros Occidental
-- Modify if you have different province assignments
UPDATE beneficiaries
SET province = 'Negros Occidental'
WHERE province IS NULL OR province = '';

-- ============================================================================
-- STEP 11: Update proponents to have correct province (data integrity)
-- ============================================================================
UPDATE proponents
SET province = 'Negros Occidental'
WHERE province IS NULL OR province = '';

-- ============================================================================
-- STEP 12: Create audit table for security monitoring
-- ============================================================================
CREATE TABLE IF NOT EXISTS province_access_audit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'create, read, update, delete',
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    province_accessed VARCHAR(100),
    allowed BOOLEAN DEFAULT TRUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_user (user_id),
    KEY idx_province (province_accessed),
    KEY idx_timestamp (timestamp),
    KEY idx_allowed (allowed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Audit trail for province-based access control';

-- ============================================================================
-- STEP 13: Add province column to activity logs (optional but recommended)
-- ============================================================================
ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS province VARCHAR(100)
COMMENT 'Province context of the activity';

-- ============================================================================
-- STEP 14: Create view for easier province-based queries (optional)
-- ============================================================================
DROP VIEW IF EXISTS vw_beneficiaries_current_user;
CREATE VIEW vw_beneficiaries_current_user AS
SELECT b.*
FROM beneficiaries b
WHERE b.province IN (
    SELECT p.name
    FROM user_provinces up
    JOIN provinces p ON up.province_id = p.id
    WHERE up.user_id = @current_user_id
)
COMMENT 'View filtered beneficiaries by current user provinces';

DROP VIEW IF EXISTS vw_proponents_current_user;
CREATE VIEW vw_proponents_current_user AS
SELECT pr.*
FROM proponents pr
WHERE pr.province IN (
    SELECT p.name
    FROM user_provinces up
    JOIN provinces p ON up.province_id = p.id
    WHERE up.user_id = @current_user_id
)
COMMENT 'View filtered proponents by current user provinces';

-- ============================================================================
-- STEP 15: Data Verification Queries (Run these to verify success)
-- ============================================================================

-- Verify provinces were created
SELECT COUNT(*) as province_count FROM provinces;
-- Expected: 3

-- Verify users were assigned to provinces
SELECT COUNT(*) as user_province_count FROM user_provinces;
-- Expected: Should equal or exceed number of users

-- Verify no orphaned beneficiaries
SELECT COUNT(*) as orphaned_beneficiaries FROM beneficiaries WHERE province IS NULL OR province = '';
-- Expected: 0

-- Verify no orphaned proponents
SELECT COUNT(*) as orphaned_proponents FROM proponents WHERE province IS NULL OR province = '';
-- Expected: 0

-- Show users and their assigned provinces
SELECT u.id, u.username, u.role, GROUP_CONCAT(p.name) as provinces
FROM users u
LEFT JOIN user_provinces up ON u.id = up.user_id
LEFT JOIN provinces p ON up.province_id = p.id
GROUP BY u.id
ORDER BY u.username;

-- ============================================================================
-- STEP 16: Index Statistics (Run after data loads)
-- ============================================================================
-- Optimize tables for better performance
ANALYZE TABLE beneficiaries;
ANALYZE TABLE proponents;
ANALYZE TABLE users;
ANALYZE TABLE user_provinces;
ANALYZE TABLE provinces;

-- ============================================================================
-- NOTES FOR IMPLEMENTATION
-- ============================================================================
/*
BEFORE RUNNING THIS SCRIPT:
1. Backup your database: mysqldump dilp_monitoring > backup_$(date +%Y%m%d).sql
2. Test on a development copy first
3. Verify you have appropriate backup/restore procedures
4. Ensure all users understand the changes
5. Plan maintenance window if production system

AFTER RUNNING THIS SCRIPT:
1. Run verification queries above
2. Check application logs for errors
3. Test province filtering with a test user
4. Verify admin can see all provinces
5. Test multi-province user switching
6. Monitor performance of province-filtered queries

IMPORTANT REMINDERS:
- Every SELECT query must include province filtering for non-admins
- Use prepared statements to prevent SQL injection
- Test cross-province access attempts
- Log all province-related operations
- Review this code regularly for security updates

IF ISSUES OCCUR:
1. Check error logs: tail -f /var/log/mysql/error.log
2. Verify foreign key constraints: SHOW ENGINE INNODB STATUS;
3. Check disk space: SELECT * FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = 'dilp_monitoring';
4. Rollback if necessary: Restore from backup created in step 1

ROLLBACK INSTRUCTIONS:
If this migration causes issues:
1. Stop the application
2. Restore from backup: mysql dilp_monitoring < backup_YYYYMMDD.sql
3. Revert application code to previous version
4. Investigate the issue
5. Contact database administrator
*/

-- ============================================================================
-- Migration Complete
-- =====================================================
-- Version: 1.0
-- Date: May 11, 2026
-- Status: Ready for deployment
-- =====================================================
