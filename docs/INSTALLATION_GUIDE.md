# DOLE DILP Monitoring and Encoding System
## Installation and Setup Guide

---

## Table of Contents
1. System Requirements
2. Installation Steps
3. Database Setup
4. Configuration
5. First-Time Login
6. User Guide
7. Troubleshooting
8. Features Overview

---

## 1. System Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ (included in XAMPP)
- **PHP**: Version 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: Minimum 512MB RAM
- **Storage**: Minimum 500MB free space

### PHP Extensions Required
- PDO
- PDO_MySQL
- mbstring
- OpenSSL
- JSON

### Client Requirements (User's Browser)
- Modern web browser (Chrome, Firefox, Edge, Safari)
- JavaScript enabled
- Minimum screen resolution: 1366x768
- Internet connection (for map functionality)

---

## 2. Installation Steps

### Step 1: Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Run the installer and follow the installation wizard
3. Install to default location: `C:\xampp` (Windows) or `/opt/lampp` (Linux)
4. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Extract System Files
1. Extract the `dilp-system` folder
2. Copy the entire folder to XAMPP's `htdocs` directory:
   - Windows: `C:\xampp\htdocs\dilp-system`
   - Linux: `/opt/lampp/htdocs/dilp-system`

### Step 3: Verify Installation
1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL
4. Both should show green "Running" status

---

## 3. Database Setup

### Step 1: Access phpMyAdmin
1. Open your web browser
2. Navigate to: `http://localhost/phpmyadmin`
3. Default credentials:
   - Username: `root`
   - Password: (leave blank)

### Step 2: Create Database
1. Click "New" in the left sidebar
2. Database name: `dilp_monitoring`
3. Collation: `utf8mb4_general_ci`
4. Click "Create"

### Step 3: Import Database Schema
1. Select the `dilp_monitoring` database from the left sidebar
2. Click the "SQL" tab at the top
3. Click "Choose File" and select `database_migrations.sql`
4. Click "Go" to execute the SQL script

**Alternative Method:**
```sql
-- Copy and paste the entire contents of database_migrations.sql
-- into the SQL tab and click "Go"
```

### Step 4: Verify Database Tables
After import, you should see these tables:
- `users`
- `beneficiaries`
- `proponents`
- `activity_logs`

---

## 4. Configuration

### Database Configuration
1. Open `config/database.php`
2. Verify/modify these settings:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dilp_monitoring');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if you set a MySQL password
```

### File Permissions (Linux only)
```bash
cd /opt/lampp/htdocs/dilp-system
chmod 755 -R .
chmod 777 -R uploads/  # If you create an uploads folder
```

---

## 5. First-Time Login

### Access the System
1. Open your web browser
2. Navigate to: `http://localhost/dilp-system`
3. You will be redirected to the login page

### Default Administrator Credentials
```
Username: admin
Email: admin@dilp.gov.ph
Password: admin123
```

### IMPORTANT: Change Default Password
1. After first login, click on your name in the top-right corner
2. Select "Change Password"
3. Enter current password: `admin123`
4. Enter and confirm your new secure password
5. Click "Update Password"

---

## 6. User Guide

### 6.1 Dashboard
The dashboard provides:
- Statistics overview (beneficiaries, proponents, amounts)
- Interactive map of Negros Occidental showing project locations
- Overdue liquidation alerts
- Status distribution charts

### 6.2 Managing Beneficiaries (Individual Recipients)

#### Adding a Beneficiary
1. Navigate to **Beneficiaries** from the sidebar
2. Click **"Add New Beneficiary"** button
3. Fill in required fields:
   - Personal information (name, gender, location)
   - Project details (name, type of worker, amount)
   - Process dates (as they occur)
4. Click **"Save Beneficiary"**

#### Editing a Beneficiary
1. Go to **Beneficiaries** page
2. Click the **pencil icon** (Edit) next to the beneficiary
3. Update the information
4. Click **"Update Beneficiary"**

#### Viewing Details
1. Click the **eye icon** (View) to see complete beneficiary information
2. View all dates, findings, and status history

#### Filtering and Searching
- Use the filter form to narrow results by:
  - Municipality
  - Barangay
  - Status
  - Name or project keyword

### 6.3 Managing Proponents (Group Recipients)

#### Adding a Proponent
1. Navigate to **Proponents** from the sidebar
2. Click **"Add New Proponent"** button
3. Select proponent type:
   - LGU-associated (10-day liquidation deadline)
   - Non-LGU-associated (60-day liquidation deadline)
4. Fill in all required information
5. Click **"Save Proponent"**

#### Tracking Liquidation Deadlines
- System automatically calculates deadlines based on turnover date
- Dashboard shows overdue liquidations in red alert box
- Filter by "Overdue" status to see all delayed liquidations

