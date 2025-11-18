# Penske Logistics - Laredo Distribution Center
# Yard Management System (YMS) - Phase 1

## Table of Contents
1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Installation Instructions](#installation-instructions)
4. [Configuration](#configuration)
5. [User Roles & Permissions](#user-roles--permissions)
6. [System Features](#system-features)
7. [File Structure](#file-structure)
8. [Database Schema](#database-schema)
9. [Usage Guide](#usage-guide)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The Penske Laredo YMS Phase 1 is a comprehensive yard management system designed for the Aptiv CS & EDS distribution center. It manages trailer check-in/out, yard movements, dock door assignments, and provides detailed reporting capabilities.

**Key Features:**
- Dual-gate check-in/out (East & West)
- Real-time yard and dock visibility
- Move tracking and audit trail
- Role-based access control (6 roles)
- Comprehensive reporting (8 reports)
- Dock door exclusivity enforcement
- Dwell time monitoring

---

## System Requirements

### Server Requirements
- **Web Server:** Apache 2.4+ (XAMPP 8.2 recommended)
- **PHP:** 8.2 or higher
- **MySQL:** 8.0 or higher
- **Extensions Required:**
  - PDO
  - PDO_MySQL
  - mbstring
  - json

### Browser Requirements
- Modern browser (Chrome, Firefox, Edge, Safari)
- JavaScript enabled
- Cookies enabled for session management

---

## Installation Instructions

### Step 1: Install XAMPP
1. Download XAMPP 8.2 from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp` (Windows) or `/opt/lampp` (Linux/Mac)
3. Start Apache and MySQL services

### Step 2: Extract YMS Files
1. Navigate to XAMPP's htdocs directory:
   - Windows: `C:\xampp\htdocs\`
   - Linux/Mac: `/opt/lampp/htdocs/`
2. Create directory structure:
   ```
   htdocs/
   └── ladcportal/
       └── yms/
   ```
3. Extract/copy all YMS files into the `yms` folder

### Step 3: Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose file: `ladcportal/yms/database.sql`
4. Click "Go" to execute
5. Verify database `ladc_yms` is created with all tables

### Step 4: Configure Database Connection
1. Open `ladcportal/yms/config/db.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ladc_yms');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Default XAMPP is empty
   ```

### Step 5: Set Permissions (Linux/Mac only)
```bash
chmod -R 755 /opt/lampp/htdocs/ladcportal/yms/
chmod -R 777 /opt/lampp/htdocs/ladcportal/yms/config/
```

### Step 6: Access the System
1. Open browser and navigate to:
   ```
   http://localhost/ladcportal/yms/public/login.php
   ```
2. Use default credentials:
   - **Admin:** admin / password123
   - **Guard:** guard1 / password123
   - **XD Clerk:** xd_clerk1 / password123

---

## Configuration

### Database Configuration
File: `config/db.php`
- Modify database credentials
- Adjust PDO options if needed

### Session Configuration
File: `config/auth.php`
- Session timeout is browser-based (closes on browser exit)
- To add timeout, modify session settings in PHP.ini

### Environment Settings
For production deployment:
1. Change all default passwords immediately
2. Update `DB_PASS` to a strong password
3. Enable HTTPS (uncomment in `.htaccess`)
4. Review security headers in `.htaccess`

---

## User Roles & Permissions

### 1. GUARD
- **Access:** Gate check-in/out screens only
- **Permissions:** Check-in and check-out trailers at assigned gate
- **Cannot:** Assign yards, docks, create moves, edit trailer info

### 2. XD_TRAFFIC_CLERK (Cross-Dock Traffic Clerk)
- **Access:** Full yard management
- **Permissions:** Check-in/out, assign yards/docks, create moves, edit trailers, view reports
- **Cannot:** Access admin panel

### 3. FG_TRAFFIC_CLERK (Finished Goods Traffic Clerk)
- **Access:** Full yard management
- **Permissions:** Same as XD Traffic Clerk
- **Cannot:** Access admin panel

### 4. SHIPPING_CLERK
- **Access:** Full yard management
- **Permissions:** Same as Traffic Clerks
- **Cannot:** Access admin panel

### 5. SUPERVISOR
- **Access:** Full yard management + oversight
- **Permissions:** All clerk permissions + can override actions
- **Cannot:** Manage users or system configuration

### 6. ADMIN
- **Access:** Full system access
- **Permissions:** Everything including user management, lookup tables, audit logs

---

## System Features

### 1. Gate Operations
**East Gate:** `http://localhost/ladcportal/yms/public/gate_east.php`
- Automatic assignment to East Yard
- Duplicate trailer detection
- Full check-in form with all trailer details

**West Gate:** `http://localhost/ladcportal/yms/public/gate_west.php`
- Automatic assignment to West Yard
- Same features as East Gate

### 2. Yard Management
- **East Yard View:** Monitor all trailers in East Yard
- **West Yard View:** Monitor all trailers in West Yard
- Color-coded dwell time indicators:
  - Normal: < 24 hours
  - Info (blue): 24-48 hours
  - Warning (yellow): 48-72 hours
  - Danger (red): > 72 hours

### 3. Dock Door Board
- Visual tile-based layout
- 52 dock doors (1-30 West, 31-52 East)
- Color-coded by load status:
  - Gray: Empty door
  - Blue: Loaded trailer
  - Yellow: Empty trailer
  - Red: Live load
- Click tile to view trailer details
- Auto-refresh every 30 seconds

### 4. Move Management
- Create moves between yards and docks
- Assign spotters to moves
- Automatic move ID generation: `MV-YYYY-MM-DD-00001`
- Validation for dock door availability
- Full audit trail

### 5. Reporting (8 Reports)
1. **Daily Yard Snapshot:** All active trailers
2. **Daily Move Log:** Last 24 hours of moves
3. **Trailer Dwell Time:** All trailers with time on site
4. **Trailers at Dock:** Current dock occupancy
5. **Trailer History:** Movement history by trailer #
6. **Departed Trailers:** Last 7 days of departures
7. **Clerk Productivity:** Move counts by user
8. **Guard Shack Activity:** Check-in/out statistics

All reports support CSV export.

### 6. Admin Panel
- **User Management:** Create, edit, deactivate users
- **Lookup Tables:** Manage carriers, spotters, load reasons, purposes
- **Audit Log:** Complete system activity log with filters

---

## File Structure

```
/ladcportal/yms/
│
├── database.sql                    # Database schema and seed data
├── README.md                       # This file
│
├── /config/                        # Configuration files
│   ├── db.php                      # Database connection
│   ├── auth.php                    # Authentication functions
│   └── utils.php                   # Utility functions
│
├── /models/                        # Data models
│   ├── User.php                    # User model
│   ├── Trailer.php                 # Trailer model
│   └── Move.php                    # Move model
│
├── /public/                        # Public web files
│   ├── .htaccess                   # Apache configuration
│   ├── login.php                   # Login page
│   ├── logout.php                  # Logout handler
│   ├── index.php                   # Main dashboard
│   ├── gate_east.php               # East gate check-in/out
│   ├── gate_west.php               # West gate check-in/out
│   ├── yard_east.php               # East yard view
│   ├── yard_west.php               # West yard view
│   ├── dock_board.php              # Dock door board
│   ├── trailers.php                # Trailer master list
│   ├── trailer_detail.php          # Trailer detail view
│   ├── move_entry.php              # Create move form
│   ├── checkout_trailer.php        # Quick checkout handler
│   ├── reports.php                 # All reports
│   ├── admin_users.php             # User management
│   ├── admin_lookups.php           # Lookup table management
│   └── admin_audit.php             # Audit log viewer
│
└── /views/                         # View templates
    └── /partials/
        ├── header.php              # HTML header & navbar include
        ├── navbar.php              # Navigation menu
        └── footer.php              # Footer & scripts
```

---

## Database Schema

### Main Tables
- **users:** System users with roles
- **trailers:** Master trailer records
- **moves:** Movement history
- **spotters:** Spotter directory
- **carriers:** Carrier lookup
- **load_reasons:** Load reason lookup
- **purposes:** Purpose lookup
- **audit_log:** Complete audit trail

### Key Relationships
- trailers → users (created_by_user_id, last_guard_user_id)
- moves → trailers (trailer_id)
- moves → users (performed_by_user_id)
- audit_log → users (user_id)

### Indexes
All foreign keys and frequently queried fields are indexed for performance.

---

## Usage Guide

### Daily Operations

#### Guard Check-In Process
1. Login with guard credentials
2. Navigate to gate (East or West)
3. Click "Check-In" tab
4. Enter trailer details:
   - Trailer number (required, uppercase A-Z 0-9)
   - Carrier, type, seal, references
   - Load status (required)
5. System auto-assigns to yard based on gate
6. Submit to complete

#### Clerk Move Process
1. Login with clerk credentials
2. Navigate to "Create Move"
3. Select trailer from dropdown
4. Choose destination (Yard, Dock, or Departed)
5. Select specific yard area or dock door
6. Optionally assign spotter
7. Add notes if needed
8. Submit to execute move

#### Supervisor Override
Supervisors can access all clerk functions and view all activities.

### Reporting Workflow
1. Navigate to "Reports" menu
2. Select desired report from dropdown
3. Apply date filters if applicable
4. Review on-screen data
5. Click "Export to CSV" to download

### Admin Tasks

#### Creating New User
1. Login as admin
2. Go to Admin → Manage Users
3. Click "Create New User"
4. Enter username, password, full name, role
5. Submit to create

#### Managing Lookup Tables
1. Go to Admin → Manage Lookups
2. Select table (Carriers, Spotters, etc.)
3. Add new items as needed
4. Toggle active/inactive status

---

## Troubleshooting

### Cannot Access Login Page
**Problem:** 404 Not Found
**Solution:**
1. Verify Apache is running in XAMPP
2. Check path: `http://localhost/ladcportal/yms/public/login.php`
3. Ensure files are in correct directory
4. Check Apache error logs

### Database Connection Failed
**Problem:** PDO connection error
**Solution:**
1. Verify MySQL is running in XAMPP
2. Check credentials in `config/db.php`
3. Verify database `ladc_yms` exists
4. Test connection via phpMyAdmin

### Login Not Working
**Problem:** Invalid credentials
**Solution:**
1. Verify database seed data was imported
2. Use default credentials: admin / password123
3. Check `users` table has entries
4. Passwords are hashed with bcrypt

### Dock Door Shows as Occupied (but isn't)
**Problem:** Stale data from previous session
**Solution:**
1. Check `trailers` table for dock_door value
2. Verify `current_location_type = 'DOCK'`
3. If incorrect, update or check out the trailer
4. Run query:
   ```sql
   UPDATE trailers SET dock_door = NULL, current_location_type = 'DEPARTED'
   WHERE id = [trailer_id];
   ```

### Move ID Generation Fails
**Problem:** Duplicate move_id error
**Solution:**
1. Check moves table for existing move_id
2. Verify `generateMoveId()` function
3. Ensure proper date/time on server
4. Clear any incomplete transactions

### CSV Export Not Working
**Problem:** Headers already sent
**Solution:**
1. Check for whitespace before `<?php` tags
2. Verify no output before export
3. Check PHP error logs
4. Ensure browser accepts downloads

### Session Lost / Logged Out Unexpectedly
**Problem:** Session expiring
**Solution:**
1. Check session settings in `php.ini`
2. Verify cookies are enabled in browser
3. Check session save path permissions
4. Increase `session.gc_maxlifetime` if needed

---

## Security Considerations

### Production Deployment Checklist
- [ ] Change all default passwords
- [ ] Use strong database password
- [ ] Enable HTTPS
- [ ] Restrict database access
- [ ] Set proper file permissions
- [ ] Enable error logging (not display)
- [ ] Regular backups
- [ ] Update PHP and MySQL regularly
- [ ] Review audit logs periodically
- [ ] Implement IP whitelisting if needed

### Password Policy
- Minimum 8 characters recommended
- Use password_hash() for storage (already implemented)
- Regular password rotation for admin accounts

---

## Support & Maintenance

### Backup Procedures
**Daily Backup:**
```bash
mysqldump -u root -p ladc_yms > backup_$(date +%Y%m%d).sql
```

**Restore from Backup:**
```bash
mysql -u root -p ladc_yms < backup_20250118.sql
```

### Log Files
- Apache: `xampp/apache/logs/error.log`
- MySQL: `xampp/mysql/data/*.err`
- PHP: Check `phpinfo()` for error_log location

### Version Information
- **System:** Penske Laredo YMS Phase 1
- **Version:** 1.0.0
- **Release Date:** January 2025
- **PHP Version:** 8.2+
- **MySQL Version:** 8.0+

---

## Contact & Credits

**Developed for:**
Penske Logistics - Laredo Distribution Center
Aptiv CS & EDS Operations

**Technical Specifications:**
- Pure PHP 8.2 (no frameworks)
- Bootstrap 5 frontend
- MySQL 8.0 with InnoDB
- PDO prepared statements
- Role-based access control
- Full audit logging

---

## License

This system is proprietary software developed for Penske Logistics.
Unauthorized copying, distribution, or modification is prohibited.

---

**END OF DOCUMENTATION**

For additional support or questions, contact your system administrator.
