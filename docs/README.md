# DOLE DILP Monitoring and Encoding System

A comprehensive web-based system for monitoring and tracking the Department of Labor and Employment's Integrated Livelihood Program (DILP) beneficiaries and proponents in Negros Occidental, Philippines.

## Overview

This system enables government offices to efficiently record, track, and monitor information about both individual beneficiaries and group proponents of the DILP program, with features including:

- Individual beneficiary management
- Group proponent tracking (LGU and Non-LGU associated)
- Interactive map visualization of approved projects
- Automated liquidation deadline calculation
- Comprehensive reporting and analytics
- Role-based access control

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Modern web browser
- Minimum 512MB RAM
- 500MB free disk space

## Features

### Individual Beneficiaries Module
- Full CRUD operations
- Personal information tracking
- Project details management
- Processing dates and status
- RPMT findings documentation
- Geolocation mapping

### Group Proponents Module
- LGU-associated and Non-LGU-associated tracking
- Automatic liquidation deadline calculation (10 days for LGU, 60 days for Non-LGU)
- Control number management
- Beneficiary counts (total, male, female)
- Financial tracking (amounts, checks, receipts)
- Comprehensive date tracking (30+ date fields)

### Dashboard
- Real-time statistics
- Interactive map of Negros Occidental
- Visual project markers (blue for individuals, green for groups)
- Status distribution charts
- Overdue liquidation alerts

### Reporting
- Customizable date range reports
- Multiple export formats (PDF, Excel)
- Financial summaries
- Beneficiary demographics

### User Management
- Three role levels: Admin, Encoder, User
- Activity logging with IP tracking
- Secure password management
- Session control

## Map Visualization

The system features an interactive map showing:
- **Blue Markers**: Individual beneficiaries
- **Green Markers**: Group proponents
- Click markers to view project details
- Automatic filtering by status (approved, implemented, monitored)

## User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full system access, user management, all CRUD operations, reports |
| **Encoder** | Create, read, update beneficiaries and proponents, view reports |
| **User** | View-only access for monitoring and reporting |

## Data Tracked

### Beneficiaries (Individuals)
- Personal info (name, gender, location, contact)
- Project details and type of worker
- Amount worth
- Process dates (compliance, forwarding, approval, turnover, monitoring)
- RPMT findings and noted comments
- Status tracking

### Proponents (Groups)
- Proponent type and name
- Control number and document tracking
- Project title and amount
- Association and beneficiary counts
- Category (Formation, Enhancement, Restoration)
- Financial details (check number, OR, dates)
- Liquidation tracking
- Source of funds
- Complete process timeline

## Security Features

- Bcrypt password hashing
- Role-based access control (RBAC)
- SQL injection prevention (prepared statements)
- XSS protection
- Session management
- Activity logging with IP addresses
- Automatic liquidation deadline enforcement

## Technical Stack

- **Backend**: PHP 7.4+ (native PHP, no framework required)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: Bootstrap 5.3, jQuery
- **Maps**: Leaflet.js with OpenStreetMap
- **Tables**: DataTables.js
- **Server**: Apache (via XAMPP)

## 📁 Project Structure

```
dilp-system/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── Auth.php              # Authentication handler
├── models/
│   ├── Beneficiary.php       # Beneficiary model
│   └── Proponent.php         # Proponent model
├── assets/
│   ├── css/                  # Custom stylesheets
│   └── js/                   # Custom scripts
├── index.php                 # Dashboard
├── login.php                 # Login page
├── beneficiaries.php         # Beneficiary list
├── beneficiary-form.php      # Add/Edit beneficiary
├── proponents.php            # Proponent list
├── proponent-form.php        # Add/Edit proponent
├── reports.php               # Reporting module
├── users.php                 # User management
├── database_migrations.sql   # Database schema
├── INSTALLATION_GUIDE.md     # Detailed setup instructions
└── README.md                 # This file
```

## Troubleshooting

### Cannot connect to database
- Check MySQL is running in XAMPP
- Verify database credentials in `config/database.php`
- Ensure `dilp_monitoring` database exists

### Map not loading
- Requires internet connection for map tiles
- Check browser console (F12) for errors
- Verify latitude/longitude format

### Login issues
- Default: admin / admin123
- Clear browser cache and cookies
- Check users table in database