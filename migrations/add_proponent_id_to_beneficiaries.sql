-- Migration: Add proponent_id column to beneficiaries table
-- Date: 2026-05-25
-- Purpose: Link beneficiaries to proponents (optional relationship)

ALTER TABLE beneficiaries 
ADD COLUMN proponent_id INT NULL AFTER project_name,
ADD INDEX idx_proponent_id (proponent_id),
ADD FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE SET NULL;