### 6.4 Map Visualization

#### Understanding Map Markers
- **Blue markers**: Individual Beneficiaries
- **Green markers**: Group Proponents
- Click any marker to view:
  - Name
  - Project title
  - Location
  - Amount
  - Status

#### Adding Location Coordinates
When creating/editing records:
1. Enter latitude and longitude manually, OR
2. Use online tools like Google Maps:
   - Right-click on location
   - Copy coordinates
   - Paste into form

### 6.5 Reports

#### Generating Reports
1. Navigate to **Reports**
2. Select report type:
   - Beneficiaries Summary
   - Proponents Summary
   - Financial Summary
   - Status Distribution
3. Choose date range
4. Select filters (optional)
5. Click **"Generate Report"**
6. Export as PDF or Excel

### 6.6 User Management (Admin Only)

#### Adding Users
1. Navigate to **User Management**
2. Click **"Add New User"**
3. Fill in user details
4. Assign role:
   - **Admin**: Full access
   - **Encoder**: Can add/edit records
   - **User**: View-only access
5. Click **"Create User"**

#### User Roles Explained
- **Admin**: All permissions including user management, deletion
- **Encoder**: Create, read, update records (no delete)
- **User**: Read-only access for monitoring and reporting

---

## 7. Troubleshooting

### Common Issues and Solutions

#### Issue: "Cannot connect to database"
**Solution:**
1. Verify MySQL is running in XAMPP Control Panel
2. Check database credentials in `config/database.php`
3. Ensure database `dilp_monitoring` exists
4. Test connection: `http://localhost/phpmyadmin`

#### Issue: "Page not found" / 404 error
**Solution:**
1. Verify files are in `htdocs/dilp-system` folder
2. Check Apache is running
3. Access via: `http://localhost/dilp-system` (not just localhost)
4. Clear browser cache

#### Issue: Map not loading
**Solution:**
1. Check internet connection (map requires online access)
2. Verify coordinates are entered correctly (latitude, longitude)
3. Check browser console for errors (F12)

#### Issue: Cannot login
**Solution:**
1. Verify database was imported correctly
2. Check users table has default admin user
3. Try default credentials: admin / admin123
4. Clear browser cookies and cache

#### Issue: PHP errors displaying
**Solution:**
1. For production, disable error display:
   - Edit `php.ini` in XAMPP
   - Set `display_errors = Off`
2. For development, keep enabled for debugging

---

## 8. Features Overview

### Current Features
✅ User authentication with role-based access
✅ Individual beneficiary management (CRUD)
✅ Group proponent management (CRUD)
✅ Interactive map visualization
✅ Automatic liquidation deadline calculation
✅ Dashboard statistics and charts
✅ Advanced filtering and search
✅ Activity logging and audit trail
✅ Responsive design (mobile-friendly)
✅ Report generation

### Data Tracked

#### For Individual Beneficiaries:
- Personal information
- Project details
- Amount worth
- Processing dates (compliance, forwarding, approval, turnover, monitoring)
- RPMT findings
- Status tracking

#### For Group Proponents:
- Proponent information
- Control number
- Project details
- Beneficiary counts (total, male, female)
- Financial information (amount, check details, OR)
- Processing dates (30+ date fields)
- Liquidation tracking with automated deadlines
- Source of funds

### Security Features
- Password hashing (bcrypt)
- Session management
- Role-based access control
- Activity logging with IP tracking
- SQL injection prevention (prepared statements)
- XSS protection

---

## Support and Maintenance

### Regular Maintenance Tasks
1. **Database Backup**: Weekly
   - Export via phpMyAdmin
   - Store in secure location
2. **Log Monitoring**: Monthly
   - Review activity logs
   - Check for unusual activity
3. **User Account Review**: Quarterly
   - Deactivate unused accounts
   - Update passwords

### Getting Help
For technical support or questions:
1. Check this documentation first
2. Review activity logs for error messages
3. Contact system administrator
4. Refer to XAMPP documentation: [https://www.apachefriends.org/docs/](https://www.apachefriends.org/docs/)

---

## Appendix

### Default Database Schema
See `database_migrations.sql` for complete schema

### Recommended Browser Settings
- Enable JavaScript
- Allow cookies
- Enable location services (for map features)
- Zoom: 100%

### Performance Tips
1. Regular database optimization
2. Clear old activity logs (older than 1 year)
3. Optimize images before upload
4. Use filters to limit large data loads

---

**Document Version**: 1.0
**Last Updated**: February 2026
**System Version**: 1.0.0

**© 2026 Department of Labor and Employment - Region VI**
