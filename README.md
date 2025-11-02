# CREODENT Integrated Web Operations System

A comprehensive web-based multi-user system that replaces the VB.NET desktop data-entry application, providing integrated workflow management for dental CAD/CAM operations with Evolution Web Portal integration.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [System Architecture](#system-architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Deployment](#deployment)
- [Usage](#usage)
- [Development](#development)
- [Security](#security)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

This system modernizes the CREODENT workflow by:

- **Replacing Google Sheets** with a proper MySQL database as the single source of truth
- **Integrating Evolution Web Portal (V18)** for automated case imports via XML API
- **Supporting multi-user concurrent editing** with optimistic locking to prevent data conflicts
- **Providing role-based access control** for administrators, managers, and workers
- **Maintaining backward compatibility** with the 3 VB.NET data formats (SOLIDEX, 3D PRINT, CoCr/Zest)
- **Enforcing date-bounded queries** to prevent infinite chart/data loading

## ✨ Features

### Core Functionality

- **Multi-User Web Interface**: Browser-based access for concurrent users
- **Role-Based Access Control**: Super Admin, Admin, Manager, Worker roles
- **Three Work Types**: SOLIDEX, 3D PRINT, ZEST/CoCr (matching VB.NET program)
- **Evolution Integration**: Automated case imports from Evolution Web Portal V18
- **Optimistic Locking**: Prevents concurrent edit conflicts
- **Audit Logging**: Complete history of all changes
- **Dashboard Analytics**: Date-filtered statistics and charts (max 90 days)
- **Case Management**: Full CRUD operations with pagination
- **Department Management**: Configurable departments and assignments

### Data Safety

- **Optimistic Locking**: Version-based conflict detection
- **Audit Trails**: All changes logged with user, timestamp, before/after states
- **Transaction Support**: Database transactions for multi-table operations
- **Backup Compatible**: Department-specific JSON mirrors for legacy compatibility

### Evolution Web Portal Integration

- **Automated Imports**: Scheduled cron job imports cases from Evolution
- **Date-Bounded**: Always uses date ranges (default: last 7 days)
- **Retry Logic**: 3 retries with exponential backoff
- **Connection Testing**: Built-in health check endpoint
- **Supported Events**:
  - `account_login` - Connection test
  - `cases_caselist` - Get cases for date range
  - `case_caseinformation` - Get case details
  - `case_noteget` / `case_noteadd` - Case notes
  - `case_imagelist` - Case images

## 🏗 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Web Browser (Multi-User)                  │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTPS
┌────────────────────────▼────────────────────────────────────┐
│                   Apache / Nginx (Hostinger)                 │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                     PHP 8 Application                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Controllers (Auth, Case, Admin, API)                 │  │
│  └────────────────┬─────────────────────────────────────┘  │
│  ┌────────────────▼─────────────────────────────────────┐  │
│  │  Services (CaseService, EvolutionClient, SheetSync)  │  │
│  └────────────────┬─────────────────────────────────────┘  │
│  ┌────────────────▼─────────────────────────────────────┐  │
│  │  Repositories (Case, User, Department, AuditLog)     │  │
│  └────────────────┬─────────────────────────────────────┘  │
└────────────────────┼────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│          MySQL Database (u359033001_CADCAM_WORK)             │
│  - cases, case_items, users, departments                     │
│  - solidex_orders, print3d_orders, cocr_orders (JSON)       │
│  - case_audit_logs, import_jobs                             │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│             Evolution Web Portal V18 (External)               │
│                    XML POST API Integration                   │
└──────────────────────────────────────────────────────────────┘
```

### Project Structure

```
/project-root
  /config
    config.php                  # Main configuration
    config.example.php          # Configuration template
  /public
    index.php                   # Front controller
    .htaccess                   # Apache rewrite rules
  /app
    Database.php                # PDO connection manager
    /Controllers
      AuthController.php        # Login/logout
      DashboardController.php   # Dashboard with date-filtered stats
      CaseController.php        # Case CRUD with optimistic locking
      AdminController.php       # Admin functions
      ApiController.php         # RESTful API endpoints
    /Services
      EvolutionClient.php       # Evolution V18 XML API client
      CaseService.php           # Case business logic
      AuthService.php           # Authentication/authorization
      SheetSyncService.php      # Google Sheets compatibility layer
    /Repositories
      CaseRepository.php        # Case data access
      CaseItemRepository.php    # Case item data access
      UserRepository.php        # User data access
      DepartmentRepository.php  # Department data access
      AuditLogRepository.php    # Audit log data access
  /views
    /layouts
      header.php                # Common header with navigation
      footer.php                # Common footer
    /auth
      login.php                 # Login form
    /dashboard
      index.php                 # Dashboard with charts
    /cases
      index.php                 # Case list with filters
      show.php                  # Case detail with 3 tabs
    /admin
      index.php                 # Admin dashboard
      users.php                 # User management
      departments.php           # Department management
      imports.php               # Import monitor
  /database
    schema.sql                  # Complete database schema
    seed.sql                    # Sample data
  /cron
    import_evo.php              # Automated Evolution import
```

## 📦 Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache 2.4+ or Nginx
- **PHP Extensions**:
  - PDO
  - pdo_mysql
  - curl
  - simplexml
  - json
  - session
  - mbstring

## 🚀 Installation

### Step 1: Upload Files to Hostinger

Upload all files to your Hostinger hosting directory:

```bash
# Via FTP, upload the entire project to:
/home/your-username/domains/your-domain.com/public_html/
```

### Step 2: Configure Directory Permissions

```bash
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 600 config/config.php
```

### Step 3: Create Configuration

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` and update:

```php
// Database connection (already set for Hostinger)
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'u359033001_CADCAM_WORK',
    'username' => 'u359033001_CADCAM_WORK',
    'password' => 'Creo$10001',
],

// Evolution Web Portal credentials
'evolution' => [
    'enabled' => true,
    'base_url' => 'https://your-evolution-portal.com',
    'username' => 'your_evo_username',
    'password' => 'your_evo_password',
],
```

## 💾 Database Setup

### Create Database and Tables

1. **Access Hostinger MySQL via phpMyAdmin**

2. **Run Schema Creation**:
   ```sql
   -- Copy and paste contents of database/schema.sql
   ```

3. **Load Seed Data**:
   ```sql
   -- Copy and paste contents of database/seed.sql
   ```

### Default Users

After running `seed.sql`, the following users are available:

| Username | Password    | Role         | Department |
|----------|-------------|--------------|------------|
| admin    | password123 | super_admin  | N/A        |
| manager1 | password123 | manager      | SOLIDEX    |
| worker1  | password123 | worker       | SOLIDEX    |
| worker2  | password123 | worker       | PRINT3D    |
| qc1      | password123 | worker       | QC         |

**⚠️ IMPORTANT**: Change the admin password immediately after first login!

## ⚙️ Configuration

### Evolution Web Portal Setup

1. Get your Evolution API credentials
2. Update `config/config.php`:
   ```php
   'evolution' => [
       'enabled' => true,
       'base_url' => 'https://your-evolution-portal.com',
       'username' => 'your_api_username',
       'password' => 'your_api_password',
       'timeout' => 10,
       'max_retries' => 3,
   ],
   ```

### Cron Job Setup (Evolution Import)

Set up a cron job to run the Evolution import regularly:

**Via Hostinger cPanel**:
1. Go to Advanced → Cron Jobs
2. Add a new cron job:
   - **Command**: `/usr/bin/php /home/your-username/domains/your-domain.com/public_html/cron/import_evo.php`
   - **Frequency**: Every hour (e.g., `0 * * * *`)

**Manual Test**:
```bash
php /path/to/cron/import_evo.php
```

### Session Configuration

For production, ensure `config/config.php` has:

```php
'session' => [
    'secure' => true,  // Requires HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
],
```

## 🌐 Deployment

### Apache Configuration (.htaccess)

The included `.htaccess` file handles URL rewriting. Ensure `mod_rewrite` is enabled.

### SSL/HTTPS Setup

**Always use HTTPS in production**. Hostinger provides free SSL certificates:

1. Go to Hostinger control panel
2. Navigate to SSL section
3. Install free SSL certificate
4. Update `config.php`:
   ```php
   'app' => [
       'url' => 'https://your-domain.com',
   ],
   'session' => [
       'secure' => true,
   ],
   ```

### Performance Optimization

1. **Enable OPcache** (usually enabled by default on Hostinger)
2. **Set proper cache headers** (already configured in `.htaccess`)
3. **Use production mode**:
   ```php
   'app' => [
       'env' => 'production',
       'debug' => false,
   ],
   ```

## 📖 Usage

### Logging In

1. Navigate to `https://your-domain.com/login`
2. Enter username and password
3. Default admin credentials: `admin` / `password123`

### Dashboard

- View statistics for last 7, 30, or 90 days
- See recent cases
- View assigned items (for workers)
- Monitor recent imports

### Managing Cases

**Creating a Case**:
1. Go to Cases → New Case
2. Fill in case details
3. Save

**Editing a Case**:
1. Open case from Cases list
2. Edit master information or department-specific tabs
3. Changes are auto-saved with optimistic locking

**Three Work Type Tabs**:
- **SOLIDEX**: Solidex work items
- **3D PRINT**: 3D printing work items
- **ZEST / CoCr**: CoCr and Zest work items

### Evolution Import

**Manual Import**:
1. Go to Admin → Import Monitor
2. Click "Run Import Now"
3. Monitor progress

**Automatic Import**:
- Runs via cron job (hourly recommended)
- Imports cases from last 7 days by default
- Check logs in Import Monitor

### User Management

**Adding Users** (Admin only):
1. Go to Admin → User Management
2. Click Add User
3. Set username, password, role, department
4. Save

**Roles**:
- **super_admin**: Full system access
- **admin**: Most admin functions except system settings
- **manager**: Department-level management and assignment
- **worker**: View and edit assigned work

## 🔒 Security

### Important Security Features

1. **Password Hashing**: Uses bcrypt for password storage
2. **CSRF Protection**: All POST requests require CSRF token
3. **SQL Injection Prevention**: Prepared statements for all queries
4. **Session Security**: HTTPOnly, Secure (HTTPS), SameSite cookies
5. **Optimistic Locking**: Prevents concurrent edit conflicts
6. **XSS Protection**: All output escaped with `htmlspecialchars()`

### Security Best Practices

1. **Change default passwords immediately**
2. **Use HTTPS only** (enforce in production)
3. **Keep `config.php` out of public directory**
4. **Set proper file permissions** (600 for config files)
5. **Enable error logging** but disable display_errors in production
6. **Regular backups** of database and files
7. **Update PHP regularly**

### Configuration Security

```php
// config/config.php should NEVER be committed to version control
// Use chmod 600 config/config.php
```

## 🐛 Troubleshooting

### Database Connection Errors

```
Error: Database connection failed
```

**Solution**:
- Verify MySQL credentials in `config/config.php`
- Check if MySQL service is running
- Verify database exists and user has permissions
- Test connection: `mysql -h 127.0.0.1 -u u359033001_CADCAM_WORK -p`

### Evolution API Errors

```
Error: Evolution API call failed after 3 attempts
```

**Solution**:
- Check Evolution credentials in config
- Test connection: Admin → Test Connection
- Verify Evolution portal is accessible
- Check firewall rules
- Review import logs in `import_jobs` table

### Optimistic Locking Conflicts

```
Error: CONFLICT - This case was updated by another user
```

**Solution**:
- This is **expected behavior** when multiple users edit simultaneously
- User should refresh the page to see latest version
- Re-apply their changes if still needed
- This prevents data loss from concurrent edits

### Session Issues

```
Error: Not logged in / Session expired
```

**Solution**:
- Check session configuration in `config.php`
- Verify `session.save_path` is writable
- Check if cookies are blocked by browser
- Ensure HTTPS if `secure` session flag is enabled

### Chart/Data Loading Issues

**If charts show too much data**:
- All charts are date-filtered by default (30 days)
- Maximum 90 days allowed
- This is **by design** to prevent infinite data loading
- Adjust date range selector in dashboard

### Permission Errors

```
Error: Access denied. Insufficient permissions.
```

**Solution**:
- Check user role in Users table
- Verify department assignment
- Review AuthService permissions logic
- Contact administrator to adjust user role

## 📝 Development

### Adding New Features

1. **Create Repository** for data access layer
2. **Create Service** for business logic
3. **Create Controller** for HTTP handling
4. **Create View** for UI
5. **Update Routes** in `public/index.php`

### Database Migrations

When adding new fields:

```sql
-- Always use ALTER TABLE with careful testing
ALTER TABLE cases ADD COLUMN new_field VARCHAR(255) NULL;
```

### Testing Evolution Integration

```bash
# Test connection
curl -X POST https://your-domain.com/admin/test-evolution \
  -H "X-CSRF-Token: YOUR_TOKEN"

# Manual import
php cron/import_evo.php
```

## 📄 License

Proprietary - CREODENT Internal Use Only

## 👥 Support

For support, contact:
- **Technical Issues**: IT Department
- **User Training**: Department Managers
- **System Administration**: System Administrator

---

**Version**: 1.0.0
**Last Updated**: 2025-11-02
**Database**: `u359033001_CADCAM_WORK`
**Environment**: Hostinger MySQL
