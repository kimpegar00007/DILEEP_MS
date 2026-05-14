# Fresh Install Quick Start Guide

**DILP Monitoring System - Get Started in 5 Minutes**

## 🚀 Quick Installation

### 1️⃣ Create Database (10 seconds)
```bash
mysql -u root -p -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### 2️⃣ Run Migration (5 seconds)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations
mysql -u root -p dilp_monitoring < fresh_install_production.sql
```

### 3️⃣ Login to Application
- **URL:** `http://localhost/dilp-system/`
- **Username:** `admin`
- **Password:** `admin123`

### 4️⃣ Change Password (CRITICAL!)
- Navigate to: Profile → Change Password
- Set a strong password immediately

## ✅ What You Get

- ✓ 12 database tables fully configured
- ✓ Admin user with super_admin role
- ✓ 3 provinces (Negros Occidental, Negros Oriental, Siquijor)
- ✓ Organizational chart structure
- ✓ All indexes and foreign keys
- ✓ Auto-calculation triggers
- ✓ System settings configured

## 📋 Next Steps

1. **Change admin password** ⚠️ CRITICAL
2. Create additional users (System Admin → Users)
3. Update org chart (About page)
4. Start adding proponents and beneficiaries
5. Create backup: `mysqldump -u root -p dilp_monitoring > backup.sql`

## 🔧 Verification Commands

```sql
-- Check all tables exist (should return 12)
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'dilp_monitoring';

-- Verify admin user
SELECT username, role FROM users WHERE id = 1;

-- Check provinces
SELECT code, name FROM provinces;
```

## 🆘 Quick Troubleshooting

**Can't login?**
```sql
-- Reset admin password to: admin123
UPDATE users 
SET password = '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm' 
WHERE id = 1;
```

**Need to start over?**
```sql
DROP DATABASE dilp_monitoring;
CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- Then re-run migration
```

## 📚 Full Documentation

- **Detailed Guide:** `FRESH_INSTALL_README.md`
- **Migration Guide:** `MIGRATION_GUIDE.md`
- **Installation Guide:** `/docs/INSTALLATION_GUIDE.md`

---

**Default Login:** admin / admin123 ⚠️ **CHANGE IMMEDIATELY!**
