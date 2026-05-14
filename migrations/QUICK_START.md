# Quick Start: Production Database Migration

**Fix "Database Error" in 5 Steps**

---

## ⚡ Quick Migration (For Experienced DBAs)

```bash
# 1. Backup database
mysqldump -u root -p dilp_monitoring > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Enable maintenance mode
mysql -u root -p dilp_monitoring -e "UPDATE system_settings SET setting_value='1' WHERE setting_key='maintenance_mode';"

# 3. Run migration
mysql -u root -p dilp_monitoring < migrations/production_schema_migration.sql

# 4. Verify migration
mysql -u root -p dilp_monitoring < migrations/verify_migration.sql

# 5. Disable maintenance mode
mysql -u root -p dilp_monitoring -e "UPDATE system_settings SET setting_value='0' WHERE setting_key='maintenance_mode';"
```

**Done!** Test login at your application URL.

---

## 📋 What This Fixes

| Issue | Solution |
|-------|----------|
| ❌ "Database Error" on login | ✅ Updates users.role ENUM |
| ❌ Missing province support | ✅ Adds province column & tables |
| ❌ No super_admin role | ✅ Promotes admin user to super_admin |
| ❌ Missing org chart | ✅ Creates org_chart table |

---

## 🔍 Quick Verification

After migration, run these checks:

```sql
-- 1. Check admin user
SELECT id, username, role, province FROM users WHERE id = 1;
-- Expected: role = 'super_admin', province = NULL

-- 2. Check new tables
SHOW TABLES LIKE '%province%';
-- Expected: provinces, user_provinces, province_access_audit

-- 3. Check role ENUM
SHOW COLUMNS FROM users LIKE 'role';
-- Expected: enum('super_admin','admin','regional_director','encoder','user')

-- 4. Test login
-- Login as 'admin' user - should work without "Database Error"
```

---

## 🚨 If Something Goes Wrong

**Rollback immediately:**
```bash
# Stop application
sudo systemctl stop httpd

# Restore backup
mysql -u root -p dilp_monitoring < backup_YYYYMMDD_HHMMSS.sql

# Restart application
sudo systemctl start httpd
```

---

## 📚 Full Documentation

- **Detailed Guide:** `MIGRATION_GUIDE.md`
- **Migration Script:** `production_schema_migration.sql`
- **Verification Script:** `verify_migration.sql`

---

## ✅ Post-Migration Checklist

- [ ] Admin user can login
- [ ] No "Database Error" messages
- [ ] Proponents page loads
- [ ] Beneficiaries page loads
- [ ] Create/Edit operations work
- [ ] System Admin panel accessible

---

## 🆘 Support

**Common Issues:**

1. **"Table already exists"** → Safe to ignore, script is idempotent
2. **"Column already exists"** → Safe to ignore, script checks existence
3. **Login fails** → Check `SELECT * FROM users WHERE id=1;`
4. **Still getting errors** → Run `verify_migration.sql` and check output

**Need Help?**
- Check `MIGRATION_GUIDE.md` for detailed troubleshooting
- Review application error logs
- Restore from backup if needed

---

**Migration Version:** 1.0  
**Estimated Time:** 5-15 minutes  
**Downtime Required:** Yes (recommended)
