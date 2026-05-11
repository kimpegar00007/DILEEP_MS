# Multi-Province Implementation - Quick Reference Guide

## Three Feasible Approaches Ranked by Recommendation

### 🥇 APPROACH 1: Simple Tenant Architecture ⭐ **RECOMMENDED**
**Timeline:** 4-6 weeks | **Complexity:** Medium | **Cost:** $13-20K

```
┌─────────────────────────────────────────────────────┐
│  SINGLE DATABASE - MULTIPLE PROVINCES               │
│  All data in one schema, filtered by province      │
└─────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        ▼                 ▼                 ▼
   Province A         Province B         Province C
(Negros Occ.)        (Antique)         (Eastern Samar)
   Users see         Users see          Users see
   only A data       only B data        only C data
```

**Implementation:**
- Add `province` column to users table ✅
- Create `user_provinces` mapping table ✅
- Filter ALL queries by user's province ✅
- Add province selector in UI ✅

**Pros:**
- ✅ Minimum code changes
- ✅ Uses existing schema structure
- ✅ Easy to debug and maintain
- ✅ Scalable to more provinces
- ✅ Works with current infrastructure

**Cons:**
- ❌ Requires careful query auditing
- ❌ Filtering can be accidentally bypassed
- ❌ No true database-level isolation

---

### 🥈 APPROACH 2: Row-Level Security (RLS)
**Timeline:** 6-8 weeks | **Complexity:** High | **Cost:** $18-25K

```
┌──────────────────────────────────────────┐
│  DATABASE VIEWS WITH RLS ENFORCEMENT    │
│  Data access controlled at DB level     │
└──────────────────────────────────────────┘
  ▼                ▼                  ▼
View_A          View_B             View_C
(RLS Filter)  (RLS Filter)      (RLS Filter)
  │              │                  │
  └──────────────┴──────────────────┘
           │
    Actual Data Table
```

**Implementation:**
- Create security context tables
- Use MySQL views with RLS
- Enforce at database layer
- Add comprehensive audit logs

**Pros:**
- ✅ True security enforcement at DB level
- ✅ Impossible to accidentally bypass
- ✅ Compliant with government regulations
- ✅ Professional audit trail

**Cons:**
- ❌ Complex to implement
- ❌ Requires database expertise
- ❌ Higher maintenance burden
- ❌ Slight performance impact

---

### 🥉 APPROACH 3: Separate Databases Per Province
**Timeline:** 12-16 weeks | **Complexity:** Very High | **Cost:** $30-50K

```
┌─────────────────────────────────────────┐
│     CENTRAL AUTH SERVICE               │
└─────────────────────────────────────────┘
         │              │              │
         ▼              ▼              ▼
    DB Province A  DB Province B  DB Province C
    └──────────┘   └──────────┘   └──────────┘
    Isolated      Isolated      Isolated
    Data          Data          Data
```

**Implementation:**
- Separate database per province
- Central authentication service
- Route queries to correct DB
- Aggregate reporting service

**Pros:**
- ✅ Complete data isolation
- ✅ Independent scaling
- ✅ Regulatory compliance
- ✅ No cross-province leakage risk

**Cons:**
- ❌ Major refactoring needed
- ❌ Complex deployment
- ❌ Hard to do cross-province reports
- ❌ High operational overhead
- ❌ NOT recommended unless required by law

---

## Side-by-Side Comparison

| Factor | Approach 1 | Approach 2 | Approach 3 |
|--------|-----------|-----------|-----------|
| **Implementation Time** | 4-6 weeks | 6-8 weeks | 12-16 weeks |
| **Complexity** | Medium | High | Very High |
| **Database Changes** | Minimal | Moderate | Massive |
| **Code Changes** | Moderate | High | Extreme |
| **Cost** | $13-20K | $18-25K | $30-50K |
| **Security Level** | Good | Excellent | Ultimate |
| **Maintenance** | Easy | Medium | Hard |
| **Team Skill Level** | Standard | Advanced | Expert |
| **Scalability** | Good | Excellent | Excellent |
| **Performance Impact** | Minimal | Minor | None (isolated) |
| **Debugging** | Easy | Hard | Hardest |

---

## Implementation Checklist for Approach 1

### Phase 1: Database (Week 1-2)
```
DATABASE CHANGES:
├── [ ] Create provinces table
├── [ ] Create user_provinces junction table
├── [ ] Add province_id to users table
├── [ ] Verify province columns exist in beneficiaries & proponents
├── [ ] Add performance indexes
├── [ ] Backfill data for existing provinces
├── [ ] Verify data integrity
└── [ ] Backup before deployment
```

