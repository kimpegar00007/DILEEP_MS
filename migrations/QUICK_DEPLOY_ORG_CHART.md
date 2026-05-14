# Quick Deploy: Org Chart Migration

## 🚀 Fast Track Deployment

### Step 1: Access Production Database
```
Log into phpMyAdmin → Select dilp_monitoring database → Click SQL tab
```

### Step 2: Run Migration
```
Copy & paste entire content of: production_org_chart_migration.sql
Click "Go" button
```

### Step 3: Verify Success
```sql
SELECT COUNT(*) FROM org_chart;
-- Expected: 10 rows
```

## ✅ Quick Verification

```sql
-- Should return 3 provinces with correct counts
SELECT province, COUNT(*) as entries 
FROM org_chart 
GROUP BY province;

-- Expected output:
-- Negros Occidental | 4
-- Negros Oriental   | 3
-- Siquijor          | 3
```

## 🎯 What You Get

- ✅ `org_chart` table created
- ✅ 10 default entries (all vacant)
- ✅ Multi-province support (3 provinces)
- ✅ Multi-person tiers (up to 5 per tier)
- ✅ Ready for `/org-chart-admin.php`
- ✅ Ready for `/about.php`

## 📝 Next Steps

1. **Test admin interface:** `/org-chart-admin.php` (super_admin only)
2. **Add real data:** Use admin interface to populate names
3. **View public chart:** `/about.php` (all users)

## 🔧 Troubleshooting

**Issue:** Table already exists  
**Fix:** Migration is idempotent - safe to re-run

**Issue:** No data showing  
**Fix:** Check user role has access to province

**Issue:** Can't add people  
**Fix:** Ensure logged in as super_admin

---

**File:** `migrations/production_org_chart_migration.sql`  
**Full Guide:** `migrations/PRODUCTION_ORG_CHART_DEPLOYMENT.md`
