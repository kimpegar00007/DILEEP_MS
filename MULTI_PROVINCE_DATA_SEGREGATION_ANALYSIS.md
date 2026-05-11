# Multi-Province Data Segregation Strategy
## DOLE DILP Monitoring System - Long-Term Implementation Plan

**Date:** May 11, 2026  
**Project:** DILP System Enhancement for 3 Provinces  
**Scope:** Implement province-based data isolation and access control

---

## Executive Summary

The DILP system is currently designed for **Negros Occidental only**. To support 3 provinces effectively, we need to implement a **row-level security (RLS) model** with **organization-based data segregation**. This analysis identifies the most feasible and scalable approach for long-term sustainability.

**Key Finding:** The system already has partial province support in the database schema (province column exists in beneficiaries and proponents tables), but it lacks:
- User-to-province mapping
- Query-level filtering
- Province-specific UI/UX controls
- Cross-province permission management

---

## Current System Analysis

### Database Structure
```
Tables with geographic data:
├── beneficiaries (municipality, barangay, PROVINCE - partially supported)
├── proponents (district, PROVINCE - partially supported)
├── users (no province field)
├── activity_logs (logs all actions)
└── fieldwork_schedule (location field)

Current Columns:
- beneficiaries.province (column exists in model but NOT in schema)
- proponents.province (column exists in model but NOT in schema)
- proponents.district (region/district identifier)
```

### User Management
```
Current Role Structure:
- Admin: Full system access
- Encoder: Create/update records
- User: View-only access

Problem: Roles are global, not province-specific
```

### Architecture
```
Single Database → Single Schema → No data isolation currently
├── All users see all data (filtered only by role)
├── No province-level access restrictions
└── Security depends on user honesty, not code enforcement
```

---

## Approaches for Multi-Province Implementation

### **APPROACH 1: Simple Tenant Architecture (Recommended for Year 1)**

#### Overview
Create a lightweight multi-tenancy system where each province operates semi-independently.

#### Implementation Details

**1. Database Schema Changes**
```sql
-- Add province field to users table
ALTER TABLE users ADD COLUMN province_id INT;
ALTER TABLE users ADD COLUMN provinces TEXT; -- JSON: ["1", "2"] for super-admins

-- Ensure province columns exist (critical!)
ALTER TABLE beneficiaries ADD COLUMN province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental';
ALTER TABLE proponents ADD COLUMN province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental';

-- Create provinces lookup table
CREATE TABLE provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL,      -- "NO", "AO", "EB" etc
    name VARCHAR(100) UNIQUE NOT NULL,     -- Full province name
    region_code VARCHAR(10),               -- Region VI, VII, etc
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_provinces junction table for multi-province users
CREATE TABLE user_provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    province_id INT NOT NULL,
    role ENUM('admin', 'encoder', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_province (user_id, province_id)
);

-- Create indexes
CREATE INDEX idx_users_province ON users(province_id);
CREATE INDEX idx_user_provinces_user ON user_provinces(user_id);
CREATE INDEX idx_beneficiaries_province ON beneficiaries(province);
CREATE INDEX idx_proponents_province ON proponents(province);
```

**2. Authentication & Authorization**
```php
// Enhanced Auth.php

class Auth {
    public static function getUserProvinces($userId) {
        // Returns array of provinces user can access
        // Super-admin: all provinces
        // Regular user: assigned provinces only
    }
    
    public static function canAccessProvince($userId, $province) {
        // Check if user has permission for this province
    }
    
    public static function getDefaultProvince($userId) {
        // Returns user's primary/default province
    }
}
```

**3. Query-Level Filtering (Core Security)**
```php
// Base query pattern all pages should use

class BaseModel {
    protected function addProvinceFilter(&$query, $userProvinces) {
        // Append: WHERE province IN ('Negros Occidental', 'Antique', ...)
        // CRITICAL: This happens automatically for non-admins
    }
}

// Example: Beneficiary list with filtering
$beneficiary = new Beneficiary();
$userProvinces = Auth::getUserProvinces($_SESSION['user_id']);

if (Auth::isAdmin($_SESSION['user_id'])) {
    $records = $beneficiary->getAll(); // All provinces
} else {
    $records = $beneficiary->getByProvinces($userProvinces); // Filtered
}
```

