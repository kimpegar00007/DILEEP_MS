-- ============================================================================
-- Production Org Chart Migration
-- ============================================================================
-- Date: May 12, 2026
-- Description: Complete org_chart table setup for production deployment
--              Consolidates phase4 + phase8 + phase9 migrations into a single
--              production-ready migration with multi-person tier support and
--              multi-province functionality.
--
-- Features:
--   - Multi-person tiers: Up to 5 people per tier per province
--   - Multi-province support: Negros Occidental, Negros Oriental, Siquijor
--   - 4 organizational tiers (0-3):
--       Tier 0: Regional Director (Negros Occidental only)
--       Tier 1: Field Office Head
--       Tier 2: DILEEP Focal
--       Tier 3: LDS / Office Staff / IT
--
-- Usage: Run this migration on production database to create org_chart table
--        with complete structure and default seed data.
-- ============================================================================

-- ============================================================================
-- PART 1: Create org_chart table with complete schema
-- ============================================================================

CREATE TABLE IF NOT EXISTS org_chart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental' 
        COMMENT 'Province this org chart entry belongs to (Negros Occidental, Negros Oriental, Siquijor)',
    tier TINYINT NOT NULL DEFAULT 0 
        COMMENT 'Organizational tier: 0=Regional Director, 1=Field Office Head, 2=DILEEP Focal, 3=Staff',
    sort_order TINYINT NOT NULL DEFAULT 0 
        COMMENT 'Order within a tier (0-4, max 5 people per tier)',
    position_title VARCHAR(255) NOT NULL 
        COMMENT 'Position title (e.g., Regional Director, DILEEP Focal)',
    person_name VARCHAR(255) DEFAULT NULL 
        COMMENT 'Name of person in this position (NULL = Vacant)',
    position_order INT NOT NULL DEFAULT 0 
        COMMENT 'Legacy sort field for backward compatibility; use tier+sort_order for display',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tier_sort (tier, sort_order),
    INDEX idx_org_chart_province (province)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
  COMMENT='DILEEP-NOCFO Multi-Province Organizational Chart';

-- ============================================================================
-- PART 2: Seed default organizational structure for all provinces
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Negros Occidental (4 tiers including Regional Director)
-- ----------------------------------------------------------------------------

-- Tier 0: Regional Director (top leadership)
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Occidental', 0, 0, 'Regional Director', NULL, 10
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Occidental' AND tier = 0 AND sort_order = 0
)
LIMIT 1;

-- Tier 1: Field Office Head (management)
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Occidental', 1, 0, 'Field Office Head', NULL, 20
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Occidental' AND tier = 1 AND sort_order = 0
)
LIMIT 1;

-- Tier 2: DILEEP Focal (technical staff)
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Occidental', 2, 0, 'DILEEP Focal', NULL, 30
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Occidental' AND tier = 2 AND sort_order = 0
)
LIMIT 1;

-- Tier 3: Support Staff
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Occidental', 3, 0, 'LDS / Office Staff / IT', NULL, 40
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Occidental' AND tier = 3 AND sort_order = 0
)
LIMIT 1;

-- ----------------------------------------------------------------------------
-- Negros Oriental (3 tiers - no Regional Director)
-- ----------------------------------------------------------------------------

-- Tier 0: Field Office Head (top position for this province)
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 0, 0, 'Field Office Head', NULL, 100
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 0 AND sort_order = 0
)
LIMIT 1;

-- Tier 1: DILEEP Focal
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 1, 0, 'DILEEP Focal', NULL, 110
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 1 AND sort_order = 0
)
LIMIT 1;

-- Tier 2: Support Staff
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 2, 0, 'LDS / Office Staff', NULL, 120
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 2 AND sort_order = 0
)
LIMIT 1;

-- ----------------------------------------------------------------------------
-- Siquijor (3 tiers - no Regional Director)
-- ----------------------------------------------------------------------------

-- Tier 0: Field Office Head (top position for this province)
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 0, 0, 'Field Office Head', NULL, 200
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 0 AND sort_order = 0
)
LIMIT 1;

-- Tier 1: DILEEP Focal
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 1, 0, 'DILEEP Focal', NULL, 210
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 1 AND sort_order = 0
)
LIMIT 1;

-- Tier 2: Support Staff
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 2, 0, 'LDS / Office Staff', NULL, 220
WHERE NOT EXISTS (
    SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 2 AND sort_order = 0
)
LIMIT 1;

-- ============================================================================
-- PART 3: Verification Queries (commented out - uncomment to verify)
-- ============================================================================

-- Verify table structure
-- SHOW CREATE TABLE org_chart;

-- Verify all seeded data
-- SELECT 
--     province,
--     tier,
--     sort_order,
--     position_title,
--     person_name,
--     position_order,
--     created_at
-- FROM org_chart 
-- ORDER BY province ASC, tier ASC, sort_order ASC;

-- Count entries per province
-- SELECT 
--     province,
--     COUNT(*) as total_entries,
--     COUNT(DISTINCT tier) as tier_count
-- FROM org_chart 
-- GROUP BY province 
-- ORDER BY province;

-- Verify indexes
-- SHOW INDEX FROM org_chart;

-- ============================================================================
-- Migration Complete
-- ============================================================================
-- Expected result:
--   - org_chart table created with 10 default entries:
--       * Negros Occidental: 4 entries (tiers 0-3)
--       * Negros Oriental: 3 entries (tiers 0-2)
--       * Siquijor: 3 entries (tiers 0-2)
--   - All positions initially vacant (person_name = NULL)
--   - Ready for use with org-chart-admin.php and about.php
-- ============================================================================
