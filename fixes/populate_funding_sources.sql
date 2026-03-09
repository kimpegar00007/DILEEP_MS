-- Migration script to populate empty source_of_funds values
-- This ensures the Funding Source Breakdown chart displays meaningful data

-- Update records with empty source_of_funds to 'DOLE' as default
-- This is a reasonable default since DOLE DILEEP is the primary funding program
UPDATE proponents 
SET source_of_funds = 'DOLE'
WHERE source_of_funds IS NULL OR source_of_funds = '';

-- Verify the update
SELECT 
    source_of_funds,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM proponents
GROUP BY source_of_funds
ORDER BY total_amount DESC;