**4. UI/UX Changes**
```
Dashboard:
├── Province Selector Dropdown (if multi-province user)
├── Breadcrumb showing current province context
└── Statistics scoped to selected province

Beneficiaries/Proponents Lists:
├── Auto-filter by user's province(s)
├── Show province column (informational)
└── Province field disabled/locked in forms

Reports:
├── Province dropdown filter
├── Option to compare provinces (Admin only)
└── Export includes province header
```

#### Advantages
- ✅ Minimal database changes needed
- ✅ Works with existing schema (province column partially exists)
- ✅ Easy to implement incrementally
- ✅ Scalable to additional provinces later
- ✅ Clear data ownership model
- ✅ Can coexist with role-based access

#### Disadvantages
- ❌ Requires careful query auditing (easy to miss filtering)
- ❌ No true table-level isolation (data exists in same table)
- ❌ Risk if filtering logic is bypassed accidentally

#### Security Considerations
- **Critical:** Every query MUST include province filter for non-admin users
- **Audit:** Log all cross-province access attempts
- **Testing:** Create test cases for province boundary violations
- **Enforcement:** Use prepared statements exclusively

#### Timeline: 3-4 weeks

---

### **APPROACH 2: Full Row-Level Security (RLS) - Production Ready**

#### Overview
Implement true row-level security at database level using MySQL Views or application-layer policies.

#### Implementation Details

**1. Database Security Layer**
```sql
-- Create security context
CREATE TABLE security_context (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provinces JSON,  -- ["Negros Occidental", "Antique"]
    effective_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create base view for beneficiaries with RLS
CREATE VIEW beneficiaries_secure AS
SELECT b.*
FROM beneficiaries b
INNER JOIN security_context sc ON 
    JSON_CONTAINS(sc.provinces, JSON_QUOTE(b.province))
    AND sc.user_id = @current_user_id;

-- Application enforces @current_user_id at connection time
```

**2. Application Layer Enforcement**
```php
class SecureModel {
    public function read($id) {
        // Verify user has access to this record's province BEFORE returning
        $stmt = $this->db->prepare("
            SELECT * FROM beneficiaries b
            WHERE b.id = ?
            AND b.province IN (
                SELECT province_name FROM user_provinces up
                JOIN provinces p ON up.province_id = p.id
                WHERE up.user_id = ?
            )
        ");
        return $stmt->execute([$id, $_SESSION['user_id']]);
    }
}
```

**3. Audit & Compliance**
```sql
-- Enhanced activity logs
ALTER TABLE activity_logs ADD COLUMN province VARCHAR(100);
ALTER TABLE activity_logs ADD COLUMN accessed_as_admin BOOLEAN DEFAULT FALSE;

-- Track cross-province access
CREATE TABLE security_audit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50),
    province_accessed VARCHAR(100),
    allowed BOOLEAN,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Advantages
- ✅ True security enforcement at multiple levels
- ✅ Impossible to accidentally bypass
- ✅ Compliant with government data regulations
- ✅ Comprehensive audit trail
- ✅ Supports complex permission schemes

#### Disadvantages
- ❌ More complex implementation
- ❌ Requires database expertise
- ❌ Higher maintenance burden
- ❌ Performance impact on large datasets

#### Timeline: 6-8 weeks

---

### **APPROACH 3: Separate Database Per Province (Enterprise)**

#### Overview
Most secure but most complex: each province has isolated database.

#### Architecture
```
DILP Master System
├── Shared Services (Auth, User Mgmt)
├── Province A Database
├── Province B Database  
├── Province C Database
└── Aggregate Reporting Service
```

#### Implementation
- Central authentication service
- Province-specific database connections
- Unified UI routing to correct database
- Cross-province reporting requires aggregation

#### Advantages
- ✅ Complete data isolation
- ✅ Independent scaling per province
- ✅ Regulatory compliance (data localization)
- ✅ No risk of cross-province data leakage

#### Disadvantages
- ❌ Major refactoring required
- ❌ Complex deployment architecture
- ❌ Difficult to implement cross-province reports
- ❌ High operational overhead
- ❌ Not recommended unless regulatory requirement exists

#### Timeline: 12-16 weeks

---

## Recommended Implementation Path

### **Phase 1: Foundation (Weeks 1-4)**
**Approach 1 - Simple Tenant Architecture**

#### Week 1-2: Database Preparation
```sql
-- 1. Add missing province columns to schema
ALTER TABLE beneficiaries MODIFY province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental';
ALTER TABLE proponents MODIFY province VARCHAR(100) NOT NULL DEFAULT 'Negros Occidental';

