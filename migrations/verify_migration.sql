-- ============================================================================
-- DILP MONITORING SYSTEM - MIGRATION VERIFICATION SCRIPT
-- ============================================================================
-- Purpose: Comprehensive validation of production schema migration
-- Date: May 13, 2026
-- Usage: Run after production_schema_migration.sql completes
-- ============================================================================

SET @db_name = DATABASE();

SELECT '============================================' AS '';
SELECT 'MIGRATION VERIFICATION REPORT' AS '';
SELECT CONCAT('Database: ', @db_name) AS '';
SELECT CONCAT('Timestamp: ', NOW()) AS '';
SELECT '============================================' AS '';

-- ============================================================================
-- SECTION 1: TABLE EXISTENCE VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '1. TABLE EXISTENCE CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check core tables
SELECT 
    'Core Tables' AS category,
    CASE 
        WHEN COUNT(*) = 6 THEN '✓ PASS'
        ELSE CONCAT('✗ FAIL - Found ', COUNT(*), ' of 6 expected')
    END AS status,
    GROUP_CONCAT(table_name ORDER BY table_name) AS tables_found
FROM information_schema.tables 
WHERE table_schema = @db_name
AND table_name IN ('users', 'beneficiaries', 'proponents', 'activity_logs', 'proponent_associations', 'proponent_returns');

-- Check new tables
SELECT 
    'New Tables' AS category,
    CASE 
        WHEN COUNT(*) = 4 THEN '✓ PASS'
        ELSE CONCAT('✗ FAIL - Found ', COUNT(*), ' of 4 expected')
    END AS status,
    GROUP_CONCAT(table_name ORDER BY table_name) AS tables_found
FROM information_schema.tables 
WHERE table_schema = @db_name
AND table_name IN ('provinces', 'org_chart', 'user_provinces', 'province_access_audit');

-- Check optional tables
SELECT 
    'Optional Tables' AS category,
    CASE 
        WHEN COUNT(*) >= 2 THEN '✓ PASS'
        ELSE CONCAT('⚠ WARNING - Found ', COUNT(*), ' of 2 expected')
    END AS status,
    GROUP_CONCAT(table_name ORDER BY table_name) AS tables_found
FROM information_schema.tables 
WHERE table_schema = @db_name
AND table_name IN ('fieldwork_schedule', 'system_settings');

-- ============================================================================
-- SECTION 2: COLUMN EXISTENCE VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '2. COLUMN EXISTENCE CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check users.role column
SELECT 
    'users.role ENUM' AS check_item,
    CASE 
        WHEN COLUMN_TYPE LIKE '%super_admin%' AND COLUMN_TYPE LIKE '%regional_director%' 
        THEN '✓ PASS - Contains super_admin and regional_director'
        ELSE CONCAT('✗ FAIL - ', COLUMN_TYPE)
    END AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'role';

-- Check users.province column
SELECT 
    'users.province' AS check_item,
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS - Column exists'
        ELSE '✗ FAIL - Column missing'
    END AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'province';

-- Check activity_logs.province column
SELECT 
    'activity_logs.province' AS check_item,
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS - Column exists'
        ELSE '⚠ WARNING - Column missing (optional)'
    END AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name
AND TABLE_NAME = 'activity_logs' 
AND COLUMN_NAME = 'province';

-- Check beneficiaries.province column
SELECT 
    'beneficiaries.province' AS check_item,
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS - Column exists'
        ELSE '✗ FAIL - Column missing'
    END AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name
AND TABLE_NAME = 'beneficiaries' 
AND COLUMN_NAME = 'province';

-- Check proponents.province column
SELECT 
    'proponents.province' AS check_item,
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS - Column exists'
        ELSE '✗ FAIL - Column missing'
    END AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name
AND TABLE_NAME = 'proponents' 
AND COLUMN_NAME = 'province';

-- ============================================================================
-- SECTION 3: FOREIGN KEY CONSTRAINTS VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '3. FOREIGN KEY CONSTRAINTS CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

