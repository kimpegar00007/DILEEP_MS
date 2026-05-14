# Production Org Chart Migration Deployment Guide

## Overview

This guide explains how to deploy the org_chart table to production using the consolidated migration file.

## Migration File

**File:** `production_org_chart_migration.sql`  
**Created:** May 12, 2026  
**Purpose:** Create org_chart table with multi-person tier and multi-province support

## What This Migration Does

1. **Creates `org_chart` table** with complete schema:
   - Province support (Negros Occidental, Negros Oriental, Siquijor)
   - Multi-person tiers (up to 5 people per tier)
   - 4 organizational levels (tiers 0-3)
   - Proper indexes for performance

2. **Seeds default data** for all 3 provinces:
   - **Negros Occidental**: 4 entries (Regional Director + 3 tiers)
   - **Negros Oriental**: 3 entries (Field Office Head + 2 tiers)
   - **Siquijor**: 3 entries (Field Office Head + 2 tiers)
   - All positions initially vacant (NULL person_name)

## Deployment Steps

### Option 1: phpMyAdmin (Recommended for Production)

1. Log into your production phpMyAdmin
2. Select the `dilp_monitoring` database
3. Click on the **SQL** tab
4. Copy the entire contents of `production_org_chart_migration.sql`
5. Paste into the SQL query box
6. Click **Go** to execute
7. Verify success message

### Option 2: MySQL Command Line

```bash
# SSH into production server
ssh your-server

# Navigate to project directory
cd /path/to/dilp-system/migrations

# Run migration
mysql -u your_username -p dilp_monitoring < production_org_chart_migration.sql

# Enter password when prompted
```

### Option 3: Import via cPanel

1. Log into cPanel
2. Navigate to **phpMyAdmin**
3. Select database
4. Click **Import** tab
5. Choose `production_org_chart_migration.sql`
6. Click **Go**

## Verification

After running the migration, verify the installation:

```sql
-- Check table exists
SHOW TABLES LIKE 'org_chart';

-- Verify structure
DESCRIBE org_chart;

-- Count entries per province
SELECT 
    province,
    COUNT(*) as total_entries,
    COUNT(DISTINCT tier) as tier_count
FROM org_chart 
GROUP BY province 
ORDER BY province;

-- Expected output:
-- Negros Occidental: 4 entries, 4 tiers
-- Negros Oriental: 3 entries, 3 tiers
-- Siquijor: 3 entries, 3 tiers

-- View all seeded data
SELECT 
    province,
    tier,
    position_title,
    person_name,
    sort_order
FROM org_chart 
ORDER BY province ASC, tier ASC, sort_order ASC;
```

## Expected Results

After successful migration:

- ✅ `org_chart` table created with 10 default entries
- ✅ All positions initially vacant (ready for data entry)
- ✅ Indexes created for performance
- ✅ Compatible with `org-chart-admin.php` (admin management)
- ✅ Compatible with `about.php` (public display)

## Post-Deployment

1. **Test the admin interface:**
   - Navigate to `/org-chart-admin.php` (super_admin only)
   - Verify all 3 provinces display correctly
   - Test adding/editing/deleting entries

2. **Test the public view:**
   - Navigate to `/about.php`
   - Verify organizational chart displays for your province
   - Super admins should see all 3 provinces

3. **Populate actual data:**
   - Use the admin interface to add real names and positions
   - Each tier can hold up to 5 people
   - Tiers 0-2 require at least 1 entry per province

## Rollback (If Needed)

If you need to rollback this migration:

```sql
-- Remove the org_chart table
DROP TABLE IF EXISTS org_chart;
```

⚠️ **Warning:** This will delete all org chart data. Only use if migration fails.

## Troubleshooting

### Issue: "Table already exists"

The migration uses `CREATE TABLE IF NOT EXISTS`, so it's safe to re-run. Existing data will be preserved.

### Issue: Duplicate entries

The INSERT statements use conditional logic to prevent duplicates. Safe to re-run.

### Issue: Missing indexes

Run these commands manually:

```sql
ALTER TABLE org_chart ADD INDEX idx_tier_sort (tier, sort_order);
ALTER TABLE org_chart ADD INDEX idx_org_chart_province (province);
```

## Support

For issues or questions:
- Check the main `INSTALLATION_GUIDE.md`
- Review `docs/guides/PRODUCTION_DEBUG_GUIDE.md`
- Verify database permissions

---

**Migration Status:** ✅ Ready for Production Deployment  
**Last Updated:** May 12, 2026
