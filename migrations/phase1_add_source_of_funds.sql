-- Phase 1 Migration: Add source_of_funds to beneficiaries table
-- Date: May 11, 2026
-- Description: Adds source_of_funds column to beneficiaries table for tracking funding sources

-- Add source_of_funds column to beneficiaries table if it doesn't exist
ALTER TABLE beneficiaries 
ADD COLUMN IF NOT EXISTS source_of_funds VARCHAR(255) DEFAULT NULL 
AFTER status;

-- Add comment to the column
ALTER TABLE beneficiaries 
MODIFY COLUMN source_of_funds VARCHAR(255) DEFAULT NULL 
COMMENT 'Source of funding for the beneficiary project';
