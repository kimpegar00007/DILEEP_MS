-- Migration: Backfill fieldwork_schedule.province from the assigned user's province
--
-- Before Oct 2026, activities created by super_admin / regional_director were saved
-- with province = NULL, so no provincial user (including the assigned staff member)
-- could see them. New and edited activities now take the assignee's province.
-- This fixes the existing rows.
--
-- Idempotent: only touches rows whose province is still NULL and whose assignee
-- has a province. Safe to run more than once.

UPDATE fieldwork_schedule fs
JOIN users u ON u.id = fs.assigned_user_id
SET fs.province = u.province
WHERE fs.province IS NULL
  AND u.province IS NOT NULL;

SELECT ROW_COUNT() AS rows_backfilled;
