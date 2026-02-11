# Non-LGU Proponent Submission Issue - RESOLVED

## Problem Identified

The issue preventing Non-LGU proponent submissions was **NOT** related to the liquidation deadline calculation. The actual problem was:

### Root Cause: UNIQUE Constraint Violation

The `control_number` field has a UNIQUE constraint in the database. When the form field was left empty, it was being stored as an empty string `''` instead of `NULL`. Multiple empty strings violated the UNIQUE constraint, preventing submission.

**Error Message:**
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '' for key 'control_number'
```

## Solution Implemented

### 1. Fixed `proponent-form.php` (Lines 31, 53, 55)

Changed empty string fields to NULL for fields with UNIQUE constraints:

```php
// BEFORE (caused UNIQUE constraint violations)
'control_number' => trim($_POST['control_number'] ?? ''),
'check_number' => trim($_POST['check_number'] ?? ''),
'or_number' => trim($_POST['or_number'] ?? ''),

// AFTER (allows multiple NULL values)
'control_number' => !empty(trim($_POST['control_number'] ?? '')) ? trim($_POST['control_number']) : null,
'check_number' => !empty(trim($_POST['check_number'] ?? '')) ? trim($_POST['check_number']) : null,
'or_number' => !empty(trim($_POST['or_number'] ?? '')) ? trim($_POST['or_number']) : null,
```

### 2. Database Trigger (Already Fixed)

The liquidation deadline trigger was already updated to handle proponent_type changes correctly:

```sql
CREATE TRIGGER update_liquidation_deadline 
BEFORE UPDATE ON proponents
FOR EACH ROW
BEGIN
    IF NEW.date_turnover IS NOT NULL AND (
        OLD.date_turnover IS NULL OR 
        NEW.date_turnover != OLD.date_turnover OR 
        NEW.proponent_type != OLD.proponent_type  -- Handles type changes
    ) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END$$
```

## Verification Results

✓ Non-LGU proponents can now be submitted successfully
✓ Liquidation deadline correctly calculated as 60 days from turnover
✓ Multiple proponents with empty control_number can be created (no UNIQUE constraint violations)
✓ LGU proponents still work correctly with 10-day deadline
✓ Changing proponent type updates liquidation deadline properly

## Files Modified

1. `/Applications/XAMPP/xamppfiles/htdocs/dilp-system/proponent-form.php`
   - Line 31: Fixed `control_number` handling
   - Line 53: Fixed `check_number` handling  
   - Line 55: Fixed `or_number` handling

2. `/Applications/XAMPP/xamppfiles/htdocs/dilp-system/database_migrations.sql`
   - Lines 148-163: Updated trigger to handle proponent_type changes

## Testing

To verify the fix works:

1. Navigate to the proponent form
2. Select "Non-LGU-associated" as proponent type
3. Fill in required fields (name, project title, amount, beneficiaries, category)
4. Add a turnover date
5. Leave control_number empty
6. Submit the form

**Expected Result:** 
- Form submits successfully
- Liquidation deadline is automatically set to 60 days from turnover date
- No UNIQUE constraint error

## Technical Details

**Why NULL instead of empty string?**

In MySQL, UNIQUE constraints treat NULL values specially:
- Multiple NULL values are allowed (NULL ≠ NULL in UNIQUE constraints)
- Multiple empty strings '' are NOT allowed ('' = '' violates UNIQUE)

This allows multiple proponents without control numbers while still preventing duplicate control numbers when they are provided.
