# Production Data Import Instructions (May 2026)

## Overview
This guide explains how to import production data from the May 2026 export into your local database.

## What Will Be Imported
- **74 beneficiaries** (complete records with all fields)
- **2 proponents** (TRUFA and AVOFA associations)
- **575 activity logs** (complete audit trail)
- **19 fieldwork schedules** (completed and pending tasks)
- **2 proponent associations** (linked to proponents)
- **1 system setting** (maintenance mode)

## What Will Be Preserved
- **Users table** - Your local user accounts remain intact
- **Database structure** - All table structures are preserved

## Prerequisites
1. XAMPP running with MySQL/MariaDB
2. Database `dilp_monitoring` exists
3. Backup your current database (recommended)

## Execution Methods

### Method 1: Using phpMyAdmin (Recommended)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select the `dilp_monitoring` database
3. Click on the **Import** tab
4. Click **Choose File** and select:
   ```
   /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations/import_production_data_may2026.sql
   ```
5. Click **Go** to execute
6. Wait for completion message

### Method 2: Using MySQL Command Line
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/dilp-system
mysql -u root -p dilp_monitoring < migrations/import_production_data_may2026.sql
```

### Method 3: Using XAMPP Shell
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root dilp_monitoring < /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations/import_production_data_may2026.sql
```

## Verification Steps

After import, verify the data:

```sql
-- Check record counts
SELECT 'beneficiaries' as table_name, COUNT(*) as count FROM beneficiaries
UNION ALL
SELECT 'proponents', COUNT(*) FROM proponents
UNION ALL
SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL
SELECT 'fieldwork_schedule', COUNT(*) FROM fieldwork_schedule
UNION ALL
SELECT 'proponent_associations', COUNT(*) FROM proponent_associations
UNION ALL
SELECT 'users', COUNT(*) FROM users;
```

**Expected Results:**
- beneficiaries: 74
- proponents: 2
- activity_logs: 575
- fieldwork_schedule: 19
- proponent_associations: 2
- users: (your local count - should be unchanged)

## Troubleshooting

### Error: "Cannot delete or update a parent row"
- The script disables foreign key checks, but if you see this error, ensure you're running the complete script, not partial sections.

### Error: "Table doesn't exist"
- Ensure you're connected to the correct database (`dilp_monitoring`)
- Run the database migrations first if tables are missing

### Data appears empty after import
- Check that the script completed successfully
- Verify you're viewing the correct database
- Check for error messages in the MySQL/phpMyAdmin output

## Rollback (If Needed)

If you need to rollback, restore from your backup or re-run your original database setup scripts.

## Post-Import Tasks

1. **Test Login**: Verify you can still log in with your local credentials
2. **Check Beneficiaries**: Navigate to the beneficiaries page and verify 74 records
3. **Check Proponents**: Verify 2 proponent records (TRUFA and AVOFA)
4. **Check Activity Logs**: Verify audit trail is present
5. **Test Application**: Ensure all features work correctly with imported data

## Notes

- The import uses a transaction, so it's all-or-nothing
- Foreign key checks are temporarily disabled during import
- AUTO_INCREMENT values are reset to match production
- The script is idempotent - you can run it multiple times safely
