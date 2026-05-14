-- Phase 9 Migration: Beneficiary Type Checkboxes + Org Chart Province Support
-- Date: May 12, 2026
-- Description:
--   1. Adds type_of_beneficiaries column to beneficiaries table (mirrors proponents)
--   2. Adds province column to org_chart to support per-province org charts

-- ============================================================
-- PART 1: Add type_of_beneficiaries to beneficiaries table
-- ============================================================

ALTER TABLE beneficiaries
    ADD COLUMN IF NOT EXISTS type_of_beneficiaries VARCHAR(500) DEFAULT NULL
    COMMENT 'Comma-separated list of beneficiary types (Farmers, Fisherfolk, PDL, etc.)'
    AFTER type_of_worker;

-- ============================================================
-- PART 2: Add province column to org_chart table
-- ============================================================

ALTER TABLE org_chart
    ADD COLUMN IF NOT EXISTS province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental'
    COMMENT 'Province this org chart entry belongs to'
    AFTER id;

-- Create index for province-based queries
ALTER TABLE org_chart ADD INDEX IF NOT EXISTS idx_org_chart_province (province);

-- Seed default org chart rows for Negros Oriental and Siquijor
-- (Negros Occidental already exists from phase4 migration)

-- Check and insert for Negros Oriental
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 0, 0, 'Field Office Head', NULL, 10
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 0)
LIMIT 1;

INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 1, 0, 'DILEEP Focal', NULL, 11
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 1)
LIMIT 1;

INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Negros Oriental', 2, 0, 'LDS / Office Staff', NULL, 12
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Negros Oriental' AND tier = 2)
LIMIT 1;

-- Check and insert for Siquijor
INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 0, 0, 'Field Office Head', NULL, 20
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 0)
LIMIT 1;

INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 1, 0, 'DILEEP Focal', NULL, 21
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 1)
LIMIT 1;

INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
SELECT 'Siquijor', 2, 0, 'LDS / Office Staff', NULL, 22
WHERE NOT EXISTS (SELECT 1 FROM org_chart WHERE province = 'Siquijor' AND tier = 2)
LIMIT 1;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================
-- SHOW COLUMNS FROM beneficiaries LIKE 'type_of_beneficiaries';
-- SELECT province, tier, sort_order, position_title, person_name FROM org_chart ORDER BY province, tier, sort_order;
