# CREODENT CADCAM Work Management System

A comprehensive web-based work management system for dental CADCAM operations, built with PHP and MySQL. Manages work across multiple departments (Solidex, 3D Print, CoCr/ZEST) with real-time collaboration, Evolution Web Portal integration, and advanced workflow tracking.

## Features

### Core Functionality
- **Multi-Department Management**: Solidex, 3D Print, CoCr/ZEST, Korea, QC
- **Case Management**: Create, track, and manage dental cases from start to finish
- **Work Item Tracking**: Individual tooth/part level tracking with status management
- **Real-time Collaboration**: Optimistic locking prevents concurrent edit conflicts
- **Role-Based Access Control (RBAC)**: Super Admin, Admin, Manager, Worker roles
- **Comprehensive Audit Logging**: Track all changes with before/after snapshots

### Integration
- **Evolution Web Portal V18**: Automatic case synchronization via XML API
- **Google Sheets Compatible**: JSON storage for legacy compatibility
- **Automated Sync**: Cron-based automatic data imports

### Technical Features
- Modern PHP 8+ with OOP architecture
- MySQL 8+ with optimized indexes and stored procedures
- Responsive UI with Tailwind CSS
- RESTful API endpoints
- Session-based authentication with CSRF protection
- Optimistic locking for concurrent operations
- Comprehensive error handling and logging

## Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache 2.4+ with mod_rewrite
- **Extensions**: PDO, PDO_MySQL, JSON, SimpleXML, cURL

## Installation

### 1. Clone/Upload Project

```bash
git clone https://github.com/yourusername/creodent-cadcam.git
cd creodent-cadcam
```

### 2. Configure Database

Edit `config/config.php` with your database credentials:

```php
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'u359033001_CADCAM_WORK',
    'username' => 'u359033001_CADCAM_WORK',
    'password' => 'Creo$10001',
],
```

### 3. Run Installation

```bash
php install.php
```

This will:
- Create the database (if needed)
- Install all tables, indexes, and stored procedures
- Create default admin user
- Set up required directories

### 4. Configure Web Server

#### Apache Configuration

Point your DocumentRoot to the `public` directory:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/creodent-cadcam/public

    <Directory /path/to/creodent-cadcam/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/creodent-error.log
    CustomLog ${APACHE_LOG_DIR}/creodent-access.log combined
</VirtualHost>
```

Enable required modules:
```bash
a2enmod rewrite
a2enmod headers
systemctl restart apache2
```

### 5. Set Permissions

```bash
chmod 755 public
chmod 755 logs
chmod 755 uploads
chown -R www-data:www-data logs uploads
```

### 6. First Login

Visit your website and login with default credentials:
- **Username**: `admin`
- **Password**: `admin123`

**⚠️ IMPORTANT**: Change this password immediately after first login!

## Configuration

### Evolution Web Portal Integration

Edit `config/config.php`:

```php
'evolution' => [
    'enabled' => true,
    'base_url' => 'https://your-evolution-portal.com',
    'username' => 'your_username',
    'password' => 'your_password',
    'timeout' => 30,
    'retry_attempts' => 3,
],
```

### Automatic Sync (Cron Job)

Add to crontab for hourly sync:

```bash
crontab -e
```

Add line:
```
0 * * * * php /path/to/creodent-cadcam/cron/sync_evolution.php >> /path/to/creodent-cadcam/logs/cron.log 2>&1
```

Manual sync:
```bash
php cron/sync_evolution.php 7  # Sync last 7 days
```

## Usage

### Dashboard
- Overview statistics (new cases, in progress, completed)
- Department workload summary
- My assigned work items
- Due today and overdue cases

### Case Management
1. **Create Case**: Manual entry or automatic import from Evolution
2. **Add Work Items**: Define work for each department
3. **Assign Items**: Assign specific items to workers
4. **Track Progress**: Monitor item status changes
5. **Complete**: Mark items/cases as done

### Department Workflow
- **Solidex**: General dental prosthetics
- **3D Print**: 3D printing operations
- **CoCr/ZEST**: Co-Cr alloy work
- **Korea**: Korea operations
- **QC**: Quality control inspection

### User Management (Admin)
- Create users with roles
- Assign departments
- Manage permissions
- View activity logs

## Architecture

### Directory Structure
```
creodent-cadcam/
├── app/
│   ├── Controllers/     # Request handlers
│   ├── Core/            # Core framework classes
│   ├── Models/          # Data models
│   ├── Repositories/    # Database access layer
│   └── Services/        # Business logic
├── config/              # Configuration files
├── cron/                # Scheduled jobs
├── database/            # SQL schema
├── logs/                # Application logs
├── public/              # Web root
│   ├── css/
│   ├── js/
│   └── index.php       # Entry point
├── uploads/             # File uploads
└── views/               # HTML templates
    ├── auth/
    ├── cases/
    ├── dashboard/
    └── admin/
