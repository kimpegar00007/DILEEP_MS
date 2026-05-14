-- ============================================================================
-- Update Org Chart Constraints Migration
-- Date: May 13, 2026
-- Purpose: Add database constraints to prevent future data integrity issues
-- ============================================================================

-- Step 1: Add CHECK constraint for tier values (0-3 only)
-- Note: MySQL 8.0.16+ supports CHECK constraints
-- Drop existing constraint if it exists (idempotent)
SET @constraint_exists = (SELECT COUNT(*) 
                          FROM information_schema.TABLE_CONSTRAINTS 
                          WHERE CONSTRAINT_SCHEMA = 'dilp_monitoring' 
                          AND TABLE_NAME = 'org_chart' 
                          AND CONSTRAINT_NAME = 'chk_tier_range');

SET @drop_sql = IF(@constraint_exists > 0, 
                   'ALTER TABLE org_chart DROP CONSTRAINT chk_tier_range', 
                   'SELECT "Constraint does not exist, skipping drop" as status');

PREPARE stmt FROM @drop_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now add the constraint
ALTER TABLE org_chart 
ADD CONSTRAINT chk_tier_range 
CHECK (tier >= 0 AND tier <= 3);

-- Step 2: Add index on tier and province for better query performance
CREATE INDEX IF NOT EXISTS idx_tier_province ON org_chart(tier, province);

-- Step 3: Ensure tier and sort_order are NOT NULL
ALTER TABLE org_chart 
MODIFY COLUMN tier TINYINT NOT NULL DEFAULT 0,
MODIFY COLUMN sort_order TINYINT NOT NULL DEFAULT 0;

-- Step 4: Add comment to document tier limits
ALTER TABLE org_chart COMMENT = 'Organizational chart with tier limits: Tier 0 (max 1), Tier 1-2 (max 5 per province), Tier 3 (max 3 per province)';

-- Verify constraints
SELECT 'Constraints added successfully!' as status;
SHOW CREATE TABLE org_chart;
