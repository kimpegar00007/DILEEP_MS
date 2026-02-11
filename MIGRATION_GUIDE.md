# Online Database Migration Guide
## DOLE DILP Monitoring System

This guide explains how to safely migrate your database online using the `migrate-database-online.php` tool.

---

## 🔒 Security First

**CRITICAL:** After successful migration, you MUST:
1. Delete `migrate-database-online.php` from your server
2. Delete `MIGRATION_GUIDE.md` from your server
3. Never commit these files to version control

---

## 📋 Pre-Migration Checklist

Before running the migration, ensure:

- [ ] You have a complete backup of your database
- [ ] You have tested the migration in a development environment
- [ ] You have verified database credentials in `.env` file
- [ ] You have sufficient database permissions (CREATE, ALTER, DROP, INDEX)
- [ ] You have informed users about potential downtime
- [ ] You have read access to migration logs

---

## 🚀 Migration Methods

### Method 1: Web Browser (Recommended for InfinityFree)

1. Upload `migrate-database-online.php` to your server
2. Navigate to: `https://yourdomain.com/migrate-database-online.php`
3. Click **"Run Migration"** button
4. Wait for completion (monitor the output)
5. Verify success messages
6. Click **"Cleanup Backups"** after confirming everything works
7. **DELETE the migration file immediately**

### Method 2: Command Line (For Local/SSH Access)

```bash
# Run full migration with backup
php migrate-database-online.php

# Run migration without backup (NOT RECOMMENDED)
php migrate-database-online.php --skip-backup

# Restore from backup if something goes wrong
php migrate-database-online.php --restore

# Cleanup backup tables after successful migration
php migrate-database-online.php --cleanup
```

---

## 🔍 What the Migration Does

### 1. Pre-Flight Checks
- Validates database connection
- Checks database permissions
- Verifies required tables exist
- Checks available disk space

### 2. Backup Creation
Creates backup tables with timestamp:
- `backup_users_YYYYMMDDHHMMSS`
- `backup_beneficiaries_YYYYMMDDHHMMSS`
- `backup_proponents_YYYYMMDDHHMMSS`
- `backup_activity_logs_YYYYMMDDHHMMSS`

### 3. Migration Application
- Creates/updates base schema (users, beneficiaries, proponents, activity_logs)
- Creates performance indexes
- Creates/updates triggers (liquidation deadline calculation)
- Inserts default admin user (if not exists)
- Tracks applied migrations

### 4. Post-Migration Validation
- Verifies all tables exist
- Verifies indexes are created
- Verifies triggers are active
- Validates data integrity

### 5. Transaction Management
- All changes wrapped in a transaction
- Automatic rollback on any failure
- Ensures database consistency

---

## 📊 Understanding the Output

### Success Indicators
```
✓ Database connection verified
✓ Database permissions verified
✓ Backup created successfully
✓ Migration 'create_base_schema' applied successfully
✓ All migrations committed successfully
```

### Warning Indicators
```
⚠ Some required tables don't exist (will be created)
⚠ Unable to verify disk space
```

### Error Indicators
```
✗ Database connection check failed
✗ Insufficient database permissions
✗ MIGRATION FAILED: [error message]
✓ All changes have been rolled back
```

---

## 🔄 Rollback Procedure

If migration fails or you need to rollback:

### Via Web Browser:
1. Navigate to `migrate-database-online.php`
2. Click **"Restore Backup"** button
3. Wait for restoration to complete
4. Verify data is restored

### Via Command Line:
```bash
php migrate-database-online.php --restore
```

---

## 🧹 Post-Migration Cleanup

After confirming migration success:

### Via Web Browser:
1. Click **"Cleanup Backups"** button
2. Confirm backup tables are removed

### Via Command Line:
```bash
php migrate-database-online.php --cleanup
```

