-- ============================================================================
-- Cleanup Org Chart Duplicates Migration
-- Date: May 13, 2026
-- Purpose: Remove duplicate org chart entries and ensure proper structure
-- ============================================================================

-- Step 1: Verify current state before cleanup
SELECT 'BEFORE CLEANUP - Current org_chart entries:' as status;
SELECT tier, province, COUNT(*) as count 
FROM org_chart 
GROUP BY tier, province 
ORDER BY tier, province;

SELECT 'Total entries before cleanup:' as status, COUNT(*) as total FROM org_chart;

-- Step 2: Backup current data (just in case)
CREATE TABLE IF NOT EXISTS org_chart_backup_20260513 AS SELECT * FROM org_chart;

-- Step 3: Remove duplicate entries
-- Keep only the core 7 entries (IDs 1-7)
-- This removes any duplicates created by the faulty seeding logic
DELETE FROM org_chart WHERE id > 7;

-- Step 4: Ensure Tier 0 has correct structure
-- Tier 0 (Regional Director) should have province = NULL
UPDATE org_chart SET province = NULL WHERE tier = 0;

-- Step 5: Verify cleanup results
SELECT 'AFTER CLEANUP - Remaining org_chart entries:' as status;
SELECT tier, province, COUNT(*) as count 
FROM org_chart 
GROUP BY tier, province 
ORDER BY tier, province;

SELECT 'Total entries after cleanup:' as status, COUNT(*) as total FROM org_chart;

-- Step 6: Verify expected structure
SELECT 'Expected structure verification:' as status;
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM org_chart WHERE tier = 0) = 1 THEN 'PASS'
        ELSE 'FAIL'
    END as 'Tier 0 has exactly 1 entry',
    CASE 
        WHEN (SELECT COUNT(*) FROM org_chart WHERE tier = 1) = 3 THEN 'PASS'
        ELSE 'FAIL'
    END as 'Tier 1 has exactly 3 entries',
    CASE 
        WHEN (SELECT COUNT(*) FROM org_chart WHERE tier = 2) = 3 THEN 'PASS'
        ELSE 'FAIL'
    END as 'Tier 2 has exactly 3 entries',
    CASE 
        WHEN (SELECT COUNT(*) FROM org_chart WHERE tier = 0 AND province IS NULL) = 1 THEN 'PASS'
        ELSE 'FAIL'
    END as 'Tier 0 has NULL province';

-- Step 7: Reset AUTO_INCREMENT to prevent gaps
ALTER TABLE org_chart AUTO_INCREMENT = 8;

SELECT 'Cleanup completed successfully!' as status;
