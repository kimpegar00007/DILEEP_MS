# Database

MySQL 8 / MariaDB 11, InnoDB, `utf8mb4`. Default DB name `dilp_monitoring`
(prod on Namecheap uses a cPanel-prefixed name, configured via `.env`).

## Which SQL file is the truth?

| File | Use |
|---|---|
| `migrations/fresh_install_production.sql` | **Canonical full schema** (v2.0, May 13 2026): drops and recreates all 12 tables, triggers and seed data (admin/admin123 super_admin, provinces, org chart, maintenance_mode=0). Destroys data. |
| `migrations/add_proponent_id_to_beneficiaries.sql`, `migrations/expand_proponent_beneficiary_columns.sql` | May 25 2026 incremental changes **not in** the fresh install. Apply after it. Both are idempotent. |
| `migrations/phase*.sql`, `province_migration.sql`, `00_add_multi_province_support.sql`, `hotfix_*.sql`, `production_schema_migration.sql` | Historical incremental steps used to bring older prod DBs forward. See `migrations/README.md`. |
| `migrations/import_production_*_may2026.sql`, `production_deploy_*.sql`, root file named `--` | Production data snapshots and imports (real records; treat as sensitive). |
| `dilp-system-database/dilp_monitoring.sql` | phpMyAdmin dump of prod (May 12 2026, MariaDB 11.4.10). Real data. |
| `sql-database/dilp_monitoring.sql` | **Obsolete** early schema (3 roles, 2 proponent types). Do not use. |
| `backups/backup_before_refactor_20260513_164849.sql` | Pre-refactor backup. |
| `fixes/*.sql` | One-off repairs (create returns/settings tables, fix the Non-LGU trigger, remove triggers, backfill liquidation deadlines, populate funding sources). |
| `philippines-region-province-citymun-brgy-master/mysql/*.sql` | PSGC reference data (not loaded by the app; the app reads the JSON copies). |

## Tables

**users**: id, username (unique), email (unique), password (bcrypt), role ENUM('super_admin','admin','regional_director','encoder','user'), province (NULL = all), full_name, is_active, timestamps.

**beneficiaries**: id; last_name, first_name, middle_name, suffix; gender ENUM('Male','Female');
barangay, municipality, province (default 'Negros Occidental'); **proponent_id** INT UNSIGNED NULL (idx, no FK constraint);
contact_number; project_name; type_of_worker, type_of_beneficiaries (comma-joined labels);
amount_worth DECIMAL(15,2); noted_findings; date_complied_by_proponent, date_forwarded_to_ro6 (label "Forwarded to NIR for RPMT"),
rpmt_findings, date_approved, date_forwarded_to_nofo (label is province-specific: NOCFO/NORFO/SFO), date_turnover, date_monitoring;
latitude DECIMAL(10,8), longitude DECIMAL(11,8); source_of_funds;
status ENUM('pending','approved','implemented','monitored'); created_by, updated_by, timestamps.

**proponents**: id; proponent_type ENUM('LGU-associated','Non-LGU-associated','By Administration','Others');
date_received, noted_findings, control_number (UNIQUE, nullable), number_of_copies, date_copies_received;
district, province; proponent_name, project_title; amount DECIMAL(15,2); number_of_associations;
total_beneficiaries, beneficiary_full_name (**TEXT after the expand migration; JSON array**), male_beneficiaries, female_beneficiaries;
type_of_beneficiaries, type_of_workers; category ENUM('Formation','Enhancement','Restoration'); recipient_barangays;
pipeline dates: letter_of_intent_date, date_forwarded_to_ro6, rpmt_findings, date_complied_by_proponent,
date_complied_by_proponent_nofo, date_forwarded_to_nofo, date_approved, date_check_release, check_number,
check_date_issued, or_number, or_date_issued, date_turnover, date_implemented, date_liquidated,
**liquidation_deadline** (turnover + 10d for LGU, + 60d otherwise), date_monitoring; source_of_funds;
latitude, longitude; status ENUM('pending','approved','implemented','liquidated','monitored'); audit columns.
Triggers `calculate_liquidation_deadline` (BEFORE INSERT) and `update_liquidation_deadline` (BEFORE UPDATE) do the same math as PHP.

**proponent_associations**: id, proponent_id, association_name, association_address, sort_order, created_at. Rewritten wholesale on every proponent save.

**proponent_returns**: id, proponent_id, return_date, reason, returned_by (user id), created_at. The record of applications returned to the proponent.

**fieldwork_schedule**: id, title, description, location, province ENUM(3 provinces) NULL, assigned_user_id, start_date, end_date,
status ENUM('pending','ongoing','completed','missed'), manual_override TINYINT, created_by, timestamps.

**activity_logs**: id, user_id, action (create/update/delete/login/logout/return…), table_name, record_id, description, province (unused by code), ip_address, created_at.

**system_settings**: setting_key (unique) → setting_value. Keys: `maintenance_mode`, `gdrive_*`.
Note that `api/settings-gdrive-auth.php` would create this table with a different shape (key as PK, no id) if it were missing.

**org_chart**: id, tier (0–3), sort_order, position_order (legacy), position_title, person_name, province.

**provinces, user_provinces, province_access_audit**: created by the multi-province migration but **not used by the PHP code**.
The province list is hard-coded in `Auth::PROVINCES`, `api/get-locations.php`, the form range tables and the `api/org-chart.php` whitelist.

## Writing a migration

```sql
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proponents' AND COLUMN_NAME = 'new_col');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE proponents ADD COLUMN new_col VARCHAR(255) NULL AFTER source_of_funds',
    'SELECT "new_col already exists, skipping..." AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
```

Prod is MariaDB, so `ADD COLUMN IF NOT EXISTS` works there, but local MySQL 8 does not support it.
Prefer the INFORMATION_SCHEMA pattern above so the file runs on both. Mirror the change into
`fresh_install_production.sql`. Prod runs these through phpMyAdmin, where `DELIMITER` blocks need care.
