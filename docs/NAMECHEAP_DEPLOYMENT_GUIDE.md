# Namecheap Deployment Guide
## DOLE DILP Monitoring System

Complete step-by-step guide for deploying the DILP Monitoring System to Namecheap shared hosting.

---

## 📋 Table of Contents

1. [Pre-Deployment Preparation](#pre-deployment-preparation)
2. [Database Setup](#database-setup)
3. [File Upload](#file-upload)
4. [Configuration](#configuration)
5. [Database Migration](#database-migration)
6. [Post-Deployment Testing](#post-deployment-testing)
7. [Troubleshooting](#troubleshooting)
8. [Security Hardening](#security-hardening)

---

## 🎯 Pre-Deployment Preparation

### Requirements Checklist

- [ ] Namecheap hosting account (Stellar or higher recommended)
- [ ] Domain name configured and pointing to Namecheap
- [ ] FTP/SFTP client (FileZilla recommended) or cPanel File Manager access
- [ ] Database backup from local development (if migrating data)
- [ ] All project files ready for upload

### Files You'll Need

```
dilp-system/
├── namecheap-migration.sql          # Database schema (REQUIRED)
├── .env.namecheap.example           # Configuration template
└── All other project files
```

---

## 🗄️ Database Setup

### Step 1: Create MySQL Database

1. **Log in to Namecheap cPanel**
   - Go to: https://cpanel.yourdomain.com
   - Or access via Namecheap dashboard

2. **Navigate to MySQL Databases**
   - Find "Databases" section
   - Click "MySQL Databases"

3. **Create New Database**
   - Database Name: `dilp_monitoring` (or your preferred name)
   - Click "Create Database"
   - **Note the full database name** (usually `username_dilp_monitoring`)

4. **Create Database User**
   - Username: `dilp_admin` (or your preferred name)
   - Password: Generate a strong password (use cPanel generator)
   - Click "Create User"
   - **Save these credentials securely!**

5. **Add User to Database**
   - Select the user you created
   - Select the database you created
   - Click "Add"
   - Grant **ALL PRIVILEGES**
   - Click "Make Changes"

### Step 2: Note Your Database Credentials

```
Database Host: localhost
Database Name: username_dilp_monitoring
Database User: username_dilp_admin
Database Password: [your generated password]
Database Port: 3306
```

---

## 📤 File Upload

### Option A: Using cPanel File Manager (Recommended for Beginners)

1. **Access File Manager**
   - cPanel > Files > File Manager
   - Navigate to `public_html` folder

2. **Create Application Directory** (Optional)
   - If you want the app at `yourdomain.com/dilp-system`:
     - Create folder: `dilp-system`
   - If you want the app at root (`yourdomain.com`):
     - Use `public_html` directly

3. **Upload Files**
   - Click "Upload" button
   - Select all project files (or upload as ZIP)
   - Wait for upload to complete

4. **Extract ZIP** (if uploaded as ZIP)
   - Select the ZIP file
   - Click "Extract"
   - Delete ZIP file after extraction

### Option B: Using FTP/SFTP (FileZilla)

1. **Get FTP Credentials**
   - cPanel > Files > FTP Accounts
   - Or use main cPanel account credentials

2. **Connect via FileZilla**
   ```
   Host: ftp.yourdomain.com
   Username: your_cpanel_username
   Password: your_cpanel_password
   Port: 21 (FTP) or 22 (SFTP)
   ```

3. **Upload Files**
   - Local site: Navigate to your project folder
   - Remote site: Navigate to `public_html/dilp-system`
   - Select all files and drag to upload
   - Wait for transfer to complete

### File Permissions

Set the following permissions via File Manager or FTP:

```
Directories: 755
Files: 644
.env file: 600 (after creation)
```

---

## ⚙️ Configuration

### Step 1: Create .env File

1. **Copy Example File**
   - Locate `.env.namecheap.example`
   - Copy and rename to `.env`

2. **Edit .env File**
   - Use cPanel File Manager > Edit
   - Or download, edit locally, and re-upload

3. **Update Database Credentials**

```env
# Application Settings
APP_NAME="DOLE DILP Monitoring System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_dilp_monitoring
DB_USERNAME=username_dilp_admin
DB_PASSWORD=your_secure_password_here

# Alternative names (for compatibility)
DB_NAME=username_dilp_monitoring
DB_USER=username_dilp_admin
DB_PASS=your_secure_password_here

# IMPORTANT: Leave empty for Namecheap
DB_SOCKET=

# Timezone
APP_TIMEZONE=Asia/Manila
```

4. **Save and Set Permissions**
   - Save the file
   - Set permissions to `600` for security

### Step 2: Verify File Structure

Ensure your directory structure looks like this:

```
public_html/dilp-system/
├── api/
├── assets/
├── config/
│   └── database.php
├── includes/
├── models/
├── .env                    # Your configuration file
├── index.php
├── login.php
└── [other files]
```

---

## 🔄 Database Migration

### Step 1: Access phpMyAdmin

1. **Open phpMyAdmin**
   - cPanel > Databases > phpMyAdmin
   - Select your database from left sidebar

2. **Verify Empty Database**
   - Should show no tables initially
   - If tables exist and you want fresh install, drop them first

### Step 2: Import Migration File

1. **Click "Import" Tab**

2. **Choose File**
   - Click "Choose File"
   - Select `namecheap-migration.sql`
   - File size should be under 2MB

3. **Import Settings**
   - Format: SQL
   - Character set: utf8mb4
   - Leave other options as default

4. **Execute Import**
   - Click "Go" button
   - Wait for import to complete
   - Should see success message

### Step 3: Verify Database Structure

1. **Check Tables Created**
   - Should see 7 tables:
     - `users`
     - `beneficiaries`
     - `proponents`
     - `proponent_associations`
     - `activity_logs`
     - `fieldwork_schedule`
     - `migrations`

2. **Verify Triggers** (if supported)
   ```sql
   SHOW TRIGGERS;
   ```
   - Should show:
     - `calculate_liquidation_deadline`
     - `update_liquidation_deadline`
   - **Note:** Some Namecheap plans may not support triggers

3. **Check Default Admin User**
   ```sql
   SELECT * FROM users WHERE username = 'admin';
   ```
   - Should return 1 row

---

## ✅ Post-Deployment Testing

### Step 1: Test Database Connection

1. **Create Test File** (optional)
   - Create `test-connection.php` in root:

```php
<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful!<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Host: " . DB_HOST . "<br>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Users table accessible. Count: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
```

2. **Access Test File**
   - Visit: `https://yourdomain.com/dilp-system/test-connection.php`
   - Should show success messages
   - **Delete this file after testing!**

### Step 2: Test Login

1. **Access Application**
   - Visit: `https://yourdomain.com/dilp-system/`
   - Should redirect to login page

2. **Login with Default Credentials**
   ```
   Username: admin
   Password: admin123
   ```

3. **Verify Dashboard Loads**
   - Should see dashboard with map
   - Check navigation menu works
   - Verify no PHP errors

### Step 3: Test Core Functionality

- [ ] Create a test beneficiary
- [ ] Create a test proponent
- [ ] Verify liquidation deadline calculation (if triggers work)
- [ ] Check activity logs are recording
- [ ] Test user management (create/edit users)
- [ ] Verify reports generation
- [ ] Test map functionality

### Step 4: Change Admin Password

**CRITICAL SECURITY STEP!**

1. Go to Profile > Change Password
2. Set a strong new password
3. Logout and login with new password

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"

**Symptoms:**
- White screen or connection error
- "SQLSTATE[HY000] [2002]" error

**Solutions:**

1. **Verify .env credentials**
   ```bash
   # Check these match your cPanel database settings
   DB_HOST=localhost
   DB_DATABASE=username_dilp_monitoring
   DB_USERNAME=username_dilp_admin
   DB_PASSWORD=correct_password
   ```

2. **Ensure DB_SOCKET is empty**
   ```bash
   DB_SOCKET=
   ```

3. **Check database user privileges**
   - cPanel > MySQL Databases
   - Verify user has ALL PRIVILEGES

4. **Test connection via phpMyAdmin**
   - If phpMyAdmin works, credentials are correct
   - Issue is likely in .env file

### Issue: "Table doesn't exist"

**Solutions:**

1. **Verify import completed**
   - phpMyAdmin > Check table list
   - Should have 7 tables

2. **Re-import migration file**
   - Drop all tables
   - Re-import `namecheap-migration.sql`

3. **Check for import errors**
   - phpMyAdmin shows errors during import
   - May need to import in smaller chunks

### Issue: "Triggers not working"

**Symptoms:**
- Liquidation deadline not auto-calculating
- Manual date entry required

**Solutions:**

1. **Check if triggers exist**
   ```sql
   SHOW TRIGGERS LIKE 'proponents';
   ```

2. **If triggers not supported:**
   - This is a Namecheap limitation on some plans
   - Liquidation deadlines must be calculated in PHP
   - Application will still work, just without auto-calculation

3. **Manual trigger creation** (if you have TRIGGER privilege)
   - Run trigger SQL separately in phpMyAdmin
   - Copy from `namecheap-migration.sql` lines 288-322

### Issue: "Permission denied" errors

**Solutions:**

1. **Fix file permissions**
   ```
   Directories: 755
   Files: 644
   .env: 600
   ```

2. **Check ownership**
   - Files should be owned by your cPanel user
   - Contact Namecheap support if ownership issues

### Issue: "Page not found" or 404 errors

**Solutions:**

1. **Check .htaccess** (if using URL rewriting)
   - Ensure .htaccess uploaded
   - Verify mod_rewrite enabled

2. **Verify file paths**
   - All includes use correct paths
   - Check case sensitivity (Linux is case-sensitive)

3. **Check DirectoryIndex**
   - Ensure index.php is recognized
   - May need to add to .htaccess:
     ```apache
     DirectoryIndex index.php index.html
     ```

### Issue: "Session errors"

**Solutions:**

1. **Check session directory permissions**
   - PHP sessions need writable directory
   - Usually handled automatically by Namecheap

2. **Verify session configuration**
   - In `php.ini` or `.user.ini`
   - Contact Namecheap if persistent issues

---

## 🔒 Security Hardening

### Essential Security Steps

1. **Change Default Admin Password**
   - [ ] Changed from `admin123` to strong password
   - [ ] Use minimum 12 characters
   - [ ] Include uppercase, lowercase, numbers, symbols

2. **Secure .env File**
   ```apache
   # Add to .htaccess
   <Files ".env">
       Order allow,deny
       Deny from all
   </Files>
   ```

3. **Disable Debug Mode**
   ```env
   APP_DEBUG=false
   APP_ENV=production
   ```

4. **Enable HTTPS**
   - Namecheap offers free SSL certificates
   - cPanel > Security > SSL/TLS
   - Install Let's Encrypt SSL
   - Force HTTPS in .htaccess:
     ```apache
     RewriteEngine On
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```

5. **Restrict File Access**
   ```apache
   # Add to .htaccess
   <FilesMatch "\.(sql|log|md)$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

6. **Hide PHP Version**
   ```apache
   # Add to .htaccess
   ServerSignature Off
   ```

7. **Set Secure Headers**
   ```apache
   # Add to .htaccess
   <IfModule mod_headers.c>
       Header set X-Content-Type-Options "nosniff"
       Header set X-Frame-Options "SAMEORIGIN"
       Header set X-XSS-Protection "1; mode=block"
       Header set Referrer-Policy "strict-origin-when-cross-origin"
   </IfModule>
   ```

### Recommended .htaccess File

Create/update `.htaccess` in your application root:

```apache
# DILP System - Security & Configuration

# Disable directory browsing
Options -Indexes

# Default index file
DirectoryIndex index.php

# Protect sensitive files
<FilesMatch "\.(env|sql|log|md|git|gitignore)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect config directory
<DirectoryMatch "^/.*/config/">
    Order allow,deny
    Deny from all
</DirectoryMatch>

# Force HTTPS (uncomment after SSL is installed)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
</apache>
```

---

## 📊 Monitoring & Maintenance

### Regular Maintenance Tasks

**Daily:**
- [ ] Check application is accessible
- [ ] Monitor error logs (cPanel > Errors)

**Weekly:**
- [ ] Review activity logs
- [ ] Check disk space usage
- [ ] Verify backups are running

**Monthly:**
- [ ] Update user passwords
- [ ] Review and clean old activity logs
- [ ] Test backup restoration
- [ ] Check for PHP/MySQL updates

### Backup Strategy

1. **Database Backups**
   - cPanel > Backup > Download Database Backup
   - Schedule: Daily or Weekly
   - Store offsite (Google Drive, Dropbox, etc.)

2. **File Backups**
   - cPanel > Backup > Download Home Directory
   - Schedule: Weekly
   - Include .env file in secure backup

3. **Automated Backups**
   - Enable cPanel automatic backups if available
   - Verify backup notifications

### Performance Optimization

1. **Enable OPcache**
   - Usually enabled by default on Namecheap
   - Check: `php -i | grep opcache`

2. **Optimize Database**
   ```sql
   OPTIMIZE TABLE beneficiaries;
   OPTIMIZE TABLE proponents;
   OPTIMIZE TABLE activity_logs;
   ```

3. **Clean Old Logs**
   ```sql
   DELETE FROM activity_logs 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

---

## 📞 Support Resources

### Namecheap Support
- **Live Chat:** Available 24/7
- **Tickets:** https://www.namecheap.com/support/
- **Knowledge Base:** https://www.namecheap.com/support/knowledgebase/

### Common Support Requests

1. **Database Issues**
   - "Need help with MySQL database permissions"
   - "Cannot create triggers in MySQL"

2. **PHP Configuration**
   - "Need to increase PHP memory limit"
   - "Need to enable specific PHP extension"

3. **SSL Certificate**
   - "Need help installing Let's Encrypt SSL"
   - "Force HTTPS redirect not working"

### Application Support

For application-specific issues:
- Review `docs/README.md`
- Check `docs/DEPLOYMENT_CHECKLIST.md`
- Review activity logs in the system

---

## ✅ Deployment Checklist

Use this checklist to ensure complete deployment:

### Pre-Deployment
- [ ] Namecheap account ready
- [ ] Domain configured
- [ ] All files prepared
- [ ] Database credentials noted

### Database Setup
- [ ] MySQL database created
- [ ] Database user created
- [ ] User added to database with ALL PRIVILEGES
- [ ] Credentials saved securely

### File Upload
- [ ] All files uploaded to server
- [ ] File permissions set correctly
- [ ] Directory structure verified

### Configuration
- [ ] .env file created
- [ ] Database credentials updated in .env
- [ ] DB_SOCKET set to empty
- [ ] APP_ENV set to production
- [ ] APP_DEBUG set to false

### Database Migration
- [ ] namecheap-migration.sql imported
- [ ] All 7 tables created
- [ ] Triggers created (or noted if not supported)
- [ ] Default admin user exists

### Testing
- [ ] Database connection tested
- [ ] Login successful with admin/admin123
- [ ] Dashboard loads correctly
- [ ] Test beneficiary created
- [ ] Test proponent created
- [ ] Activity logs recording

### Security
- [ ] Admin password changed
- [ ] .env file protected
- [ ] .htaccess configured
- [ ] SSL certificate installed
- [ ] HTTPS forced
- [ ] Sensitive files protected

### Post-Deployment
- [ ] User accounts created
- [ ] Initial data imported (if applicable)
- [ ] Backup strategy implemented
- [ ] Team trained on system
- [ ] Documentation provided

---

## 🎉 Deployment Complete!

Your DILP Monitoring System should now be live on Namecheap!

**Next Steps:**
1. Train your team on using the system
2. Import any existing data
3. Set up regular backup schedule
4. Monitor system performance
5. Collect user feedback for improvements

**Remember:**
- Keep your .env file secure
- Regularly backup your database
- Monitor activity logs
- Update passwords periodically
- Keep documentation updated

---

**Document Version:** 1.0.0  
**Last Updated:** March 2026  
**For Support:** Contact your system administrator