SELECT 
    CONSTRAINT_NAME AS constraint_name,
    TABLE_NAME AS table_name,
    COLUMN_NAME AS column_name,
    REFERENCED_TABLE_NAME AS references_table,
    REFERENCED_COLUMN_NAME AS references_column,
    '✓' AS status
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @db_name
AND CONSTRAINT_NAME IN (
    'fk_user_provinces_user',
    'fk_user_provinces_province',
    'fk_province_audit_user'
)
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Count foreign keys
SELECT 
    'Foreign Keys Count' AS check_item,
    CASE 
        WHEN COUNT(*) >= 3 THEN CONCAT('✓ PASS - ', COUNT(*), ' constraints found')
        ELSE CONCAT('⚠ WARNING - Only ', COUNT(*), ' of 3 expected constraints')
    END AS status
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @db_name
AND CONSTRAINT_NAME IN (
    'fk_user_provinces_user',
    'fk_user_provinces_province',
    'fk_province_audit_user'
);

-- ============================================================================
-- SECTION 4: INDEX VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '4. INDEX VERIFICATION' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check critical indexes
SELECT 
    TABLE_NAME AS table_name,
    INDEX_NAME AS index_name,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns,
    '✓' AS status
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db_name
AND INDEX_NAME IN (
    'idx_users_province',
    'idx_beneficiaries_province',
    'idx_proponents_province',
    'idx_beneficiaries_province_status',
    'idx_proponents_province_status',
    'idx_tier_sort'
)
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================================================
-- SECTION 5: DATA INTEGRITY VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '5. DATA INTEGRITY CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check provinces populated
SELECT 
    'Provinces Table' AS check_item,
    CASE 
        WHEN COUNT(*) >= 3 THEN CONCAT('✓ PASS - ', COUNT(*), ' provinces found')
        ELSE CONCAT('✗ FAIL - Only ', COUNT(*), ' provinces')
    END AS status,
    GROUP_CONCAT(name ORDER BY display_order) AS province_list
FROM provinces;

-- Check users have province assignments
SELECT 
    'Users with Province' AS check_item,
    CASE 
        WHEN COUNT(*) = (SELECT COUNT(*) FROM users WHERE role != 'super_admin')
        THEN CONCAT('✓ PASS - All non-super_admin users have province')
        ELSE CONCAT('⚠ WARNING - ', COUNT(*), ' users with province of ', (SELECT COUNT(*) FROM users), ' total')
    END AS status
FROM users 
WHERE province IS NOT NULL OR role = 'super_admin';

-- Check super_admin has NULL province
SELECT 
    'Super Admin Province' AS check_item,
    CASE 
        WHEN COUNT(*) > 0 AND province IS NULL THEN '✓ PASS - super_admin has NULL province (all access)'
        WHEN COUNT(*) > 0 THEN CONCAT('⚠ WARNING - super_admin has province: ', province)
        ELSE '⚠ INFO - No super_admin user found'
    END AS status
FROM users 
WHERE role = 'super_admin'
LIMIT 1;

-- Check beneficiaries have province
SELECT 
    'Beneficiaries with Province' AS check_item,
    CASE 
        WHEN COUNT(*) = (SELECT COUNT(*) FROM beneficiaries)
        THEN CONCAT('✓ PASS - All ', COUNT(*), ' beneficiaries have province')
        ELSE CONCAT('✗ FAIL - ', COUNT(*), ' of ', (SELECT COUNT(*) FROM beneficiaries), ' have province')
    END AS status
FROM beneficiaries 
WHERE province IS NOT NULL AND province != '';

-- Check proponents have province
SELECT 
    'Proponents with Province' AS check_item,
    CASE 
        WHEN COUNT(*) = (SELECT COUNT(*) FROM proponents)
        THEN CONCAT('✓ PASS - All ', COUNT(*), ' proponents have province')
        ELSE CONCAT('✗ FAIL - ', COUNT(*), ' of ', (SELECT COUNT(*) FROM proponents), ' have province')
    END AS status
