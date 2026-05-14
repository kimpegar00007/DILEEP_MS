# Database Migrations Directory

This directory contains all database migration scripts for the DILP Monitoring System.

## 🚨 IMPORTANT: Production "Database Error" Fix

**If you're experiencing "Database Error" in production**, use the schema migration script:

```bash
# Quick fix (5 minutes):
cd /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations
mysql -u root -p dilp_monitoring < production_schema_migration.sql
```

**See:** `QUICK_START.md` for step-by-step instructions.

---

## Migration Scripts Overview

### 🔧 Production Fixes (Use These First!)

| Script | Purpose | When to Use |
|--------|---------|-------------|
| **`production_schema_migration.sql`** | **Fix "Database Error"** | Production DB with schema mismatch |
| `verify_migration.sql` | Validate migration success | After running schema migration |
| `QUICK_START.md` | Quick reference guide | Fast deployment instructions |
| `MIGRATION_GUIDE.md` | Detailed deployment guide | Complete step-by-step process |

### 📦 Fresh Installation

| Script | Purpose | When to Use |
|--------|---------|-------------|
| **`fresh_install_production.sql`** | **Complete fresh install (RECOMMENDED)** | New production server, clean installation |
| `FRESH_INSTALL_README.md` | Fresh install documentation | Detailed installation guide |
| `FRESH_INSTALL_QUICKSTART.md` | Quick start guide | 5-minute setup instructions |
| `production_deploy_fresh.sql` | Legacy fresh install | Old deployment method |
| `production_deploy_data_only.sql` | Data-only import | Existing schema, import data |

### 🔄 Incremental Updates

| Script | Purpose | When to Use |
|--------|---------|-------------|
| `00_add_multi_province_support.sql` | Add province support | Enable multi-province features |
| `phase8_regional_director_and_org_chart_tiers.sql` | Add regional director role | Enable regional director access |
| `phase9_beneficiary_types_and_orgchart_province.sql` | Add beneficiary types | Extend beneficiary categorization |
| `add_province_fields.sql` | Add province columns | Legacy province field addition |
| `province_migration.sql` | Province data migration | Migrate existing province data |

### 📊 Data Imports

| Script | Purpose | When to Use |
|--------|---------|-------------|
| `import_production_data_may2026.sql` | Import May 2026 data | Restore May 2026 snapshot |
| `import_production_with_structure_may2026.sql` | Structure + May 2026 data | Complete May 2026 restore |

### 🏗️ Legacy/Archive

| Script | Purpose | Status |
|--------|---------|--------|
| `database_migrations.sql` | Old migration script | ⚠️ Deprecated |
| `database_update_production.sql` | Old production update | ⚠️ Use new scripts instead |
| `namecheap-migration.sql` | Namecheap hosting migration | Specific to Namecheap |

---

## 🎯 Common Scenarios

### Scenario 1: "Database Error" in Production

**Problem:** Application shows "Database Error" when users try to login or access features.

**Root Cause:** Schema mismatch between database and code (missing super_admin role, province column, etc.)

**Solution:**
```bash
# 1. Backup first!
mysqldump -u root -p dilp_monitoring > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Run schema migration
mysql -u root -p dilp_monitoring < production_schema_migration.sql

# 3. Verify success
mysql -u root -p dilp_monitoring < verify_migration.sql

# 4. Test login
```

**See:** `QUICK_START.md` or `MIGRATION_GUIDE.md`

---

### Scenario 2: Fresh Production Deployment

**Problem:** Setting up DILP system on a new production server.

**Solution:**
```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. Import fresh installation
mysql -u root -p dilp_monitoring < fresh_install_production.sql

# 3. Login and change password
# URL: http://your-server/dilp-system/
# Username: admin
# Password: admin123
# IMMEDIATELY change password after first login!

# 4. Configure .env file (if needed)
cp .env.example .env
# Edit .env with production credentials

# 5. Create backup
mysqldump -u root -p dilp_monitoring > dilp_monitoring_fresh_backup.sql
```

**See:** `FRESH_INSTALL_QUICKSTART.md` for 5-minute setup guide

---

### Scenario 3: Updating Existing Production

**Problem:** Need to add new features to existing production database without losing data.

**Solution:**
```bash
# 1. Backup
mysqldump -u root -p dilp_monitoring > backup_before_update.sql

# 2. Check current schema
mysql -u root -p dilp_monitoring -e "SHOW COLUMNS FROM users LIKE 'role';"

# 3. If schema is outdated, run migration
mysql -u root -p dilp_monitoring < production_schema_migration.sql

# 4. Verify
mysql -u root -p dilp_monitoring < verify_migration.sql
```

---

### Scenario 4: Restoring from Backup

**Problem:** Need to restore database from backup after data loss or corruption.

**Solution:**
```bash
# 1. Drop current database
mysql -u root -p -e "DROP DATABASE dilp_monitoring;"

# 2. Recreate database
mysql -u root -p -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 3. Restore from backup
mysql -u root -p dilp_monitoring < backup_YYYYMMDD_HHMMSS.sql

# 4. Verify data
mysql -u root -p dilp_monitoring -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM proponents; SELECT COUNT(*) FROM beneficiaries;"
```

---

## 📋 Migration Checklist

Before running any migration:

- [ ] **Create database backup**
- [ ] **Enable maintenance mode** (if applicable)
- [ ] **Stop application traffic** (recommended)
- [ ] **Review migration script** (understand what it does)
- [ ] **Test on staging/development first** (if available)
- [ ] **Have rollback plan ready**
- [ ] **Verify database credentials**
- [ ] **Check disk space** (ensure enough space for migration)

