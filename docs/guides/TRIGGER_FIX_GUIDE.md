# MySQL Trigger Permission Fix - Implementation Guide

## Problem
Your online hosting provider does not grant `TRIGGER` privileges to your database user. This prevents the automatic calculation of liquidation deadlines using MySQL triggers.

**Error:** `#1142 - TRIGGER command denied to user 'if0_41077295'@'192.168.0.6'`

## Solution
The liquidation deadline calculation logic has been moved from MySQL triggers to the PHP application layer. This approach:
- Works on any hosting provider regardless of trigger permissions
- Maintains the same business logic
- Ensures consistent deadline calculations across all operations

## Implementation Steps

### Step 1: Remove Triggers from Online Database
1. Log in to your online hosting control panel (cPanel, Plesk, etc.)
2. Access phpMyAdmin or your database management tool
3. Select your database (`if0_41077295_dilp_monitoring`)
4. Open the SQL tab and execute the following commands:

```sql
DROP TRIGGER IF EXISTS calculate_liquidation_deadline;
DROP TRIGGER IF EXISTS update_liquidation_deadline;
```

Alternatively, you can use the provided `remove_triggers.sql` file.

### Step 2: Deploy Updated Code
Upload the following updated files to your online server:
- `models/Proponent.php` - Contains the new `calculateLiquidationDeadline()` method
- `database_migrations.sql` - Updated schema without trigger definitions

### Step 3: Verify Implementation
1. Create a new proponent record with a turnover date
2. Verify that the liquidation deadline is automatically calculated:
   - LGU-associated: 10 days from turnover
   - Non-LGU-associated: 60 days from turnover

## How It Works

### Liquidation Deadline Calculation
The `calculateLiquidationDeadline()` method in the Proponent model:
- Takes the `date_turnover` and `proponent_type` as parameters
- Returns `null` if no turnover date is provided
- Adds 10 days for LGU-associated proponents
- Adds 60 days for Non-LGU-associated proponents

### Automatic Calculation Points
The deadline is automatically calculated when:
1. **Creating a new proponent** - `create()` method
2. **Updating an existing proponent** - `update()` method

Both operations call `calculateLiquidationDeadline()` before saving to the database.

## Code Changes Summary

### Proponent.php Model
- Added `calculateLiquidationDeadline($dateTurnover, $proponentType)` method
- Updated `create()` method to calculate deadline before insert
- Updated `update()` method to calculate deadline before update
- Both methods now include `liquidation_deadline` in their SQL statements

### database_migrations.sql
- Removed trigger definitions that were causing permission errors
- Schema remains unchanged; only trigger creation statements removed

## Testing Checklist
- [ ] Triggers successfully removed from online database
- [ ] New proponent with turnover date creates correct deadline
- [ ] Updating proponent turnover date recalculates deadline
- [ ] LGU-associated proponents get 10-day deadlines
- [ ] Non-LGU-associated proponents get 60-day deadlines
- [ ] Proponents without turnover dates have null deadlines
- [ ] Existing proponent records display correctly

## Rollback Plan
If needed, you can restore triggers on a local environment (if your local hosting supports it):
1. Use the original `database_migrations.sql` from your backup
2. The application will still work correctly as it calculates deadlines in PHP

## Notes
- The application-layer solution is more flexible and works across all hosting providers
- No data migration is needed; existing records will work with the new system
- The liquidation deadline display in the form remains unchanged