FROM proponents 
WHERE province IS NOT NULL AND province != '';

-- Check user-province mappings
SELECT 
    'User-Province Mappings' AS check_item,
    CASE 
        WHEN COUNT(*) >= (SELECT COUNT(*) FROM users)
        THEN CONCAT('✓ PASS - ', COUNT(*), ' mappings for ', (SELECT COUNT(*) FROM users), ' users')
        ELSE CONCAT('⚠ WARNING - Only ', COUNT(*), ' mappings for ', (SELECT COUNT(*) FROM users), ' users')
    END AS status
FROM user_provinces;

-- ============================================================================
-- SECTION 6: USER ROLE VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '6. USER ROLES VERIFICATION' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check admin user promotion
SELECT 
    'Admin User (ID=1)' AS check_item,
    CASE 
        WHEN role = 'super_admin' THEN '✓ PASS - Promoted to super_admin'
        ELSE CONCAT('✗ FAIL - Role is still: ', role)
    END AS status,
    username,
    role,
    COALESCE(province, 'NULL (all provinces)') AS province_access
FROM users 
WHERE id = 1;

-- Role distribution
SELECT 
    'Role Distribution' AS category,
    role,
    COUNT(*) AS user_count,
    '✓' AS status
FROM users
GROUP BY role
ORDER BY 
    FIELD(role, 'super_admin', 'regional_director', 'admin', 'encoder', 'user');

-- ============================================================================
-- SECTION 7: ORG CHART VERIFICATION
-- ============================================================================

SELECT '' AS '';
SELECT '7. ORGANIZATIONAL CHART CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check org chart populated
SELECT 
    'Org Chart Entries' AS check_item,
    CASE 
        WHEN COUNT(*) >= 1 THEN CONCAT('✓ PASS - ', COUNT(*), ' positions defined')
        ELSE '⚠ INFO - No org chart entries (can be added via UI)'
    END AS status
FROM org_chart;

-- Show org chart structure
SELECT 
    tier,
    sort_order,
    position_title,
    full_name,
    COALESCE(province, 'All Provinces') AS province,
    '✓' AS status
FROM org_chart
ORDER BY tier, sort_order
LIMIT 10;

-- ============================================================================
-- SECTION 8: DATA COUNT SUMMARY
-- ============================================================================

SELECT '' AS '';
SELECT '8. DATA COUNT SUMMARY' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

SELECT 'Users' AS table_name, COUNT(*) AS record_count FROM users
UNION ALL
SELECT 'Proponents', COUNT(*) FROM proponents
UNION ALL
SELECT 'Beneficiaries', COUNT(*) FROM beneficiaries
UNION ALL
SELECT 'Activity Logs', COUNT(*) FROM activity_logs
UNION ALL
SELECT 'Proponent Associations', COUNT(*) FROM proponent_associations
UNION ALL
SELECT 'Proponent Returns', COUNT(*) FROM proponent_returns
UNION ALL
SELECT 'Provinces', COUNT(*) FROM provinces
UNION ALL
SELECT 'User-Province Mappings', COUNT(*) FROM user_provinces
UNION ALL
SELECT 'Org Chart Positions', COUNT(*) FROM org_chart
UNION ALL
SELECT 'Province Access Audit', COUNT(*) FROM province_access_audit
UNION ALL
SELECT 'Fieldwork Schedule', COUNT(*) FROM fieldwork_schedule WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @db_name AND table_name = 'fieldwork_schedule')
UNION ALL
SELECT 'System Settings', COUNT(*) FROM system_settings WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @db_name AND table_name = 'system_settings');

-- ============================================================================
-- SECTION 9: DETAILED USER-PROVINCE MAPPING
-- ============================================================================

SELECT '' AS '';
SELECT '9. USER-PROVINCE MAPPING DETAILS' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

