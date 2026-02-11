# DOLE DILP Monitoring System - User Manual

**Version 1.0** | **February 2026**

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Managing Beneficiaries](#4-managing-beneficiaries)
5. [Managing Proponents](#5-managing-proponents)
6. [Map Visualization](#6-map-visualization)
7. [Generating Reports](#7-generating-reports)
8. [User Management](#8-user-management)
9. [Activity Logs](#9-activity-logs)
10. [Tips and Best Practices](#10-tips-and-best-practices)
11. [Frequently Asked Questions](#11-frequently-asked-questions)

---

## 1. Introduction

### 1.1 Purpose
The DOLE DILP (Department of Labor and Employment - Integrated Livelihood Program) Monitoring System is designed to help government offices efficiently track and monitor livelihood program beneficiaries and proponents across Negros Occidental.

### 1.2 Key Features
- Track individual beneficiaries and group proponents
- Interactive map visualization of project locations
- Automated liquidation deadline tracking
- Comprehensive reporting capabilities
- Role-based access control
- Complete audit trail

### 1.3 System Requirements
- Modern web browser (Chrome, Firefox, Edge, Safari)
- JavaScript enabled
- Internet connection (for map features)
- Minimum 1366x768 screen resolution

---

## 2. Getting Started

### 2.1 Accessing the System
1. Open your web browser
2. Navigate to: `http://localhost/dilp-system`
3. You will be redirected to the login page

### 2.2 Logging In
1. Enter your **Username**
2. Enter your **Password**
3. Click **"Sign In"**

**First-time login:**
- Username: `admin`
- Password: `admin123`
- ⚠️ **IMPORTANT**: Change this password immediately after first login

### 2.3 Changing Your Password
1. Click on your name in the top-right corner
2. Select **"Change Password"**
3. Enter your current password
4. Enter your new password
5. Confirm your new password
6. Click **"Update Password"**

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter (recommended)
- At least one number (recommended)
- At least one special character (recommended)

### 2.4 Logging Out
1. Click on your name in the top-right corner
2. Select **"Logout"**
3. You will be redirected to the login page

---

## 3. Dashboard Overview

The dashboard is your home screen and provides a comprehensive overview of the DILP program.

### 3.1 Statistics Cards

**Top Row Statistics:**

1. **Total Beneficiaries**
   - Shows total number of individual beneficiaries
   - Breakdown by gender (Male/Female)
   
2. **Total Proponents**
   - Shows total number of group proponents
   - Breakdown by type (LGU-associated / Non-LGU-associated)
   
3. **Total Beneficiaries (Groups)**
   - Shows total people benefiting from group proponents
   - Breakdown by gender
   
4. **Total Amount**
   - Shows cumulative funding amount
   - Combines individual and group projects

### 3.2 Status Overview Cards

**Individual Beneficiaries Status:**
- **Pending**: Awaiting approval
- **Approved**: Proposal approved
- **Implemented**: Project turned over
- **Monitored**: Post-implementation monitoring completed

**Group Proponents Status:**
- **Pending**: Initial submission
- **Approved**: Approved for funding
- **Implemented**: Project executed
- **Liquidated**: Financial liquidation completed
- **Monitored**: Monitoring completed

### 3.3 Overdue Liquidation Alerts
- Red alert box appears when liquidations are overdue
- Shows proponent name and days overdue
- Click "View all" to see complete list
- **Deadlines:**
  - LGU-associated: 10 days from turnover
  - Non-LGU-associated: 60 days from turnover

### 3.4 Interactive Map
- Displays all approved and implemented projects
- **Blue markers**: Individual beneficiaries
- **Green markers**: Group proponents
- Click any marker to view project details
- Map centered on Negros Occidental

---

## 4. Managing Beneficiaries

Individual beneficiaries are single recipients of the livelihood program.

### 4.1 Viewing Beneficiaries List

**Navigation:**
1. Click **"Beneficiaries"** in the left sidebar
2. You'll see a table of all beneficiaries

**Table Columns:**
- ID
- Full Name
- Gender
- Location (Barangay, Municipality)
- Project Name
- Amount
- Status
- Actions (View/Edit/Delete buttons)

### 4.2 Filtering Beneficiaries

Use the filter form to narrow results:

1. **Search**: Enter name or project keyword
2. **Municipality**: Select specific municipality
3. **Barangay**: Select specific barangay
4. **Status**: Select status (Pending/Approved/Implemented/Monitored)
5. Click **"Filter"** button
6. Click **"Clear"** to reset filters

**Using DataTables:**
- Use the search box for quick text search
- Click column headers to sort
- Use pagination at bottom to navigate pages
- Adjust "Show X entries" to change items per page

### 4.3 Adding a New Beneficiary

**Steps:**
1. Click **"Add New Beneficiary"** button (top-right)
2. Fill in the form sections:

**Personal Information:**
- Last Name (required)
- First Name (required)
- Middle Name (optional)
- Suffix (optional: Jr., Sr., III, etc.)
- Gender (required: Male/Female)
- Barangay (required)
- Municipality (required)
- Contact Number (optional)

**Project Information:**
- Project Name/Title (required) - Example: "Banana Cue Vending", "Cooked Viand Vending"
- Type of Worker (optional) - Example: "Self-employed", "Home-based"
- Amount Worth (required) - Enter amount in pesos

**Process Tracking:**
- Noted Findings/Comments (optional)
- Date Complied by Proponent/ACP
- Date Forwarded to RO6 for RPMT Evaluation
- RPMT Findings (text field)
- Date Approved
- Date Forwarded of Approved Proposal to NOFO
- Date of Turn-over
- Date of Monitoring

**Location (Optional):**
- Latitude (decimal degrees)
- Longitude (decimal degrees)
- *Used for map visualization*

**Status:**
- Select current status: Pending/Approved/Implemented/Monitored

3. Click **"Save Beneficiary"**

**Tips:**
- Fields marked with * are required
- Dates should be in YYYY-MM-DD format
- Coordinates can be obtained from Google Maps

### 4.4 Editing a Beneficiary

**Steps:**
1. Go to Beneficiaries list
2. Find the beneficiary
3. Click the **pencil icon** (Edit button)
4. Update the information
5. Click **"Update Beneficiary"**

**Note:** All changes are logged in the activity log with your user ID and timestamp.

### 4.5 Viewing Beneficiary Details

**Steps:**
1. Click the **eye icon** (View button)
2. You'll see a detailed view with:
   - Complete personal information
   - Full project details
   - All dates and timeline
   - RPMT findings
   - Current status
   - Map location (if coordinates provided)

### 4.6 Deleting a Beneficiary

**⚠️ Admin Only Feature**

**Steps:**
1. Click the **trash icon** (Delete button)
2. Confirm deletion in the popup
3. Beneficiary will be permanently removed

**Warning:** This action cannot be undone. Consider updating status instead of deleting.

### 4.7 Getting Coordinates for Map

**Method 1: Google Maps**
1. Open Google Maps
2. Right-click on the location
3. Click on the coordinates shown
4. Copy latitude and longitude
5. Paste into beneficiary form

**Method 2: GPS Device**
1. Use GPS-enabled device
2. Record coordinates at project site
3. Convert to decimal degrees if necessary
4. Enter in beneficiary form

---

## 5. Managing Proponents

Group proponents are organizations or associations receiving livelihood assistance.

### 5.1 Understanding Proponent Types

**LGU-associated Proponents:**
- Associated with Local Government Units
- Liquidation deadline: **10 days** from turnover date
- Typically faster processing

**Non-LGU-associated Proponents:**
- Independent organizations or associations
- Liquidation deadline: **60 days** from turnover date
- May require additional documentation

### 5.2 Viewing Proponents List

**Navigation:**
1. Click **"Proponents"** in the left sidebar
2. View table of all proponents

**Table Columns:**
- ID
- Control Number
- Proponent Name
- Project Title
- Type (LGU/Non-LGU)
- Amount
- Beneficiaries Count
- Status
- Actions

### 5.3 Filtering Proponents

**Available Filters:**
- Search (name, project, control number)
- Proponent Type (LGU/Non-LGU)
- District
- Status
- Category (Formation/Enhancement/Restoration)
- Date Range (approval dates)

### 5.4 Adding a New Proponent

**Steps:**
1. Click **"Add New Proponent"** button
2. Fill in the comprehensive form:

**Basic Information:**
- Proponent Type (required: LGU-associated or Non-LGU-associated)
- Date Received (DILP)
- Control Number (unique identifier)
- Number of Proposal Copies Submitted
- Date Received (copies)

**Proponent Details:**
- District
- Name of Proponent/ACP (required)
- Project Title (required)
- Amount (required, in pesos)

**Beneficiary Information:**
- Number of Associations
- Total Number of Beneficiaries (required)
- Number of Male Beneficiaries
- Number of Female Beneficiaries
- Type of Beneficiaries
- Category (required: Formation/Enhancement/Restoration)
- Recipient Barangays/ACPs

**Process Dates:**
- Letter of Intent - Date Received
- Date Forwarded to RO6 for RPMT Evaluation
- RPMT Findings (text field)
- Date Complied by Proponent/ACP
- Date Complied by Proponent/ACP/NOFO
- Date Forwarded of Approved Proposal to NOFO
- Date Approved

**Financial Information:**
- Date of Check Release
- Check Number
- Date Issued (check)
- Official Receipt (OR) Number
- OR Date Issued

**Implementation:**
- Date of Turn-over
- Date Implemented
- Date Liquidated
- Date of Monitoring
- Source of Funds

**Location (for map):**
- Latitude
- Longitude

**Status & Comments:**
- Current Status
- Noted Findings/Comments

3. Click **"Save Proponent"**

**Important Notes:**
- Liquidation deadline is automatically calculated when turnover date is entered
- System will alert if liquidation becomes overdue
- Male + Female beneficiaries should equal Total Beneficiaries

### 5.5 Tracking Liquidation Deadlines

**Automatic Calculation:**
- System calculates deadline when turnover date is entered
- LGU: Turnover Date + 10 days
- Non-LGU: Turnover Date + 60 days

**Monitoring Overdue Liquidations:**
1. Check dashboard for overdue alerts
2. Click "View all overdue liquidations"
3. Or filter proponents by "Overdue" status

**Updating Liquidation Date:**
1. Edit the proponent record
2. Enter Date Liquidated
3. Status automatically updates

### 5.6 Understanding Proponent Categories

**Formation:**
- New associations being formed
- Initial capital build-up
- First-time livelihood projects

**Enhancement:**
- Existing projects being improved
- Additional equipment or training
- Expansion of operations

**Restoration:**
- Projects being rehabilitated
- Recovery from setbacks
- Rebuilding operations

---

## 6. Map Visualization

### 6.1 Understanding the Map

**Map Features:**
- Shows Negros Occidental
- Displays only approved/implemented/monitored projects
- Interactive markers with details
- Zoom and pan capabilities

**Marker Colors:**
- **Blue**: Individual Beneficiaries
- **Green**: Group Proponents

### 6.2 Using the Map

**Viewing Project Details:**
1. Click any marker on the map
2. Popup shows:
   - Name
   - Project title
   - Location (Barangay/Municipality or District)
   - Amount
   - Number of beneficiaries (for proponents)
   - Current status

**Navigation:**
- **Zoom In**: Click + button or scroll up
- **Zoom Out**: Click - button or scroll down
- **Pan**: Click and drag the map
- **Reset View**: Reload page

### 6.3 Adding Projects to Map

**Requirements:**
- Project must have status: Approved, Implemented, or Monitored
- Must have latitude and longitude coordinates

**Steps:**
1. When creating/editing beneficiary or proponent
2. Enter coordinates in Latitude and Longitude fields
3. Save the record
4. Project will appear on map automatically

---

## 7. Generating Reports

### 7.1 Types of Reports

**Available Reports:**
1. **Beneficiaries Summary**
   - All individual beneficiaries
   - Filter by date, location, status

2. **Proponents Summary**
   - All group proponents
   - Filter by type, district, category

3. **Financial Summary**
   - Total amounts disbursed
   - By municipality/district
   - By date range

4. **Status Distribution**
   - Count by status
   - Gender distribution
   - Geographic distribution

### 7.2 Generating a Report

**Steps:**
1. Click **"Reports"** in sidebar
2. Select report type
3. Set filters:
   - Date range (From - To)
   - Location (Municipality/District)
   - Status
   - Other relevant filters
4. Click **"Generate Report"**
5. View report on screen

### 7.3 Exporting Reports

**Export Formats:**
- **PDF**: For printing and archiving
- **Excel**: For further analysis

**Steps:**
1. Generate the report
2. Click **"Export as PDF"** or **"Export as Excel"**
3. File will download to your computer
4. Save with appropriate filename

**Naming Convention:**
```
DILP_[ReportType]_[Date]
Example: DILP_Beneficiaries_20260203.pdf
```

---

## 8. User Management

**⚠️ Admin Only Feature**

### 8.1 User Roles Explained

**Admin:**
- Full system access
- Can create/edit/delete users
- Can delete records
- Access to activity logs
- User management

**Encoder:**
- Can create and edit records
- Cannot delete records
- Cannot manage users
- Can view reports

**User:**
- View-only access
- Can view all records
- Can generate reports
- Cannot create/edit/delete

### 8.2 Viewing Users

**Steps:**
1. Click **"User Management"** in sidebar
2. View list of all system users
3. See username, full name, role, status

### 8.3 Creating a New User

**Steps:**
1. Click **"Add New User"** button
2. Fill in form:
   - Username (required, unique)
   - Email (required, unique)
   - Full Name (required)
   - Password (required, min 8 characters)
   - Confirm Password
   - Role (required: Admin/Encoder/User)
3. Click **"Create User"**

**Tips:**
- Choose strong usernames (not easily guessable)
- Use official email addresses
- Set strong temporary passwords
- Instruct users to change password on first login

### 8.4 Editing a User

**Steps:**
1. Find user in list
2. Click **"Edit"** button
3. Update information:
   - Full name
   - Email
   - Role
   - Active status
4. Click **"Update User"**

**Note:** Username cannot be changed once created.

### 8.5 Resetting User Password

**Steps:**
1. Edit the user
2. Enter new password in "New Password" field
3. Confirm new password
4. Click **"Update User"**
5. Inform user of new password securely

### 8.6 Deactivating a User

**Instead of deleting:**
1. Edit the user
2. Set "Active" status to "No"
3. Click **"Update User"**

**Effect:**
- User cannot log in
- All past activities remain in system
- Can be reactivated anytime

---

## 9. Activity Logs

**⚠️ Admin Only Feature**

### 9.1 Purpose of Activity Logs

Activity logs track all user actions for:
- Security monitoring
- Audit compliance
- Troubleshooting
- Accountability

### 9.2 Viewing Activity Logs

**Steps:**
1. Click **"Activity Logs"** in sidebar
2. View chronological list of activities

**Log Information:**
- Date and time
- User who performed action
- Action type (create/update/delete/login/logout)
- Table affected
- Record ID
- Description
- IP Address

### 9.3 Filtering Activity Logs

**Filter by:**
- Date range
- User
- Action type
- Table (beneficiaries/proponents/users)

### 9.4 Understanding Log Entries

**Common Activities:**

```
2026-02-03 10:30:15 | admin | login | users | 1 | User logged in | 192.168.1.100

2026-02-03 10:35:22 | encoder01 | create | beneficiaries | 45 | Created new beneficiary | 192.168.1.105

2026-02-03 10:40:18 | encoder01 | update | beneficiaries | 45 | Updated beneficiary | 192.168.1.105
```

---

## 10. Tips and Best Practices

### 10.1 Data Entry Best Practices

**Consistency:**
- Use consistent naming conventions
- Always use proper capitalization
- Enter dates in correct format (YYYY-MM-DD)
- Keep project names descriptive

**Accuracy:**
- Double-check amounts before saving
- Verify coordinates before entering
- Review beneficiary counts for proponents
- Confirm dates are chronologically correct

**Completeness:**
- Fill in all required fields
- Add optional information when available
- Include contact numbers for follow-up
- Document findings and comments

### 10.2 Workflow Recommendations

**For Beneficiaries:**
1. Create record when proposal received
2. Set status to "Pending"
3. Update dates as process advances
4. Add RPMT findings when available
5. Update status to "Approved" when approved
6. Enter turnover date when implemented
7. Change status to "Implemented"
8. Add monitoring date and notes
9. Update status to "Monitored"

**For Proponents:**
1. Create record with complete basic info
2. Enter control number immediately
3. Update process dates as they occur
4. Track financial details (check, OR)
5. Enter turnover date (triggers liquidation deadline)
6. Monitor liquidation deadline
7. Update when liquidated
8. Complete monitoring process

### 10.3 Using Filters Effectively

**Strategy:**
- Start broad, then narrow down
- Use combinations of filters
- Save common filter combinations mentally
- Clear filters between different searches

### 10.4 Map Best Practices

**Coordinates:**
- Use decimal degrees format
- Latitude first, then longitude
- Negros Occidental ranges:
  - Latitude: ~9.5° to ~11.5°
  - Longitude: ~122.5° to ~123.5°
- Verify on map before saving

### 10.5 Security Best Practices

**Passwords:**
- Change default password immediately
- Use strong, unique passwords
- Don't share login credentials
- Change passwords regularly (every 90 days)

**Access:**
- Log out when leaving workstation
- Don't leave system unattended while logged in
- Report suspicious activity
- Use only assigned user accounts

### 10.6 Regular Maintenance

**Daily:**
- Review overdue liquidations
- Check new submissions
- Update pending dates

**Weekly:**
- Generate status reports
- Review activity logs (Admin)
- Update monitoring schedules

**Monthly:**
- Generate comprehensive reports
- Review user access (Admin)
- Archive completed projects

---

## 11. Frequently Asked Questions

### Q1: I forgot my password. What should I do?
**A:** Contact your system administrator to reset your password. Only administrators can reset passwords.

### Q2: Can I delete a beneficiary I created by mistake?
**A:** Only administrators can delete records. Encoders and Users cannot delete. Contact your administrator, or edit the record to mark it as inactive.

### Q3: What if I enter the wrong amount?
**A:** Simply edit the beneficiary or proponent record and update the amount field. All changes are logged.

### Q4: How do I get coordinates for a project location?
**A:** Use Google Maps: Right-click on the location → Click coordinates → Copy and paste into the form.

### Q5: Why isn't my project showing on the map?
**A:** Check that: 1) Coordinates are entered, 2) Status is Approved/Implemented/Monitored, 3) Coordinates are valid for Negros Occidental.

### Q6: What happens when a liquidation deadline passes?
**A:** System shows a red alert on the dashboard. The proponent appears in overdue list. Follow up with proponent for liquidation.

### Q7: Can I export data to Excel for analysis?
**A:** Yes, use the Reports module to generate and export reports in Excel format.

### Q8: How far back does the activity log go?
**A:** Activity logs are kept for one year by default. Older logs are archived by administrators.

### Q9: Can I edit someone else's entry?
**A:** Yes, Encoders and Admins can edit any record, regardless of who created it.

### Q10: What's the difference between turnover and monitoring dates?
**A:** Turnover date is when project is handed over to beneficiary. Monitoring date is when post-implementation check is conducted.

### Q11: How do I filter for LGU proponents only?
**A:** In Proponents page, use the "Proponent Type" filter and select "LGU-associated", then click Filter.

### Q12: Can I print a beneficiary's details?
**A:** Yes, open the beneficiary details view and use your browser's print function (Ctrl+P or Cmd+P).

### Q13: What should I do if the system is running slow?
**A:** Use filters to limit results, close unnecessary browser tabs, clear browser cache, or contact administrator.

### Q14: How often should I backup the system?
**A:** Administrators should backup daily. Users don't need to worry about backups.

### Q15: Can I access this system from home?
**A:** Only if your organization has set up remote access. Check with your IT administrator.

---

## Getting Help

### Technical Support
- **Contact:** Your System Administrator
- **Email:** [admin@dole.gov.ph]
- **Office Hours:** Monday-Friday, 8:00 AM - 5:00 PM

### Training
- Request additional training from your supervisor
- Refer to this manual for step-by-step guidance
- Practice with test data before entering real information

### Reporting Issues
When reporting problems:
1. Note what you were trying to do
2. What error message appeared (if any)
3. Your username (don't share password)
4. Screenshot if possible
5. Contact administrator with details

---

**End of User Manual**

**Version 1.0** | **February 2026**
**DOLE Region VI - Negros Occidental**