-- 2. Create provinces table
-- 3. Create user_provinces mapping table
-- 4. Migrate existing users to default province
```

**Files to Create/Modify:**
- `database/migrations/add_multi_province_support.sql`
- `includes/Auth.php` - Enhanced with province checks

#### Week 2-3: User Management
```
Updates to users.php:
├── Add province selector when creating users
├── Show province assignments clearly
└── Allow admins to assign multiple provinces

New page: province-management.php (Admin only)
├── List all provinces
├── Manage province settings
└── View province-specific statistics
```

#### Week 3-4: Core Filtering Implementation
```
Update all list pages:
├── beneficiaries.php - Filter by user's provinces
├── proponents.php - Filter by user's provinces
├── reports.php - Add province selector
└── dashboard.php - Scope statistics to selected province

All CRUD operations:
├── Automatically set logged-in user's default province
├── Prevent editing province field (locked)
└── Show province in all views
```

### **Phase 2: Enforcement (Weeks 5-6)**
Security audit of every query:
- [ ] All SELECT statements have province filter
- [ ] INSERT operations set user's default province
- [ ] DELETE operations check province access
- [ ] UPDATE operations validate province ownership
- [ ] API endpoints enforce province restrictions

### **Phase 3: Testing & Documentation (Weeks 7-8)**
```
Test Cases:
├── User from Province A cannot see Province B data
├── Admin can see all provinces
├── Multi-province user can switch contexts
├── Reports correctly scope to selected province
├── Activity logs track province access
└── Cross-province access is blocked with error

Documentation:
├── Administrator Guide: Managing Multi-Province Setup
├── User Guide: Working with Province Filtering
└── Developer Guide: Province-Aware Query Patterns
```

---

## Recommendations Summary

### **Best Approach for Your Project: APPROACH 1 (Simple Tenant)**

**Why:**
1. ✅ Already has partial province column support
2. ✅ Minimal database changes required
3. ✅ Can be implemented in 4-6 weeks
4. ✅ Easy for team to maintain long-term
5. ✅ Scalable to additional provinces
6. ✅ Sufficient security for government use

**Implementation Priority:**
1. **Week 1-2:** Database schema finalization
2. **Week 2-3:** User management enhancements
3. **Week 3-4:** Core filtering in all pages
4. **Week 5-6:** Security audit & testing
5. **Week 7-8:** Documentation & deployment

**Resource Requirements:**
- 1 Senior Developer (lead): 4-6 weeks full-time
- 1 Junior Developer (support): 4-6 weeks
- 1 QA Engineer (testing): 2-3 weeks
- 1 DBA (database): 1-2 weeks

**Budget Estimate:**
- Development: $8,000-$12,000
- Testing/QA: $2,000-$3,000
- Documentation: $1,000-$2,000
- Training/Deployment: $2,000-$3,000
- **Total: $13,000-$20,000**

**Success Metrics:**
- ✅ Users from different provinces cannot see each other's data
- ✅ All queries execute in < 100ms
- ✅ No data leakage in activity logs
- ✅ Admin can view all provinces
- ✅ Multi-province users can switch contexts
- ✅ 100% test coverage of province boundaries

---

## Next Steps

1. **Review & Approve:** Present this analysis to stakeholders
2. **Plan Sprint:** Create detailed JIRA/DevOps Board tasks
3. **Prepare Database:** Create migration scripts
4. **Code Review:** Set up security review checklist
5. **Testing Plan:** Design comprehensive test cases
6. **Train Team:** Conduct implementation workshop
7. **Execute:** Begin implementation following the roadmap

---

**Document Prepared By:** Senior Full-Stack Developer  
**Approved By:** [Pending]  
**Effective Date:** [Implementation Date]  
**Last Updated:** May 11, 2026

**© 2026 Department of Labor and Employment**
