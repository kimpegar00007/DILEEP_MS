# Production Database Migration Guide

**DILP Monitoring System - Schema Migration to Fix "Database Error"**

## Overview

This guide provides step-by-step instructions for migrating the production database to resolve the "Database Error" issue caused by schema mismatches between the database and application code.

### What This Migration Does

✅ **Fixes Critical Schema Mismatches:**
- Updates `users.role` ENUM to include `super_admin` and `regional_director`
- Adds `province` column to `users` table
- Creates missing tables: `provinces`, `org_chart`, `user_provinces`, `province_access_audit`

✅ **Preserves All Data:**
- Zero data loss - all existing users, proponents, beneficiaries preserved
- Promotes user 'admin' to 'super_admin' role automatically
- Assigns all users to "Negros Occidental" province

✅ **Enables New Features:**
- Multi-province support
- Regional director role
- Organizational chart system
- Province-based access control

---

## Pre-Migration Checklist

### 1. System Requirements
- [ ] MariaDB/MySQL 10.4+ or equivalent
- [ ] Database user with ALTER, CREATE, INSERT, UPDATE privileges
- [ ] Minimum 100MB free disk space
- [ ] Application downtime window scheduled (15-30 minutes)

### 2. Backup Procedures

**CRITICAL: Create a complete backup before proceeding!**

```bash
# Navigate to backup directory
cd /path/to/backups

# Create timestamped backup
mysqldump -u root -p dilp_monitoring > dilp_monitoring_backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup was created
ls -lh dilp_monitoring_backup_*.sql

# Test backup integrity (optional but recommended)
mysql -u root -p -e "CREATE DATABASE dilp_test;"
mysql -u root -p dilp_test < dilp_monitoring_backup_*.sql
mysql -u root -p -e "DROP DATABASE dilp_test;"
```

### 3. Pre-Migration Verification

```bash
# Verify database connection
mysql -u root -p dilp_monitoring -e "SELECT 'Connection successful' AS status;"

# Check current schema
mysql -u root -p dilp_monitoring -e "SHOW TABLES;"

# Verify users table structure
mysql -u root -p dilp_monitoring -e "DESCRIBE users;"

# Check current user roles
mysql -u root -p dilp_monitoring -e "SELECT id, username, role FROM users;"
```

---

## Migration Execution

### Step 1: Put Application in Maintenance Mode

**Option A: Via System Admin Panel**
1. Login as admin user
2. Navigate to System Admin → Settings
3. Enable "Maintenance Mode"
4. Verify maintenance page displays for non-admin users

**Option B: Via Database**
```sql
UPDATE system_settings 
SET setting_value = '1' 
WHERE setting_key = 'maintenance_mode';
```

### Step 2: Stop Application Traffic

```bash
# If using Apache
sudo systemctl stop httpd
# OR
sudo apachectl stop

# If using Nginx + PHP-FPM
sudo systemctl stop nginx
sudo systemctl stop php-fpm
```

### Step 3: Execute Migration Script

**Method 1: Direct MySQL Import (Recommended)**
```bash
mysql -u root -p dilp_monitoring < /path/to/migrations/production_schema_migration.sql
```

**Method 2: Interactive MySQL Session**
```bash
mysql -u root -p dilp_monitoring

# Inside MySQL prompt:
source /path/to/migrations/production_schema_migration.sql;
```

**Method 3: phpMyAdmin**
1. Login to phpMyAdmin
2. Select `dilp_monitoring` database
3. Go to "Import" tab
4. Choose file: `production_schema_migration.sql`
5. Click "Go"

### Step 4: Monitor Migration Progress

The migration script will output validation messages at each phase:

```
✓ Starting migration on database: dilp_monitoring
✓ PASS: All core tables exist
✓ Creating missing tables...
✓ Adding missing columns...
✓ Populating reference data...
✓ Migrating user data...
✓ Updating role ENUM...
✓ Creating foreign keys...
✓ PASS: All new tables created
✓ PASS: users.role ENUM updated successfully
✓ PASS: Admin user promoted to super_admin
✓ MIGRATION COMPLETED SUCCESSFULLY
```

### Step 5: Verify Migration Success

