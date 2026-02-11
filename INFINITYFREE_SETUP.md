# InfinityFree Database Connection Setup Guide

## Problem
The application was failing with: `Database connection failed: SQLSTATE[HY000] [2002] No such file or directory`

## Root Cause
The original database configuration used a hardcoded Unix socket path (`/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`) which only works on local XAMPP installations. InfinityFree uses TCP/IP connections, not Unix sockets.

## Solution

### Step 1: Update Your `.env` File
Create or update the `.env` file in your project root with your InfinityFree database credentials:

```
APP_NAME=DILP Monitoring System
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=your-infinityfree-db-host.com
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
DB_SOCKET=
```

### Step 2: Find Your Database Credentials
1. Log in to your InfinityFree control panel
2. Navigate to **MySQL Databases** section
3. Find your database entry - it will show:
   - **Database Name** (e.g., `id12345_dbname`)
   - **Username** (e.g., `id12345_user`)
   - **Password** (your chosen password)
   - **Hostname** (e.g., `sql123.infinityfree.com`)

### Step 3: Configure `.env` with Your Credentials
Replace the placeholder values with your actual InfinityFree credentials:

```
DB_HOST=sql123.infinityfree.com
DB_PORT=3306
DB_NAME=id12345_dbname
DB_USER=id12345_user
DB_PASS=your_actual_password
DB_SOCKET=
```

### Step 4: Upload Files to InfinityFree
1. Upload all project files to your InfinityFree hosting
2. Ensure the `.env` file is uploaded (it contains your database credentials)
3. Make sure the `config/database.php` file is updated with the new connection logic

### Step 5: Verify Connection
Access your application URL. If the database connection fails:
1. Double-check your credentials in the `.env` file
2. Verify the hostname is correct (check InfinityFree control panel)
3. Ensure the database user has proper permissions
4. Check that the database exists

## Technical Details

### Connection Methods
The updated `config/database.php` now supports two connection methods:

**TCP/IP Connection (for InfinityFree and most shared hosting):**
```
mysql:host=sql123.infinityfree.com;port=3306;dbname=database_name;charset=utf8mb4
```

**Unix Socket Connection (for local XAMPP):**
```
mysql:unix_socket=/path/to/mysql.sock;dbname=database_name;charset=utf8mb4
```

The system automatically selects the appropriate method based on your `.env` configuration.

### Environment Variables
- `DB_HOST` - Database server hostname (default: localhost)
- `DB_PORT` - Database server port (default: 3306)
- `DB_NAME` - Database name (default: dilp_monitoring)
- `DB_USER` - Database username (default: root)
- `DB_PASS` - Database password (default: empty)
- `DB_SOCKET` - Unix socket path (optional, only for local development)
- `APP_ENV` - Set to `production` for InfinityFree, `development` for local testing

### Error Handling
- **Development mode** (`APP_ENV=development`): Shows detailed error messages
- **Production mode** (`APP_ENV=production`): Logs errors securely, shows generic message to users

## Troubleshooting

### Error: "No such file or directory"
- **Cause**: Socket path doesn't exist on the server
- **Solution**: Ensure `DB_SOCKET` is empty and `DB_HOST` is set to your InfinityFree hostname

### Error: "Connection refused"
- **Cause**: Incorrect hostname or port
- **Solution**: Verify hostname from InfinityFree control panel (usually `sqlXXX.infinityfree.com`)

### Error: "Access denied for user"
- **Cause**: Wrong username or password
- **Solution**: Check credentials in InfinityFree control panel and update `.env`

### Error: "Unknown database"
- **Cause**: Database name doesn't match or database doesn't exist
- **Solution**: Verify database name in InfinityFree control panel

## Local Development Setup

For local XAMPP development, use these settings:

```
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_PORT=3306
DB_NAME=dilp_monitoring
DB_USER=root
DB_PASS=
DB_SOCKET=
```

## Security Notes

1. **Never commit `.env` to version control** - it contains sensitive credentials
2. **Use strong database passwords** on production
3. **Restrict database user permissions** to only necessary tables
4. **Keep `APP_DEBUG=false`** on production to avoid exposing sensitive information
5. **Regularly backup your database** on InfinityFree

## Support

If you continue experiencing issues:
1. Check InfinityFree's documentation on database connections
2. Verify all credentials are correct
3. Contact InfinityFree support for database connectivity issues
4. Review application logs for detailed error messages