```

### Database Schema

**Core Tables:**
- `users` - System users
- `departments` - Work departments
- `cases` - Main case records
- `case_items` - Individual work items
- `case_audit_logs` - Change tracking
- `import_jobs` - Sync job history

**Department-Specific:**
- `solidex_orders` - Solidex data (JSON)
- `print3d_orders` - 3D Print data (JSON)
- `cocr_orders` - CoCr/ZEST data (JSON)

### Security Features
- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention (PDO prepared statements)
- XSS protection (output escaping)
- Session security (httponly, secure, samesite)
- Role-based access control
- Input validation

## API Endpoints

### Authentication
- `POST /login` - User login
- `GET /logout` - User logout
- `GET /api/user` - Get current user

### Cases
- `GET /cases` - List cases (with filters)
- `GET /cases/{id}` - Get case details
- `POST /cases` - Create case
- `POST /cases/{id}` - Update case
- `POST /cases/{id}/status` - Update status

### Items
- `POST /items/assign` - Assign item to user
- `POST /items/status` - Update item status
- `POST /items/bulk-assign` - Bulk assignment

### Admin
- `GET /admin/users` - User management
- `POST /admin/users` - Create user
- `POST /admin/users/{id}` - Update user
- `GET /admin/audit-logs` - View logs

### Import
- `POST /admin/import/test-connection` - Test Evolution connection
- `POST /admin/import/cases` - Import cases
- `POST /admin/import/sync-recent` - Sync recent cases

## Troubleshooting

### Database Connection Errors
- Verify credentials in `config/config.php`
- Check MySQL service is running: `systemctl status mysql`
- Ensure database user has proper permissions

### 404 Errors on All Pages
- Check Apache mod_rewrite is enabled
- Verify `.htaccess` file exists in `public/`
- Check DocumentRoot points to `public/` directory

### Permission Denied Errors
- Check directory permissions: `chmod 755 logs uploads`
- Check owner: `chown -R www-data:www-data logs uploads`

### Evolution Sync Failing
- Test connection: Admin → Import → Test Connection
- Check credentials in config
- Verify Evolution Portal URL is accessible
- Check logs: `tail -f logs/import-*.log`

### Charts Extending Infinitely
The system includes CSS fixes to prevent infinite chart height:
```css
.chart-container {
    position: relative;
    height: 300px !important;
    max-height: 300px !important;
}
```

## Development

### Adding New Features
1. Create repository in `app/Repositories/`
2. Add service in `app/Services/`
3. Create controller in `app/Controllers/`
4. Add routes in `public/index.php`
5. Create views in `views/`

### Database Migrations
- Modify `database/schema.sql`
- Run: `mysql -u user -p database < database/schema.sql`

### Logging
Logs are stored in `logs/` directory:
- `app-*.log` - Application logs
- `error-*.log` - Error logs
- `import-*.log` - Import job logs
- `audit-*.log` - Audit logs

## Support

For issues, questions, or contributions:
- GitHub Issues: [Create Issue]
- Email: admin@creodent.com
- Documentation: [Link to docs]

## License

Proprietary - CREODENT Corporation
All rights reserved.

## Changelog

### Version 1.0.0 (2025-11-02)
- Initial release
- Multi-department support
- Evolution Portal integration
- Optimistic locking
- Comprehensive audit logging
- Role-based access control
- Automated sync via cron

---

**Note**: This system handles sensitive dental case information. Ensure proper security measures are in place including:
- HTTPS enabled in production
- Strong passwords enforced
- Regular backups
- Firewall protection
- Regular security updates
