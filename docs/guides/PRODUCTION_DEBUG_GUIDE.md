# Production Debugging Guide - DILP Monitoring System

## Overview
This guide helps diagnose and resolve data creation issues in the production environment (InfinityFree).

## Issue: "Failed to create beneficiary" / "Failed to create proponent"

### Root Cause Analysis
The original code had **no error handling** in the model's `create()`, `update()`, and `delete()` methods. When database operations failed silently, users saw generic error messages without diagnostic information.

### What Was Fixed

#### 1. **Beneficiary Model** (`models/Beneficiary.php`)
- Added try-catch blocks around all database operations
- Added statement preparation validation
- Added comprehensive error logging via `logDatabaseError()` method
- Logs capture:
  - SQLSTATE error codes
  - Driver error codes and messages
  - PDOException details (code, message, file, line)
  - Timestamp, user ID, and IP address

#### 2. **Proponent Model** (`models/Proponent.php`)
- Applied identical error handling as Beneficiary model
- Same comprehensive logging mechanism

#### 3. **Form Pages** (`beneficiary-form.php`, `proponent-form.php`)
- Enhanced error messages to guide users to check logs
- Added error_log() calls with submitted data for debugging

### How to Debug Production Issues

#### Step 1: Check Error Logs
InfinityFree stores error logs in your control panel. Access them:
1. Log into InfinityFree control panel
2. Navigate to **Error Logs** section
3. Look for entries with `[Beneficiary Model]` or `[Proponent Model]` prefix
4. Check timestamps matching when the error occurred

#### Step 2: Identify Common Issues

**Issue: SQLSTATE 42S22 (Column not found)**
- **Cause**: Database schema mismatch between local and production
- **Solution**: Run database migrations on production
- **Command**: Access your database via phpMyAdmin and verify all columns exist

**Issue: SQLSTATE 23000 (Integrity constraint violation)**
- **Cause**: Duplicate key, foreign key constraint, or NOT NULL violation
- **Solution**: 
  - Check if required fields are being sent as NULL
  - Verify foreign key relationships exist
  - Check for duplicate entries

**Issue: SQLSTATE HY000 (General error)**
- **Cause**: Connection issues or permission problems
- **Solution**:
  - Verify database credentials in `.env` file
  - Check if user has INSERT/UPDATE/DELETE permissions
  - Test database connection manually

**Issue: PDOException with code 1040 (Too many connections)**
- **Cause**: Database connection pool exhausted
- **Solution**: 
  - Check for connection leaks in code
  - Reduce concurrent requests
  - Contact InfinityFree support

#### Step 3: Verify Database Schema

Run this SQL to verify beneficiaries table structure:
```sql
DESCRIBE beneficiaries;
```

Expected columns:
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- last_name (VARCHAR)
- first_name (VARCHAR)
- middle_name (VARCHAR, nullable)
- suffix (VARCHAR, nullable)
- gender (VARCHAR)
- barangay (VARCHAR)
- municipality (VARCHAR)
- contact_number (VARCHAR, nullable)
- project_name (VARCHAR)
- type_of_worker (VARCHAR, nullable)
- amount_worth (DECIMAL)
- noted_findings (TEXT, nullable)
- date_complied_by_proponent (DATE, nullable)
- date_forwarded_to_ro6 (DATE, nullable)
- rpmt_findings (TEXT, nullable)
- date_approved (DATE, nullable)
- date_forwarded_to_nofo (DATE, nullable)
- date_turnover (DATE, nullable)
- date_monitoring (DATE, nullable)
- latitude (DECIMAL, nullable)
- longitude (DECIMAL, nullable)
- status (VARCHAR)
- created_by (INT)
- updated_by (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

Run this SQL to verify proponents table structure:
```sql
DESCRIBE proponents;
```

#### Step 4: Test Data Submission

1. **Enable Debug Mode** (temporary):
   - Edit `.env`: Change `APP_ENV=production` to `APP_ENV=development`
   - This will display full error messages instead of generic ones
   - **Remember to change back to production after debugging**

2. **Submit Test Data**:
   - Try creating a minimal beneficiary/proponent with only required fields
   - Check error logs immediately after submission

3. **Check Submitted Data**:
   - Error logs will show the exact data that was submitted
   - Verify data types match database expectations

#### Step 5: Common Production-Specific Issues

**Issue: Session User ID is NULL**
- **Cause**: User not properly authenticated
- **Solution**: 
  - Verify Auth.php is working correctly
  - Check session configuration in `.env`
  - Ensure user is logged in before accessing forms

**Issue: Database Connection Fails**
- **Cause**: Incorrect credentials or host in `.env`
- **Solution**:
  - Verify `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` in `.env`
  - Test connection with phpMyAdmin
  - Check if InfinityFree database is active

**Issue: Timezone Issues with Dates**
- **Cause**: Server timezone mismatch
- **Solution**:
  - Set timezone in PHP: `date_default_timezone_set('Asia/Manila');`
  - Verify database timezone matches application

### Error Log Format

Each error is logged with this format:
```
[Model Name] Operation Context
Code: XXXX
Message: Detailed error message
File: /path/to/file.php
Line: XXX
SQLSTATE: XXXXX
Driver Code: XXXX
Driver Message: Detailed driver message
Timestamp: YYYY-MM-DD HH:MM:SS
User ID: X
IP: XXX.XXX.XXX.XXX
```

### Monitoring Best Practices

1. **Regular Log Review**: Check error logs weekly
2. **Set Up Alerts**: Configure email notifications for critical errors
3. **Database Backups**: Ensure regular automated backups
4. **Performance Monitoring**: Monitor query execution times
5. **User Feedback**: Ask users to report issues with timestamps

### Quick Troubleshooting Checklist

- [ ] Check error logs for specific error messages
- [ ] Verify database connection credentials
- [ ] Confirm database schema matches expected structure
- [ ] Test with minimal data (only required fields)
- [ ] Check user authentication status
- [ ] Verify file permissions on server
- [ ] Check server disk space
- [ ] Review recent code changes
- [ ] Test in development environment first
- [ ] Contact InfinityFree support if connection issues persist

### Support Resources

- **InfinityFree Help**: https://www.infinityfree.com/support
- **PHP PDO Documentation**: https://www.php.net/manual/en/class.pdo.php
- **MySQL Error Codes**: https://dev.mysql.com/doc/mysql-errors/8.0/en/

### Next Steps After Fixing

1. Monitor error logs for 24-48 hours
2. Test all CRUD operations (Create, Read, Update, Delete)
3. Verify data integrity in database
4. Document any issues found and solutions applied
5. Update this guide with new findings