SELECT 
    u.id,
    u.username,
    u.role,
    COALESCE(u.province, 'NULL (all)') AS direct_province,
    GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS mapped_provinces,
    CASE 
        WHEN u.role = 'super_admin' THEN '✓ Super Admin (all access)'
        WHEN u.province IS NOT NULL THEN '✓ Province assigned'
        ELSE '⚠ No province'
    END AS status
FROM users u
LEFT JOIN user_provinces up ON u.id = up.user_id
LEFT JOIN provinces p ON up.province_id = p.id
GROUP BY u.id, u.username, u.role, u.province
ORDER BY u.id;

-- ============================================================================
-- SECTION 10: POTENTIAL ISSUES CHECK
-- ============================================================================

SELECT '' AS '';
SELECT '10. POTENTIAL ISSUES CHECK' AS 'VERIFICATION SECTION';
SELECT '-------------------------------------------' AS '';

-- Check for users without province (except super_admin)
SELECT 
    'Users without Province' AS issue_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS - No issues found'
        ELSE CONCAT('⚠ WARNING - ', COUNT(*), ' users without province')
    END AS status,
    GROUP_CONCAT(username) AS affected_users
FROM users 
WHERE province IS NULL AND role != 'super_admin' AND role != 'regional_director';

-- Check for beneficiaries without province
SELECT 
    'Beneficiaries without Province' AS issue_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS - No issues found'
        ELSE CONCAT('✗ FAIL - ', COUNT(*), ' beneficiaries without province')
    END AS status,
    COUNT(*) AS affected_count
FROM beneficiaries 
WHERE province IS NULL OR province = '';

-- Check for proponents without province
SELECT 
    'Proponents without Province' AS issue_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS - No issues found'
        ELSE CONCAT('✗ FAIL - ', COUNT(*), ' proponents without province')
    END AS status,
    COUNT(*) AS affected_count
FROM proponents 
WHERE province IS NULL OR province = '';

-- Check for orphaned user_provinces (users that don't exist)
SELECT 
    'Orphaned User-Province Mappings' AS issue_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS - No orphaned mappings'
        ELSE CONCAT('⚠ WARNING - ', COUNT(*), ' orphaned mappings')
    END AS status,
    COUNT(*) AS affected_count
FROM user_provinces up
LEFT JOIN users u ON up.user_id = u.id
WHERE u.id IS NULL;

-- ============================================================================
-- SECTION 11: MIGRATION COMPLETION STATUS
-- ============================================================================

SELECT '' AS '';
SELECT '============================================' AS '';
SELECT 'MIGRATION VERIFICATION COMPLETE' AS '';
SELECT CONCAT('Completed at: ', NOW()) AS '';
SELECT '============================================' AS '';

-- Overall status summary
SELECT 
    CASE 
        WHEN (
            (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = @db_name AND table_name IN ('provinces', 'org_chart', 'user_provinces', 'province_access_audit')) = 4
            AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'province') = 1
            AND (SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role') LIKE '%super_admin%'
            AND (SELECT COUNT(*) FROM users WHERE id = 1 AND role = 'super_admin') = 1
            AND (SELECT COUNT(*) FROM provinces) >= 3
        ) THEN '✓✓✓ MIGRATION SUCCESSFUL ✓✓✓'
        ELSE '⚠⚠⚠ MIGRATION INCOMPLETE - Review warnings above ⚠⚠⚠'
    END AS overall_status;

-- Next steps
SELECT '' AS '';
SELECT 'NEXT STEPS:' AS '';
SELECT '1. Review any warnings or failures above' AS action
UNION ALL
SELECT '2. Test user login (especially admin user)'
UNION ALL
SELECT '3. Verify province filtering in application'
UNION ALL
SELECT '4. Check for "Database Error" messages'
UNION ALL
SELECT '5. Create new database backup'
UNION ALL
SELECT '6. Monitor application logs for errors'
UNION ALL
SELECT '7. Test CRUD operations on proponents/beneficiaries';

SELECT '' AS '';
SELECT '============================================' AS '';