### Phase 2: Authentication (Week 2-3)
```
CODE CHANGES:
├── [ ] Update Auth.php - Add getUserProvinces()
├── [ ] Update Auth.php - Add canAccessProvince()
├── [ ] Update Auth.php - Add getDefaultProvince()
├── [ ] Update login.php to set province session
├── [ ] Store user provinces in SESSION
└── [ ] Test authentication with multiple provinces
```

### Phase 3: Data Access Layer (Week 3-4)
```
MODEL UPDATES:
├── [ ] Update Beneficiary.php - Add getByProvinces()
├── [ ] Update Proponent.php - Add getByProvinces()
├── [ ] Create Province.php model class
├── [ ] Add province filtering to all queries
├── [ ] Update create() methods to set default province
├── [ ] Update getById() to verify province access
└── [ ] Audit all queries for missing filters
```

### Phase 4: User Interface (Week 4-5)
```
PAGE UPDATES:
├── [ ] beneficiaries.php - Add province filter
├── [ ] proponents.php - Add province filter
├── [ ] beneficiary-form.php - Lock province field
├── [ ] proponent-form.php - Lock province field
├── [ ] dashboard.php - Add province selector
├── [ ] reports.php - Add province dropdown
├── [ ] Create province-management.php (Admin)
├── [ ] Update header.php - Show current province
└── [ ] Add province breadcrumb navigation
```

### Phase 5: Security & Testing (Week 5-6)
```
TESTING & AUDIT:
├── [ ] Create security test cases
├── [ ] Test Province A user cannot see B data
├── [ ] Test Admin can see all provinces
├── [ ] Test multi-province user can switch
├── [ ] Test unauthorized province access blocked
├── [ ] Audit all SQL queries
├── [ ] Performance test with 3 provinces
└── [ ] Load test with multiple concurrent users
```

### Phase 6: Documentation (Week 6-7)
```
DOCUMENTATION:
├── [ ] Update README.md
├── [ ] Create MULTI_PROVINCE_SETUP.md
├── [ ] Update USER_MANUAL.md sections
├── [ ] Create SECURITY.md
├── [ ] Create ADMIN_GUIDE.md
├── [ ] Create API documentation
└── [ ] Prepare training materials
```

### Phase 7: Deployment & Training (Week 7-8)
```
DEPLOYMENT:
├── [ ] Final database backup
├── [ ] Run migrations
├── [ ] Verify data integrity
├── [ ] Deploy application code
├── [ ] Run smoke tests
├── [ ] Monitor activity logs
├── [ ] Train administrators
├── [ ] Train encoders/users
└── [ ] Document any issues
```

---

## Key Files to Modify

### Database Files
```
NEW:
├── migrations/add_multi_province_support.sql
├── migrations/seed_provinces.sql
└── migrations/backfill_user_provinces.sql

MODIFY:
├── database_migrations.sql - Add provinces table
└── [Existing schema modifications]
```

### Application Files
```
MODIFY:
├── includes/Auth.php - Add province methods
├── models/Beneficiary.php - Add province filtering
├── models/Proponent.php - Add province filtering
├── config/database.php - No changes needed
├── login.php - Set province in session
└── logout.php - Clear province from session

NEW:
├── models/Province.php - New model class
├── province-management.php - Admin UI
└── api/get-provinces.php - AJAX endpoint

UPDATE THESE PAGES:
├── beneficiaries.php
├── beneficiary-form.php
├── proponents.php
├── proponent-form.php
├── dashboard.php
├── reports.php
├── users.php
└── activity-logs.php
```

---

## Security Best Practices

### For Developers ⚠️
1. **ALWAYS filter by province** - No query without WHERE clause
2. **Use prepared statements** - Prevent SQL injection
3. **Validate province access** - Before every operation
4. **Log province access** - Comprehensive audit trail
5. **Test boundaries** - Attempt unauthorized access
6. **Code review** - Have colleague review province logic

### For Administrators 🔐
1. **Regular audits** - Check user province assignments monthly
2. **Monitor logs** - Watch for suspicious access patterns
3. **Backup regularly** - Daily backups with point-in-time recovery
4. **Control access** - Limit database access to authorized personnel
5. **Communicate changes** - Notify users of scope changes
6. **Document assignments** - Keep clear records of who accesses what

---

## Common Pitfalls to Avoid

