# Fresh Install Migration Guide

**DILP Monitoring System - Production Fresh Installation**

## Overview

This migration provides a complete fresh installation of the DILP Monitoring System database with all necessary tables, indexes, constraints, triggers, and seed data.

## What's Included

### ✅ Database Tables (12 Total)

**Core Application Tables:**
- `users` - User accounts with updated role ENUM (super_admin, admin, regional_director, encoder, user)
- `activity_logs` - System activity tracking with province context
- `beneficiaries` - Individual beneficiary records
- `proponents` - Proponent/organization records with enhanced fields
- `proponent_associations` - Association mappings for proponents
- `proponent_returns` - Return tracking for proponents
- `fieldwork_schedule` - Fieldwork scheduling system
- `system_settings` - Application configuration

**Multi-Province Support Tables:**
- `provinces` - Province reference table (NO, NOR, SIQ)
- `user_provinces` - User-province access mapping
- `province_access_audit` - Audit trail for province access
- `org_chart` - Organizational chart with tier-based structure

### ✅ Seed Data

**Default Admin User:**
- Username: `admin`
- Email: `admin@dilp.gov.ph`
- Password: `admin123` ⚠️ **CHANGE IMMEDIATELY AFTER FIRST LOGIN!**
- Role: `super_admin`
- Province Access: All provinces

**Provinces (3):**
1. Negros Occidental (NO) - Region VI, Western Visayas
2. Negros Oriental (NOR) - Region VII, Central Visayas
3. Siquijor (SIQ) - Region VII, Central Visayas

**Organizational Chart:**
- 1 Regional Director position
- 3 Field Office Head positions (one per province)
- 3 DILEEP Focal Person positions (one per province)

**System Settings:**
- Maintenance Mode: Disabled (0)

### ✅ Database Features

**Indexes:**
- Performance-optimized indexes for province filtering
- Status and date-based indexes
- Composite indexes for common queries

**Foreign Keys:**
- Referential integrity constraints
- Proper CASCADE rules for deletions
- SET NULL for audit trail preservation

**Triggers:**
- `calculate_liquidation_deadline` - Auto-calculate deadline on INSERT
- `update_liquidation_deadline` - Auto-update deadline on UPDATE

## Installation Instructions

### Prerequisites

- MySQL 5.7+ or MariaDB 10.4+
- Database user with CREATE, ALTER, INSERT privileges
- Minimum 100MB free disk space

### Step 1: Create Database

```bash
mysql -u root -p -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Step 2: Execute Migration

**Option A: Command Line (Recommended)**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations
mysql -u root -p dilp_monitoring < fresh_install_production.sql
```

**Option B: MySQL Client**
```bash
mysql -u root -p dilp_monitoring

# Inside MySQL prompt:
source /Applications/XAMPP/xamppfiles/htdocs/dilp-system/migrations/fresh_install_production.sql;
```

**Option C: phpMyAdmin**
1. Login to phpMyAdmin
2. Select `dilp_monitoring` database
3. Go to "Import" tab
4. Choose file: `fresh_install_production.sql`
5. Click "Go"

### Step 3: Verify Installation

The migration includes automatic validation queries. Look for these messages:

```
✓ PASS: All 12 tables created successfully
✓ PASS: users.role ENUM includes super_admin and regional_director
✓ PASS: Admin user created successfully
✓ PASS: 3 provinces populated
✓ PASS: 3 user-province mappings created
✓ PASS: 7 org chart positions created
✓ PASS: 2 triggers created
```

### Step 4: Manual Verification (Optional)

```sql
-- Check tables
SHOW TABLES;

-- Verify admin user
SELECT id, username, role, province FROM users WHERE id = 1;

-- Check provinces
SELECT * FROM provinces;

-- View org chart
SELECT tier, position_title, full_name, province FROM org_chart ORDER BY tier, sort_order;

-- Check triggers
SHOW TRIGGERS;
```

## Post-Installation Steps

### 🔒 Security (CRITICAL)

