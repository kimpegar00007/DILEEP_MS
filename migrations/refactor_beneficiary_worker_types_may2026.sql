-- ============================================================================
-- Refactor Beneficiary and Worker Types Migration
-- Date: May 13, 2026
-- Purpose: Clear old data and prepare for new classification options
-- ============================================================================

-- Step 1: Backup current data
SELECT 'Creating backup of current type_of_worker and type_of_beneficiaries data...' as status;

CREATE TABLE IF NOT EXISTS beneficiaries_types_backup_20260513 AS 
SELECT id, type_of_worker, type_of_beneficiaries 
FROM beneficiaries 
WHERE type_of_worker IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

CREATE TABLE IF NOT EXISTS proponents_types_backup_20260513 AS 
SELECT id, type_of_workers, type_of_beneficiaries 
FROM proponents 
WHERE type_of_workers IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

SELECT 'Backup created successfully!' as status;

-- Step 2: Show current data before clearing
SELECT 'BEFORE CLEARING - Beneficiaries with type data:' as status;
SELECT COUNT(*) as count FROM beneficiaries WHERE type_of_worker IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

SELECT 'BEFORE CLEARING - Proponents with type data:' as status;
SELECT COUNT(*) as count FROM proponents WHERE type_of_workers IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

-- Step 3: Clear old data from beneficiaries table
UPDATE beneficiaries 
SET type_of_worker = NULL, 
    type_of_beneficiaries = NULL;

-- Step 4: Clear old data from proponents table
UPDATE proponents 
SET type_of_workers = NULL, 
    type_of_beneficiaries = NULL;

-- Step 5: Verify clearing
SELECT 'AFTER CLEARING - Beneficiaries with type data:' as status;
SELECT COUNT(*) as count FROM beneficiaries WHERE type_of_worker IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

SELECT 'AFTER CLEARING - Proponents with type data:' as status;
SELECT COUNT(*) as count FROM proponents WHERE type_of_workers IS NOT NULL OR type_of_beneficiaries IS NOT NULL;

-- Step 6: Add comments to document new allowed values
ALTER TABLE beneficiaries 
MODIFY COLUMN type_of_worker VARCHAR(500) DEFAULT NULL 
COMMENT 'Kinds Beneficiaries: Disadvantaged Workers, Indigenous People (IPs), Parents/Guardians of Child Laborers, TESDA graduates, Micro-establishment beneficiaries, Labor Organizations, Micro-entrepreneur, Others';

ALTER TABLE beneficiaries 
MODIFY COLUMN type_of_beneficiaries VARCHAR(500) DEFAULT NULL 
COMMENT 'Type of Beneficiaries: Marginalized and Landless Farmers, Marginalized Fisherfolk, Self-employed with Insufficient Income, Parents/Guardians of Child Laborers, Displaced Workers, Among others';

ALTER TABLE proponents 
MODIFY COLUMN type_of_workers VARCHAR(500) DEFAULT NULL 
COMMENT 'Kinds Beneficiaries: Disadvantaged Workers, Indigenous People (IPs), Parents/Guardians of Child Laborers, TESDA graduates, Micro-establishment beneficiaries, Labor Organizations, Micro-entrepreneur, Others';

ALTER TABLE proponents 
MODIFY COLUMN type_of_beneficiaries VARCHAR(500) DEFAULT NULL 
COMMENT 'Type of Beneficiaries: Marginalized and Landless Farmers, Marginalized Fisherfolk, Self-employed with Insufficient Income, Parents/Guardians of Child Laborers, Displaced Workers, Among others';

SELECT 'Migration completed successfully! Old data cleared and new schema documented.' as status;