```sql
-- Check new tables exist
SHOW TABLES LIKE '%province%';
SHOW TABLES LIKE 'org_chart';

-- Verify users.role ENUM
SHOW COLUMNS FROM users LIKE 'role';

-- Verify admin user promotion
SELECT id, username, role, province FROM users WHERE id = 1;

-- Check province assignments
SELECT u.username, u.role, u.province, p.name as mapped_province
FROM users u
LEFT JOIN user_provinces up ON u.id = up.user_id
LEFT JOIN provinces p ON up.province_id = p.id;

-- Verify data integrity
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_proponents FROM proponents;
SELECT COUNT(*) as total_beneficiaries FROM beneficiaries;
```

### Step 6: Restart Application

```bash
# If using Apache
sudo systemctl start httpd
# OR
sudo apachectl start

# If using Nginx + PHP-FPM
sudo systemctl start php-fpm
sudo systemctl start nginx
```

### Step 7: Disable Maintenance Mode

**Option A: Via Database**
```sql
UPDATE system_settings 
SET setting_value = '0' 
WHERE setting_key = 'maintenance_mode';
```

**Option B: Via System Admin Panel**
1. Login as admin user (now super_admin)
2. Navigate to System Admin → Settings
3. Disable "Maintenance Mode"

---

## Post-Migration Testing

### Test 1: User Authentication
```
1. Logout if currently logged in
2. Login as 'admin' user
3. Verify login successful
4. Check that dashboard loads without errors
5. Verify no "Database Error" messages appear
```

### Test 2: Province Filtering
```
1. Navigate to Proponents page
2. Verify all existing proponents display
3. Check that province filter works
4. Navigate to Beneficiaries page
5. Verify all existing beneficiaries display
```

### Test 3: CRUD Operations
```
1. Create a new beneficiary
2. Edit an existing proponent
3. View activity logs
4. Verify all operations complete without errors
```

### Test 4: Role-Based Access
```
1. Login as different user roles (encoder, user)
2. Verify appropriate access restrictions
3. Test that super_admin sees all data
4. Verify province filtering for non-admin users
```

### Test 5: System Admin Panel
```
1. Login as super_admin (admin user)
2. Navigate to System Admin
3. Verify Users management works
4. Check Settings page loads
5. Test Org Chart management
```

---

## Rollback Procedures

### Scenario 1: Migration Failed During Execution

**If migration script errors out:**
```sql
-- The transaction will auto-rollback
-- Simply fix the issue and re-run the script
ROLLBACK;
```

### Scenario 2: Migration Completed But Application Has Issues

**Restore from backup:**
```bash
# Stop application
sudo systemctl stop httpd

# Drop current database
mysql -u root -p -e "DROP DATABASE dilp_monitoring;"

# Recreate database
mysql -u root -p -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Restore from backup
mysql -u root -p dilp_monitoring < dilp_monitoring_backup_YYYYMMDD_HHMMSS.sql

# Restart application
sudo systemctl start httpd
```

### Scenario 3: Partial Rollback (Keep Some Changes)

**Manual rollback of specific changes:**
```sql
-- Revert users.role ENUM only
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin','encoder','user') DEFAULT 'user';

-- Restore admin user role
UPDATE users SET role = 'admin' WHERE id = 1;

-- Remove province column
ALTER TABLE users DROP COLUMN province;

-- Drop new tables
DROP TABLE IF EXISTS province_access_audit;
DROP TABLE IF EXISTS user_provinces;
DROP TABLE IF EXISTS org_chart;
DROP TABLE IF EXISTS provinces;
```

---

## Troubleshooting

### Issue: "Table already exists" Error

**Solution:**
```sql
-- Check if tables exist
SHOW TABLES LIKE '%province%';

-- If tables exist but are empty, the script will skip creation
-- This is normal and safe
```

### Issue: "Column already exists" Error

**Solution:**
```sql
-- The script uses "IF NOT EXISTS" and "ADD COLUMN IF NOT EXISTS"
-- These errors are warnings and can be ignored
-- Verify column exists:
SHOW COLUMNS FROM users LIKE 'province';
```

### Issue: Foreign Key Constraint Errors

**Solution:**
```sql
-- Check existing constraints
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'dilp_monitoring' 
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- If constraint already exists, drop and recreate:
ALTER TABLE user_provinces DROP FOREIGN KEY fk_user_provinces_user;
ALTER TABLE user_provinces 
ADD CONSTRAINT fk_user_provinces_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
```

### Issue: "Database Error" Still Appears After Migration