1. **Change Admin Password Immediately**
   ```sql
   -- Login to application as admin
   -- Navigate to Profile > Change Password
   -- Or use SQL:
   UPDATE users 
   SET password = PASSWORD_HASH_HERE 
   WHERE id = 1;
   ```

2. **Review User Access**
   - Ensure only authorized personnel have database access
   - Remove default test accounts if any exist

### 👥 User Management

1. **Create Additional Users**
   - Login as admin
   - Navigate to System Admin > Users
   - Add encoders, regional directors, and other users

2. **Assign Province Access**
   - Configure user-province mappings
   - Set default provinces for each user

### 📊 Configuration

1. **Update Organizational Chart**
   - Navigate to About page
   - Replace "To Be Assigned" with actual personnel names
   - Update positions as needed

2. **Configure System Settings**
   - Review maintenance mode settings
   - Configure other application settings

3. **Customize Province Data**
   - Add/modify provinces if needed
   - Update region information

### 🔄 Backup

**Create Initial Backup:**
```bash
mysqldump -u root -p dilp_monitoring > dilp_monitoring_fresh_$(date +%Y%m%d_%H%M%S).sql
```

**Schedule Regular Backups:**
```bash
# Daily backup at 2 AM
0 2 * * * mysqldump -u root -p[PASSWORD] dilp_monitoring > /path/to/backups/dilp_$(date +\%Y\%m\%d).sql
```

## Database Schema Details

### Users Table - Role Hierarchy

```
super_admin       → Full system access, all provinces
admin             → Province-specific admin access
regional_director → Regional oversight access
encoder           → Data entry access
user              → Read-only access
```

### Province Support

- Users can be assigned to multiple provinces
- Super admins have access to all provinces (province = NULL)
- Province filtering applied automatically based on user access

### Liquidation Deadlines

**Auto-calculated based on proponent type:**
- LGU-associated: 10 days from turnover date
- Non-LGU-associated: 60 days from turnover date
- Others: 60 days from turnover date

## Troubleshooting

### Issue: "Table already exists" Error

**Solution:** Drop the database and recreate:
```sql
DROP DATABASE dilp_monitoring;
CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- Then re-run migration
```

### Issue: Foreign Key Constraint Errors

**Solution:** The migration disables foreign key checks during installation. If you see errors:
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- Re-run migration
SET FOREIGN_KEY_CHECKS = 1;
```

### Issue: Trigger Creation Fails

**Solution:** Check MySQL/MariaDB version and permissions:
```sql
-- Check version
SELECT VERSION();

-- Verify trigger privilege
SHOW GRANTS FOR CURRENT_USER();
```

### Issue: Cannot Login with Admin Account

**Solution:** Verify password hash:
```sql
-- Check admin user
SELECT id, username, password FROM users WHERE id = 1;

-- Reset password if needed (password: admin123)
UPDATE users 
SET password = '$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm' 
WHERE id = 1;
```

## Migration Details

**File:** `fresh_install_production.sql`  
**Version:** 1.0  
**Date:** May 13, 2026  
**Database:** dilp_monitoring  
**Charset:** utf8mb4  
**Collation:** utf8mb4_general_ci  
**Estimated Execution Time:** 5-10 seconds  

## Rollback Instructions

If you need to completely remove the installation:

```sql
-- Drop database
DROP DATABASE dilp_monitoring;

-- Recreate empty database
CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

## Support & Documentation

- **Migration Guide:** `/migrations/MIGRATION_GUIDE.md`
- **Project Documentation:** `/docs/`
- **Installation Guide:** `/docs/INSTALLATION_GUIDE.md`

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | May 13, 2026 | Initial fresh install migration with all tables and seed data |

## Notes

- This migration is for **fresh installations only**
- Do not run on existing databases with data
- For existing database updates, use the incremental migration files
- Always backup before running any migration
- Test on development environment first

## Default Credentials Summary

⚠️ **SECURITY WARNING**: Change these immediately after installation!

| Username | Password | Role | Email |
|----------|----------|------|-------|
| admin | admin123 | super_admin | admin@dilp.gov.ph |

---

**Last Updated:** May 13, 2026  
**Maintained By:** DILP Development Team
