-- Migration: Add province field to beneficiaries and proponents tables
-- Date: 2026-04-17
-- Description: Adds province column and updates proponent_type ENUM

-- Add province column to beneficiaries table
ALTER TABLE beneficiaries 
ADD COLUMN province VARCHAR(100) AFTER municipality;

-- Add province column to proponents table
ALTER TABLE proponents 
ADD COLUMN province VARCHAR(100) AFTER district;

-- Update proponent_type ENUM to include new options
ALTER TABLE proponents 
MODIFY COLUMN proponent_type ENUM('LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others') NOT NULL;

-- Optional: Add index for better query performance
CREATE INDEX idx_beneficiaries_province ON beneficiaries(province);
CREATE INDEX idx_proponents_province ON proponents(province);
