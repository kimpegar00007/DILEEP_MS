-- Fix for Non-LGU Proponent Submission Issue
-- This script updates the liquidation_deadline trigger to properly handle proponent_type changes

-- Drop existing trigger
DROP TRIGGER IF EXISTS update_liquidation_deadline;

-- Recreate trigger with fix
DELIMITER $$

CREATE TRIGGER update_liquidation_deadline 
BEFORE UPDATE ON proponents
FOR EACH ROW
BEGIN
    IF NEW.date_turnover IS NOT NULL AND (
        OLD.date_turnover IS NULL OR 
        NEW.date_turnover != OLD.date_turnover OR 
        NEW.proponent_type != OLD.proponent_type
    ) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END$$

DELIMITER ;