### ❌ DON'T DO THIS
```php
// BAD: No province filtering
$records = $beneficiary->getAll();  // Gets ALL provinces!

// BAD: String concatenation
$sql = "SELECT * FROM beneficiaries WHERE province = '" . $prov . "'";

// BAD: Forgetting to check access
$record = $db->query("SELECT * FROM beneficiaries WHERE id = $id");

// BAD: Hardcoding province
if ($province == 'Negros Occidental') { ... }

// BAD: Client-side filtering only
// JS: if (data.province === 'Antique') { show(); }
```

### ✅ DO THIS INSTEAD
```php
// GOOD: Query with province filtering
$records = $beneficiary->getByProvinces($userProvinces);

// GOOD: Prepared statements
$stmt = $db->prepare("SELECT * FROM beneficiaries WHERE province = ?");
$stmt->execute([$province]);

// GOOD: Check access first
if (!Auth::canAccessProvince($userId, $record['province'])) {
    throw new Exception("Access denied");
}

// GOOD: Use configuration
define('ALLOWED_PROVINCES', ['Negros Occidental', 'Antique', ...]);

// GOOD: Server-side validation
if (!in_array($province, Auth::getUserProvinces($userId))) {
    return false;
}
```

---

## Performance Expectations

### Query Performance with Proper Indexing
```
Baseline (no province filter):        ~5ms
With province filter:                 ~8-10ms    (slight overhead)
With proper indexes:                  ~5-7ms     (minimal impact)
With 1M+ records:                     ~10-15ms   (acceptable)
```

### Database Index Strategy
```sql
-- These indexes are CRITICAL:
CREATE INDEX idx_beneficiaries_province ON beneficiaries(province);
CREATE INDEX idx_beneficiaries_province_status ON beneficiaries(province, status);
CREATE INDEX idx_proponents_province ON proponents(province);
CREATE INDEX idx_proponents_province_status ON proponents(province, status);

-- Verify indexes are used:
EXPLAIN SELECT * FROM beneficiaries WHERE province = 'Antique';
```

---

## Rollback Plan (If Issues Found)

### If Something Goes Wrong
```sql
-- 1. Stop the application
-- 2. Restore from backup
RESTORE DATABASE dilp_monitoring FROM DISK='backup_YYYYMMDD.sql';

-- 3. Revert user_provinces (users see all provinces again)
DELETE FROM user_provinces;

-- 4. Revert application code
git revert <commit_hash>

-- 5. Restart application
-- 6. Investigate issue
-- 7. Fix and retry
```

---

## Timeline Overview

```
Week 1 ▓▓░░ Database schema finalization
Week 2 ▓▓▓░ Authentication enhancements
Week 3 ▓▓▓▓ Core filtering implementation
Week 4 ▓▓▓░ UI/UX updates
Week 5 ▓▓░░ Security audit & testing
Week 6 ▓▓░░ Documentation
Week 7 ▓▓░░ Training & final testing
Week 8 ▓░░░ Deployment & monitoring

TOTAL: 4-8 weeks (depending on team size)
```

---

## Success Metrics

After implementation, you should see:

```
✅ Zero data leakage between provinces
✅ Users only see their assigned province data
✅ Admins can view all provinces
✅ All queries complete in < 100ms
✅ No cross-province access attempts logged
✅ 100% test coverage of province boundaries
✅ Comprehensive audit trail
✅ Staff trained on new system
✅ Documentation complete
✅ Zero production incidents
```

---

## FAQ - Quick Answers

**Q: Can a user from Antique see Negros Occidental data?**  
A: No, the database query filters it out before returning results.

**Q: What if an admin needs to see all provinces?**  
A: Admins bypass the province filter and see everything.

**Q: Can users be assigned to multiple provinces?**  
A: Yes, they can switch between assigned provinces via dropdown.

**Q: Will this slow down the system?**  
A: No, proper indexes keep performance impact minimal (< 2ms).

**Q: What if we add a 4th province later?**  
A: Just add it to the provinces table and assign users - no code changes needed.

**Q: Is this secure enough for government use?**  
A: Yes, Approach 1 is sufficient. Approach 2 provides extra security if needed.

---

## Resources & Support

**Documentation:**
- MULTI_PROVINCE_DATA_SEGREGATION_ANALYSIS.md (Detailed technical guide)
- This file (Quick reference)
- SECURITY.md (Security policies)

**Contact:**
- Development Lead: [Your Dev Team]
- Database Admin: [Your DBA]
- Security Officer: [Your Security Contact]

---

**Last Updated:** May 11, 2026  
**Status:** Ready for Implementation  
**Approval:** [Pending]

© 2026 Department of Labor and Employment - Region VI