### Manual Cleanup:
```sql
DROP TABLE IF EXISTS backup_users_YYYYMMDDHHMMSS;
DROP TABLE IF EXISTS backup_beneficiaries_YYYYMMDDHHMMSS;
DROP TABLE IF EXISTS backup_proponents_YYYYMMDDHHMMSS;
DROP TABLE IF EXISTS backup_activity_logs_YYYYMMDDHHMMSS;
```

---

## 📝 Migration Logs

All migration activities are logged to `migration_log.txt` in the same directory.

**Log includes:**
- Timestamp for each operation
- Pre-flight check results
- Backup creation details
- Migration application steps
- Validation results
- Execution time
- Any errors or warnings

**Review the log after migration to ensure everything completed successfully.**

---

## ⚠️ Troubleshooting

### Issue: "Database connection failed"
**Solution:**
- Verify `.env` file has correct credentials
- Check if database server is accessible
- Verify DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

### Issue: "Insufficient database permissions"
**Solution:**
- Contact your hosting provider
- Request CREATE, ALTER, DROP, INDEX privileges
- For InfinityFree, these should be available by default

### Issue: "Migration failed" with rollback
**Solution:**
- Check `migration_log.txt` for specific error
- Verify database has sufficient space
- Check for conflicting table names
- Ensure no other process is using the database

### Issue: "Trigger creation failed"
**Solution:**
- Some shared hosting restricts triggers
- Contact hosting support
- May need to manually create triggers via phpMyAdmin

### Issue: "Backup creation failed"
**Solution:**
- Check available disk space
- Verify CREATE TABLE permission
- Try running with `--skip-backup` (NOT RECOMMENDED)

---

## 🔐 Security Best Practices

1. **Delete migration files after use:**
   ```bash
   rm migrate-database-online.php
   rm MIGRATION_GUIDE.md
   rm migration_log.txt
   ```

2. **Protect with authentication (if keeping temporarily):**
   Add to top of `migrate-database-online.php`:
   ```php
   <?php
   session_start();
   if (!isset($_SESSION['admin_authenticated'])) {
       die('Unauthorized access');
   }
   ```

3. **Use .htaccess protection:**
   ```apache
   <Files "migrate-database-online.php">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

4. **Monitor access logs:**
   Check server logs for unauthorized access attempts

---

## ✅ Verification Steps

After migration, verify:

1. **Login to the system:**
   - Username: `admin`
   - Password: `admin123`
   - Change password immediately

2. **Check tables exist:**
   - Navigate to phpMyAdmin
   - Verify: users, beneficiaries, proponents, activity_logs, migrations

3. **Test functionality:**
   - Create a new proponent (LGU-associated)
   - Set turnover date
   - Verify liquidation_deadline = turnover + 10 days
   - Change to Non-LGU-associated
   - Verify liquidation_deadline = turnover + 60 days

4. **Check indexes:**
   ```sql
   SHOW INDEX FROM proponents;
   SHOW INDEX FROM beneficiaries;
   ```

5. **Check triggers:**
   ```sql
   SHOW TRIGGERS LIKE 'proponents';
   ```

---

## 📞 Support

If you encounter issues:

1. Check `migration_log.txt` for detailed error messages
2. Review this guide's troubleshooting section
3. Verify database credentials in `.env`
4. Contact your hosting provider for permission issues
5. Restore from backup if needed

---

## 🎯 Quick Reference

| Action | Web Browser | Command Line |
|--------|-------------|--------------|
| Run Migration | Click "Run Migration" | `php migrate-database-online.php` |
| Restore Backup | Click "Restore Backup" | `php migrate-database-online.php --restore` |
| Cleanup Backups | Click "Cleanup Backups" | `php migrate-database-online.php --cleanup` |
| Skip Backup | N/A | `php migrate-database-online.php --skip-backup` |

---

## 📄 Migration Tracking

The migration system tracks applied migrations in the `migrations` table:

```sql
SELECT * FROM migrations ORDER BY applied_at DESC;
```

This prevents duplicate migrations and allows you to see migration history.

---

**Remember:** Always test in development first, backup before migrating, and delete migration files after success!
