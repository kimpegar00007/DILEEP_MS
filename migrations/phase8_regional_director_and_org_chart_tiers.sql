-- Phase 8 Migration: Regional Director Role + Org Chart Multi-Person Tiers
-- Date: May 12, 2026
-- Description:
--   1. Adds 'regional_director' to the users.role ENUM
--   2. Restructures org_chart to support multiple people per tier (max 5)

-- ============================================================
-- PART 1: Add regional_director to users.role ENUM
-- ============================================================

-- Modify the role column to include regional_director
ALTER TABLE users
    MODIFY COLUMN role ENUM('super_admin', 'admin', 'regional_director', 'encoder', 'user')
    NOT NULL DEFAULT 'user';

-- ============================================================
-- PART 2: Restructure org_chart for multi-person tiers
-- ============================================================

-- Step 1: Add tier column (0=Regional Dir, 1=Field Office Head, 2=DILEEP Focal, 3=Staff Row)
ALTER TABLE org_chart
    ADD COLUMN tier TINYINT NOT NULL DEFAULT 0 COMMENT '0=top, 1,2=middle tiers, 3=staff row' AFTER id,
    ADD COLUMN sort_order TINYINT NOT NULL DEFAULT 0 COMMENT 'Order within a tier (0-4)' AFTER tier;

-- Step 2: Drop the UNIQUE constraint on position_order (allows multiple rows per tier)
ALTER TABLE org_chart DROP INDEX position_order;

-- Step 3: Migrate existing 4 rows to new tier structure
UPDATE org_chart SET tier = 0, sort_order = 0 WHERE position_order = 1;
UPDATE org_chart SET tier = 1, sort_order = 0 WHERE position_order = 2;
UPDATE org_chart SET tier = 2, sort_order = 0 WHERE position_order = 3;
UPDATE org_chart SET tier = 3, sort_order = 0 WHERE position_order = 4;

-- Step 4: Add index for efficient tier-based queries
ALTER TABLE org_chart ADD INDEX idx_tier_sort (tier, sort_order);

-- Step 5: Update position_order comment to reflect new meaning
ALTER TABLE org_chart
    MODIFY COLUMN position_order INT NOT NULL DEFAULT 0
    COMMENT 'Legacy sort field; use tier+sort_order for display';

-- ============================================================
-- VERIFICATION QUERIES (run to confirm migration success)
-- ============================================================
-- SELECT * FROM org_chart ORDER BY tier, sort_order;
-- SHOW COLUMNS FROM users LIKE 'role';