**Diagnosis Steps:**
```sql
-- 1. Verify role ENUM was updated
SHOW COLUMNS FROM users LIKE 'role';
-- Should show: enum('super_admin','admin','regional_director','encoder','user')

-- 2. Check province column exists
SHOW COLUMNS FROM users LIKE 'province';

-- 3. Verify all tables exist
SHOW TABLES;

-- 4. Check application error logs
tail -f /var/log/httpd/error_log
# OR
tail -f /Applications/XAMPP/xamppfiles/logs/error_log
```

### Issue: Users Cannot Login After Migration

**Solution:**
```sql
-- Check user accounts
SELECT id, username, role, province, is_active FROM users;

-- Verify admin user
SELECT * FROM users WHERE id = 1;

-- Reset admin password if needed
UPDATE users 
SET password = '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm' 
WHERE id = 1;
-- Default password: admin123 (change after login!)
```

### Issue: Performance Degradation After Migration

**Solution:**
```sql
-- Rebuild indexes
ANALYZE TABLE users;
ANALYZE TABLE beneficiaries;
ANALYZE TABLE proponents;
ANALYZE TABLE provinces;
ANALYZE TABLE user_provinces;

-- Check index usage
SHOW INDEX FROM users;
SHOW INDEX FROM beneficiaries;
SHOW INDEX FROM proponents;
```

---

## Verification Checklist

After migration, verify the following:

### Database Schema
- [ ] `provinces` table exists with 3 records
- [ ] `org_chart` table exists with default structure
- [ ] `user_provinces` table exists with user mappings
- [ ] `province_access_audit` table exists
- [ ] `users.role` ENUM includes super_admin, regional_director
- [ ] `users.province` column exists
- [ ] All indexes created successfully

### Data Integrity
- [ ] All users preserved (count matches pre-migration)
- [ ] All proponents preserved
- [ ] All beneficiaries preserved
- [ ] All activity logs preserved
- [ ] User 'admin' promoted to 'super_admin'
- [ ] All users assigned to Negros Occidental province

### Application Functionality
- [ ] Login works for all user types
- [ ] No "Database Error" messages
- [ ] Proponents page loads correctly
- [ ] Beneficiaries page loads correctly
- [ ] CRUD operations work
- [ ] Province filtering functional
- [ ] System Admin panel accessible
- [ ] Activity logs recording properly

### Performance
- [ ] Page load times acceptable
- [ ] Database queries executing efficiently
- [ ] No timeout errors
- [ ] Memory usage normal

---

## Support & Contact

If you encounter issues not covered in this guide:

1. **Check Application Logs:**
   - PHP error log: `/var/log/httpd/error_log` or XAMPP logs
   - MySQL error log: `/var/log/mysql/error.log`

2. **Database Diagnostics:**
   ```sql
   -- Check for locked tables
   SHOW OPEN TABLES WHERE In_use > 0;
   
   -- Check for long-running queries
   SHOW PROCESSLIST;
   
   -- Check table status
   SHOW TABLE STATUS FROM dilp_monitoring;
   ```

3. **Restore from Backup:**
   - If all else fails, restore from the backup created in Step 2
   - Document the error for further investigation

4. **Contact Database Administrator:**
   - Provide error logs
   - Share backup file location
   - Document steps taken before error occurred

---

## Appendix: Migration Script Phases

The migration executes in 12 phases:

1. **Pre-Migration Validation** - Verify database state
2. **Create Missing Tables** - Add provinces, org_chart, user_provinces, province_access_audit
3. **Add Missing Columns** - Add province to users and activity_logs
4. **Populate Reference Data** - Insert 3 provinces
5. **Data Migration** - Assign provinces to existing users
6. **Update Role ENUM** - Add super_admin and regional_director roles
7. **Create Foreign Keys** - Link new tables with constraints
8. **Populate User-Province Mappings** - Create user-province relationships
9. **Create Performance Indexes** - Optimize queries
10. **Seed Org Chart** - Insert default organizational structure
11. **Post-Migration Validation** - Verify all changes
12. **Optimize Tables** - Analyze tables for performance

---

**Migration Version:** 1.0  
**Last Updated:** May 13, 2026  
**Tested On:** MariaDB 11.4.10, MySQL 8.0+  
**Estimated Duration:** 5-15 minutes (depending on data volume)
