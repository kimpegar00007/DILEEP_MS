-- Fix existing proponent records with incorrect liquidation deadlines
-- This script recalculates liquidation_deadline for all existing records

-- Update all existing proponents that have a turnover date but incorrect or missing liquidation deadline
UPDATE proponents
SET liquidation_deadline = CASE 
    WHEN proponent_type = 'LGU-associated' THEN DATE_ADD(date_turnover, INTERVAL 10 DAY)
    WHEN proponent_type = 'Non-LGU-associated' THEN DATE_ADD(date_turnover, INTERVAL 60 DAY)
    ELSE NULL
END
WHERE date_turnover IS NOT NULL;

-- Verify the fix by showing all proponents with their liquidation deadlines
SELECT 
    id,
    proponent_name,
    proponent_type,
    date_turnover,
    liquidation_deadline,
    CASE 
        WHEN proponent_type = 'LGU-associated' THEN '10 days'
        WHEN proponent_type = 'Non-LGU-associated' THEN '60 days'
        ELSE 'N/A'
    END as expected_period,
    DATEDIFF(liquidation_deadline, date_turnover) as actual_days
FROM proponents
WHERE date_turnover IS NOT NULL
ORDER BY date_turnover DESC;
