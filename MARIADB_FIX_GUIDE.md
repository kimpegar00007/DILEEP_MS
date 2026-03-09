# MariaDB Connection Error 1130 - Fix Guide

## Problem Summary
**Error:** "Host 'localhost' is not allowed to connect to this MariaDB server"
**Error Code:** 1130
**Root Cause:** MariaDB user permissions are not configured for the connection host (localhost/127.0.0.1)

## Solution Overview

The fix includes three components:

### 1. Enhanced Database Connection Handler (`config/database.php`)
- **Multi-host fallback system**: Automatically tries multiple connection methods
- **Socket support**: Falls back to Unix socket if TCP fails
- **MariaDB-specific options**: Proper charset initialization and timeout handling
- **Improved error logging**: Detailed troubleshooting information

**Key Features:**
- Tries connection in this order: configured socket → configured host → localhost → 127.0.0.1 → system hostname
- Sets proper charset (utf8mb4) at connection initialization
- Includes 10-second timeout for connection attempts
- Logs each failed attempt for debugging

### 2. Diagnostic Script (`debug/diagnose-mariadb-permissions.php`)
Identifies the exact issue by:
- Checking PDO MySQL driver availability
- Testing all connection methods
- Displaying current configuration
- Showing user privileges and grants
- Listing all database users
- Verifying database existence

### 3. Fix Script (`fixes/fix-mariadb-permissions.php`)
Automatically resolves permissions by:
- Creating database if missing
- Creating users for localhost and 127.0.0.1
- Creating user for system hostname
- Granting all privileges on dilp_monitoring database
- Flushing privileges

## Quick Start

### Step 1: Run Diagnostic
```bash
# Via browser:
http://localhost/dilp-system/debug/diagnose-mariadb-permissions.php

# Or via command line:
php /Applications/XAMPP/xamppfiles/htdocs/dilp-system/debug/diagnose-mariadb-permissions.php
```

### Step 2: Apply Fix
```bash
# Via browser:
http://localhost/dilp-system/fixes/fix-mariadb-permissions.php

# Or via command line:
php /Applications/XAMPP/xamppfiles/htdocs/dilp-system/fixes/fix-mariadb-permissions.php
```

### Step 3: Verify Solution
```bash
# Via browser:
http://localhost/dilp-system/debug/diagnose-mariadb-permissions.php

# Should show successful connections and proper user grants
```

## Manual Fix (if scripts don't work)

If the automated scripts cannot connect, manually fix permissions:

```sql
-- Connect to MariaDB as root
mysql -u root -p

-- Create database
CREATE DATABASE IF NOT EXISTS dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create users with proper permissions
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';

-- Grant privileges
GRANT ALL PRIVILEGES ON dilp_monitoring.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON dilp_monitoring.* TO 'root'@'127.0.0.1';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify
SHOW GRANTS FOR 'root'@'localhost';
SHOW GRANTS FOR 'root'@'127.0.0.1';
```

## Configuration Verification

Check your `.env` file:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=dilp_monitoring
DB_USER=root
DB_PASS=
DB_SOCKET=
```

## MariaDB Configuration Check

If issues persist, verify MariaDB configuration:

```bash
# Check bind-address in MariaDB config
grep -i "bind-address" /Applications/XAMPP/xamppfiles/etc/my.cnf

# Should be:
# bind-address = 0.0.0.0
# OR
# bind-address = 127.0.0.1
```

If bind-address is wrong:
1. Edit `/Applications/XAMPP/xamppfiles/etc/my.cnf`
2. Change `bind-address` to `0.0.0.0` or `127.0.0.1`
3. Restart MariaDB via XAMPP Control Panel

## Troubleshooting

### Issue: Still getting Error 1130
**Solutions:**
1. Verify MariaDB is running: `mysql -u root -p -e "SELECT 1;"`
2. Check user exists: `mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='root';"`
3. Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`
4. Check MariaDB bind-address configuration

### Issue: "Access denied for user 'root'@'localhost'"
**Solution:** Password mismatch
- Verify `DB_PASS` in `.env` matches MariaDB root password
- If no password set, leave `DB_PASS=` empty

### Issue: "Unknown database 'dilp_monitoring'"
**Solution:** Database doesn't exist
- Run the fix script to create it automatically
- Or manually: `CREATE DATABASE dilp_monitoring;`

### Issue: Connection timeout
**Solutions:**
1. Verify MariaDB service is running
2. Check firewall isn't blocking port 3306
3. Verify `DB_HOST` and `DB_PORT` are correct
4. Try Unix socket if available (set `DB_SOCKET` in `.env`)

## How the Fix Works

### Connection Sequence
```
1. Try configured socket (if set)
   ↓ (if fails)
2. Try configured host (127.0.0.1)
   ↓ (if fails)
3. Try localhost
   ↓ (if fails)
4. Try 127.0.0.1 again
   ↓ (if fails)
5. Try system hostname
   ↓ (if fails)
6. Display detailed error with troubleshooting steps
```

### Why Multiple Hosts?
- **localhost**: Traditional MySQL connection, uses Unix socket on some systems
- **127.0.0.1**: TCP connection to loopback interface
- **System hostname**: Allows connections from other machines on network
- **Unix socket**: Fastest local connection method

### Why Error 1130 Occurs
MariaDB has user privileges tied to specific hosts:
- User `root@localhost` ≠ User `root@127.0.0.1`
- If only `root@localhost` exists but connection uses `127.0.0.1`, error 1130 occurs
- The fix creates users for all possible connection hosts

## Testing the Connection

After applying the fix, test with:

```php
<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $result = $conn->query("SELECT 1 as test");
    echo "✓ Connection successful!";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
?>
```

## Prevention

To prevent this issue in the future:
1. Always create users for both `localhost` and `127.0.0.1`
2. Set proper bind-address in MariaDB config
3. Use the diagnostic script during deployment
4. Document your database user setup

## Support

If issues persist:
1. Check error logs: `/Applications/XAMPP/xamppfiles/logs/`
2. Run diagnostic script for detailed information
3. Review MariaDB error log: `SHOW ENGINE INNODB STATUS;`
4. Check system firewall settings
