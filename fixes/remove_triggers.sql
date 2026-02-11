-- Migration script to remove triggers from online hosting
-- Run this script on your online database to remove the triggers that are causing permission errors

DROP TRIGGER IF EXISTS calculate_liquidation_deadline;
DROP TRIGGER IF EXISTS update_liquidation_deadline;
