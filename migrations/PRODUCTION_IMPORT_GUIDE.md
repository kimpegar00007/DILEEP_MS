# Production Server Import Guide

## Issues Resolved
✅ **Fixed Issue 1:** Table 'dilemvwz_dilp_monitoring.activity_logs' doesn't exist  
✅ **Fixed Issue 2:** Cannot truncate a table referenced in a foreign key constraint

## Solution
Use the new migration script that includes **both table structures AND data**:
- File: `import_production_with_structure_may2026.sql`
- This script creates all necessary tables if they don't exist
- Safe for both fresh databases and existing databases

---

## Upload Instructions for Production Server

### Step 1: Upload the Migration File
1. Upload this file to your production server:
   ```
   import_production_with_structure_may2026.sql
   ```

2. Place it in an accessible location (e.g., via FTP/cPanel File Manager)

### Step 2: Access phpMyAdmin on Production
1. Log in to your hosting control panel (cPanel/Plesk)
2. Open phpMyAdmin
3. Select the database: `dilemvwz_dilp_monitoring`

### Step 3: Import the SQL File
1. Click the **Import** tab
2. Click **Choose File**
3. Select: `import_production_with_structure_may2026.sql`
4. **Important Settings:**
   - Format: SQL
   - Character set: utf8mb4
   - Format-specific options: Leave as default
5. Click **Go** to execute

### Step 4: Wait for Completion
- The import may take 1-2 minutes
- Do not close the browser window
- Wait for the success message

---

## What This Script Does

### Creates Tables (if missing):
- ✅ activity_logs
- ✅ beneficiaries  
- ✅ proponents
- ✅ proponent_associations
- ✅ proponent_returns
- ✅ fieldwork_schedule
- ✅ system_settings

### Imports Data:
- 74 beneficiaries
- 2 proponents
- 492 activity logs
- 17 fieldwork schedules
- 2 proponent associations
- 1 system setting

### Preserves:
- **users table** (if it exists - will NOT be modified)

---

## Verification After Import

Run this query in phpMyAdmin to verify:

```sql
SELECT 
    'beneficiaries' as table_name, COUNT(*) as count FROM beneficiaries
UNION ALL SELECT 'proponents', COUNT(*) FROM proponents
UNION ALL SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL SELECT 'fieldwork_schedule', COUNT(*) FROM fieldwork_schedule;
```

**Expected Results:**
- beneficiaries: 74
- proponents: 2
- activity_logs: 492
- fieldwork_schedule: 17

---

## Troubleshooting

### Error: "MySQL server has gone away"
- **Solution:** Increase `max_allowed_packet` in MySQL settings
- Or split the import into smaller chunks
- Contact your hosting provider for assistance

### Error: "Access denied"
- **Solution:** Ensure your database user has CREATE, INSERT, ALTER privileges
- Check with your hosting provider

### Import times out
- **Solution:** Use MySQL command line instead:
  ```bash
  mysql -u username -p dilemvwz_dilp_monitoring < import_production_with_structure_may2026.sql
  ```

### Tables already exist with data
- **Safe:** The script uses `CREATE TABLE IF NOT EXISTS`
- **Warning:** Existing data will be TRUNCATED (deleted)
- **Backup first** if you have important data

---

## Important Notes

1. **Backup First:** Always backup your database before importing
2. **Users Table:** This script does NOT modify the users table
3. **Transaction:** The entire import is wrapped in a transaction (all-or-nothing)
4. **Foreign Keys:** Temporarily disabled during import, re-enabled after
5. **Safe to Re-run:** You can run this script multiple times safely

---

## Post-Import Checklist

- [ ] Verify record counts match expected values
- [ ] Test application login
- [ ] Check beneficiaries page (should show 74 records)
- [ ] Check proponents page (should show 2 records)
- [ ] Test creating a new beneficiary
- [ ] Verify all application features work

---

## Support

If you encounter issues:
1. Check the error message in phpMyAdmin
2. Verify database user permissions
3. Ensure database name is correct: `dilemvwz_dilp_monitoring`
4. Contact your hosting provider if needed

---

**File to use:** `import_production_with_structure_may2026.sql` (833 lines)
