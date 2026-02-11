# Fix for Non-LGU Proponent Submission Issue

## Issue Summary

The system had a critical bug affecting Non-LGU-associated proponents where the liquidation deadline calculation was not properly updating when:
1. The proponent type was changed from LGU to Non-LGU (or vice versa)
2. Only the proponent type was modified without changing the turnover date

## Root Cause

The database trigger `update_liquidation_deadline` only recalculated the liquidation deadline when `date_turnover` changed, but ignored changes to `proponent_type`. This caused Non-LGU proponents to potentially have incorrect 10-day deadlines instead of the correct 60-day deadlines.

## Changes Made

### 1. Database Trigger Fix (`database_migrations.sql`)
- **Modified**: `update_liquidation_deadline` trigger
- **Change**: Added condition to recalculate when `proponent_type` changes
- **Before**: Only triggered on `date_turnover` change
- **After**: Triggers on `date_turnover` OR `proponent_type` change

### 2. Form Validation Enhancement (`proponent-form.php`)
- **Added**: Server-side validation to ensure proponent_type is valid
- **Added**: Client-side liquidation deadline preview
- **Improvement**: Real-time calculation display when user selects type or turnover date

### 3. Migration Scripts Created
- `fix_non_lgu_trigger.sql` - Updates the trigger in existing databases
- `fix_existing_liquidation_deadlines.sql` - Recalculates all existing records
- `run-migrations.php` - PHP-based migration runner (works without mysql command-line tool)

## Installation Instructions

### Step 1: Apply Trigger Fix and Fix Existing Records

You have two options to run the migrations:

#### Option A: Command Line (Recommended for automation)
```bash
php run-migrations.php --all
```

Or run individual migrations:
```bash
php run-migrations.php --fix-trigger
php run-migrations.php --fix-records
```

#### Option B: Web Browser
1. Open your browser and navigate to: `http://localhost/dilp-system/run-migrations.php`
2. Click the **"Run All Migrations"** button
3. Wait for the process to complete and verify the success message

#### Option C: Direct SQL (If mysql command is available)
If you have the mysql command-line tool installed:

```bash
mysql -u your_username -p dilp_monitoring < fix_non_lgu_trigger.sql
mysql -u your_username -p dilp_monitoring < fix_existing_liquidation_deadlines.sql
```

### Step 3: Verify the Fix
1. Log into the system
2. Create a new Non-LGU-associated proponent with a turnover date
3. Verify the liquidation deadline is set to 60 days from turnover
4. Edit an existing LGU proponent and change it to Non-LGU
5. Verify the deadline updates to 60 days

## Technical Details

### Liquidation Deadline Rules
- **LGU-associated**: 10 days from turnover date
- **Non-LGU-associated**: 60 days from turnover date

### Trigger Logic (Fixed)
```sql
IF NEW.date_turnover IS NOT NULL AND (
    OLD.date_turnover IS NULL OR 
    NEW.date_turnover != OLD.date_turnover OR 
    NEW.proponent_type != OLD.proponent_type  -- NEW: Added this condition
) THEN
    -- Calculate deadline based on type
END IF;
```

### Form Enhancements
- Real-time preview of liquidation deadline
- Validation of proponent_type values
- Better error messages for invalid submissions

## Testing Checklist

- [ ] Create new LGU-associated proponent with turnover date → Verify 10-day deadline
- [ ] Create new Non-LGU-associated proponent with turnover date → Verify 60-day deadline
- [ ] Edit LGU proponent, change to Non-LGU → Verify deadline updates to 60 days
- [ ] Edit Non-LGU proponent, change to LGU → Verify deadline updates to 10 days
- [ ] Change turnover date on existing proponent → Verify deadline recalculates
- [ ] Submit form with invalid proponent_type → Verify error message displays

## Security & Quality Standards

✅ **Input Validation**: Added server-side validation for proponent_type
✅ **SQL Injection Prevention**: Using prepared statements (existing)
✅ **Error Handling**: Proper error messages for invalid data
✅ **Performance**: Trigger executes efficiently on BEFORE UPDATE
✅ **Data Integrity**: Ensures liquidation deadlines are always accurate

## Rollback Instructions

If you need to rollback this fix:

```sql
DROP TRIGGER IF EXISTS update_liquidation_deadline;

DELIMITER $$
CREATE TRIGGER update_liquidation_deadline 
BEFORE UPDATE ON proponents
FOR EACH ROW
BEGIN
    IF NEW.date_turnover IS NOT NULL AND (OLD.date_turnover IS NULL OR NEW.date_turnover != OLD.date_turnover) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END$$
DELIMITER ;
```

## Troubleshooting

### Issue: "mysql: command not found"
**Solution**: Use the PHP migration runner instead:
```bash
php run-migrations.php --all
```

The `run-migrations.php` script handles database connections using PHP's PDO extension and doesn't require the mysql command-line tool to be installed.

### Issue: Database connection failed
**Solution**: Ensure XAMPP MySQL is running:
1. Open XAMPP Control Panel
2. Start the MySQL service
3. Run the migration script again

### Issue: Permission denied when running PHP script
**Solution**: Make the script executable:
```bash
chmod +x run-migrations.php
php run-migrations.php --all
```

## Support

For issues or questions about this fix, refer to the system documentation or contact the development team.