After running migration:

- [ ] **Run verification script** (`verify_migration.sql`)
- [ ] **Test user login** (especially admin user)
- [ ] **Check for errors** in application logs
- [ ] **Verify data integrity** (counts match pre-migration)
- [ ] **Test CRUD operations** (create, read, update, delete)
- [ ] **Create new backup** of migrated database
- [ ] **Disable maintenance mode**
- [ ] **Monitor application** for 24-48 hours

---

## 🔍 Verification Queries

Quick checks to verify database health:

```sql
-- Check all tables exist
SHOW TABLES;

-- Check users table structure
DESCRIBE users;

-- Verify admin user
SELECT id, username, role, province FROM users WHERE id = 1;

-- Check role ENUM
SHOW COLUMNS FROM users LIKE 'role';

-- Count records
SELECT 
    'users' AS table_name, COUNT(*) AS count FROM users
UNION ALL
SELECT 'proponents', COUNT(*) FROM proponents
UNION ALL
SELECT 'beneficiaries', COUNT(*) FROM beneficiaries;

-- Check province support
SELECT DISTINCT province FROM users WHERE province IS NOT NULL;
SELECT DISTINCT province FROM proponents WHERE province IS NOT NULL;
SELECT DISTINCT province FROM beneficiaries WHERE province IS NOT NULL;
```

---

## 🆘 Troubleshooting

### "Table already exists" Error

**Cause:** Migration script trying to create a table that already exists.

**Solution:** 
- This is usually safe to ignore if using `CREATE TABLE IF NOT EXISTS`
- Verify table structure matches expected schema
- If structure is wrong, drop and recreate table

### "Column already exists" Error

**Cause:** Migration script trying to add a column that already exists.

**Solution:**
- Safe to ignore if using `ADD COLUMN IF NOT EXISTS`
- Verify column type matches expected schema

### "Foreign key constraint fails" Error

**Cause:** Trying to create foreign key but referenced data doesn't exist.

**Solution:**
```sql
-- Check foreign key constraints
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'dilp_monitoring' 
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Drop problematic constraint
ALTER TABLE user_provinces DROP FOREIGN KEY fk_user_provinces_user;

-- Recreate after fixing data
ALTER TABLE user_provinces 
ADD CONSTRAINT fk_user_provinces_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
```

### Migration Hangs/Takes Too Long

**Cause:** Large dataset, missing indexes, or locked tables.

**Solution:**
```sql
-- Check for locked tables
SHOW OPEN TABLES WHERE In_use > 0;

-- Check running processes
SHOW PROCESSLIST;

-- Kill long-running query (if safe)
KILL <process_id>;
```

### "Database Error" Still Appears After Migration

**Diagnosis:**
```sql
-- 1. Verify role ENUM updated
SHOW COLUMNS FROM users LIKE 'role';
-- Should show: enum('super_admin','admin','regional_director','encoder','user')

-- 2. Check province column exists
SHOW COLUMNS FROM users LIKE 'province';

-- 3. Verify new tables exist
SHOW TABLES LIKE '%province%';
SHOW TABLES LIKE 'org_chart';

-- 4. Check admin user
SELECT * FROM users WHERE id = 1;
-- Should have: role = 'super_admin', province = NULL
```

---

## 📚 Documentation

- **`QUICK_START.md`** - Fast deployment guide (5 minutes)
- **`MIGRATION_GUIDE.md`** - Comprehensive deployment guide (detailed)
- **`production_schema_migration.sql`** - Main migration script (well-commented)
- **`verify_migration.sql`** - Validation script (comprehensive checks)

---

## 🔐 Security Notes

1. **Always backup before migration** - Cannot be stressed enough!
2. **Use strong database passwords** - Never use default passwords in production
3. **Limit database user permissions** - Grant only necessary privileges
4. **Secure backup files** - Encrypt backups containing sensitive data
5. **Review migration scripts** - Understand what each script does before running
6. **Test in staging first** - Never test migrations directly in production
7. **Monitor after migration** - Watch for unusual activity or errors

---

## 📞 Support

If you encounter issues not covered here:

1. **Check application logs:**
   - PHP error log: `/var/log/httpd/error_log` or XAMPP logs
   - MySQL error log: `/var/log/mysql/error.log`

2. **Run verification script:**
   ```bash
   mysql -u root -p dilp_monitoring < verify_migration.sql
   ```

3. **Review migration guide:**
   - See `MIGRATION_GUIDE.md` for detailed troubleshooting

4. **Restore from backup if needed:**
   ```bash
   mysql -u root -p dilp_monitoring < backup_YYYYMMDD_HHMMSS.sql
   ```

---

## 📝 Migration History

| Date | Script | Purpose | Status |
|------|--------|---------|--------|
| May 13, 2026 | `fresh_install_production.sql` | Fresh installation with all features | ✅ Current (Recommended) |
| May 13, 2026 | `production_schema_migration.sql` | Fix "Database Error" | ✅ Current |
| May 12, 2026 | `phase9_beneficiary_types_and_orgchart_province.sql` | Add beneficiary types | ✅ Integrated |
| May 12, 2026 | `phase8_regional_director_and_org_chart_tiers.sql` | Add regional director | ✅ Integrated |
| May 11, 2026 | `00_add_multi_province_support.sql` | Multi-province support | ✅ Integrated |
| May 2026 | `import_production_data_may2026.sql` | May 2026 data snapshot | 📦 Archive |

---

**Last Updated:** May 13, 2026  
**Maintained By:** Development Team  
**Version:** 2.0
