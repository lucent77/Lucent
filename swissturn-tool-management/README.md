# 🔧 Swissturn Tool Management System

A comprehensive web application designed to systematically manage tool inventory, track tool lifespan, log job usage, and enable automated restocking through supplier integration.

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Technical Stack](#technical-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Security](#security)
- [Support](#support)

---

## 🎯 Overview

The **Swissturn Tool Management System** prevents tool-related problems through systematic management, including:

- Real-time tool inventory tracking
- Lifecycle management (time-based or usage-based)
- Transaction logging for every tool movement
- Automated purchase order generation
- Comprehensive reporting and analytics

### System Purpose
- Prevent tool-related problems through systematic management
- Track tool inventory in real-time
- Monitor tool lifespan (time-based or usage-based)
- Log job usage for every tool transaction
- Automate restocking through supplier integration
- Generate reports for informed decision-making

---

## ✨ Features

### User Roles

#### **Administrator**
- Full CRUD operations on tools, users, and suppliers
- Purchase order management and approval
- Comprehensive reporting and analytics
- System configuration and settings
- Audit trail access

#### **Worker**
- Tool check-out and check-in
- Job usage logging
- Personal transaction history
- Tool availability viewing

### Tool Management
- Complete tool master data management
- Category-based organization
- Supplier linkage with model numbers
- Stock level tracking with minimum thresholds
- Dual-mode lifespan tracking:
  - **Time-based**: Days from first use
  - **Usage-based**: Operation count

### Status Indicators
- 🟢 **Active**: Tool in good condition
- 🟠 **Near Expiry**: 80% of lifespan used
- 🔴 **Expired**: Lifespan exceeded
- 🔵 **Needs Reorder**: Stock below minimum

### Automated Features
- Automatic purchase order generation when:
  - Stock drops below minimum threshold
  - Tool approaches or reaches end of lifespan
- Priority-based ordering system
- Supplier information ready for export

### Reporting & Analytics
- Tool usage trends with charts (limited to 30 data points to prevent infinite charts)
- Top tools by usage frequency
- Reorder recommendations
- Lifecycle status reports
- Complete audit trail

---

## 🛠 Technical Stack

### Frontend
- **JavaScript**: Vanilla ES6+ (no frameworks)
- **CSS Framework**: Tailwind CSS v3.3+
- **Charts**: Chart.js with data point limiting
- **Responsive**: Mobile-first design

### Backend
- **Language**: PHP 7.4+
- **Architecture**: RESTful API
- **Database**: MySQL 8.0+
- **Security**: Prepared statements, password hashing (bcrypt)

### Hosting
- **Provider**: Hostinger
- **Database**: MySQL
  - Server: 127.0.0.1:3306
  - Database: u359033001_TOOL
  - User: u359033001_TOOL

---

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache or Nginx web server
- mod_rewrite enabled (for Apache)

### Step 1: Upload Files
1. Upload all files to your web server directory
2. Ensure proper file permissions:
   - Files: 644
   - Directories: 755
   - Executables: 755

### Step 2: Database Setup
1. Access phpMyAdmin or MySQL command line
2. Create database (if not exists):
   ```sql
   CREATE DATABASE u359033001_TOOL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import schema:
   ```bash
   mysql -u u359033001_TOOL -p u359033001_TOOL < database/schema.sql
   ```
4. Import test data (optional):
   ```bash
   mysql -u u359033001_TOOL -p u359033001_TOOL < database/seed_data.sql
   ```

### Step 3: Configuration
1. Verify database configuration in `config/database.php`:
   ```php
   define('DB_SERVER', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_DATABASE', 'u359033001_TOOL');
   define('DB_USERNAME', 'u359033001_TOOL');
   define('DB_PASSWORD', 'YOUR_PASSWORD');
   ```

2. Update environment setting in `config/constants.php`:
   ```php
   define('ENVIRONMENT', 'production'); // Change from 'development'
   ```

### Step 4: First Access
1. Navigate to your domain: `http://yourdomain.com`
2. You'll be redirected to login page
3. Use default admin credentials:
   - **Username**: `admin`
   - **Password**: `admin123`
4. **IMPORTANT**: Change default password immediately!

### Step 5: Initial Setup
1. Add tool categories (or use defaults)
2. Add suppliers
3. Add tools with proper lifecycle settings
4. Create user accounts for workers
5. Set minimum stock thresholds for each tool

---

## ⚙️ Configuration

### System Constants
Edit `config/constants.php` to customize:

```php
// Application
define('APP_NAME', 'Swissturn Tool Management');
define('APP_TIMEZONE', 'Europe/Zurich');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Tool Lifecycle
define('NEAR_EXPIRY_THRESHOLD', 0.8); // 80%

// Charts (prevents infinite charts)
define('CHART_MAX_DATA_POINTS', 30);
define('CHART_DEFAULT_DAYS', 30);
```

### HTTPS Configuration
Uncomment in `.htaccess` for production:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📖 Usage

### For Administrators

#### Managing Tools
1. Go to **Tools** page
2. Click **+ Add Tool**
3. Fill in tool details:
   - Name, category, size
   - Supplier and model number
   - Current and minimum stock
   - Lifespan type and limit
4. Save and monitor status

#### Reviewing Purchase Orders
1. Go to **Purchase Orders** page
2. Review auto-generated orders
3. Adjust quantities if needed
4. Approve orders
5. Export for supplier submission

#### Generating Reports
1. Go to **Reports** page
2. Select report type:
   - Usage Trends
   - Top Tools
   - Reorder Report
   - Lifecycle Report
3. View charts and tables
4. Export as PDF/CSV (if implemented)

### For Workers

#### Checking Out Tools
1. Go to **Check Out/In** page
2. Select tool from dropdown
3. Enter job ID and task description
4. Click **Check Out**
5. Tool stock is automatically reduced

#### Checking In Tools
1. Go to **Check Out/In** page
2. Select tool and choose "Check In"
3. Add completion notes
4. Click **Check In**
5. Tool stock is automatically increased

#### Viewing History
1. Go to **My History** page
2. View all your transactions
3. Filter by date or job ID

---

## 🔌 API Documentation

### Authentication
All API endpoints require authentication via PHP session.

### Base URL
```
/api/
```

### Endpoints

#### Tools API (`/api/tools.php`)
- `GET` - Retrieve all tools or specific tool
- `POST` - Create new tool (admin only)
- `PUT` - Update tool (admin only)
- `DELETE` - Delete tool (admin only)

#### Transactions API (`/api/transactions.php`)
- `GET` - Retrieve transactions
- `POST` - Create checkout/checkin

#### Purchase Orders API (`/api/orders.php`)
- `GET` - Retrieve orders (admin only)
- `POST` - Create order (admin only)
- `PUT` - Update order status (admin only)

#### Reports API (`/api/reports.php`)
- `GET ?type=usage` - Usage trends
- `GET ?type=top_tools` - Most used tools
- `GET ?type=reorder` - Reorder recommendations
- `GET ?type=lifecycle` - Lifecycle status
- `GET ?type=dashboard` - Dashboard statistics

### Response Format
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful",
    "timestamp": "2025-10-31 10:30:00"
}
```

---

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection
- ✅ CSRF token validation
- ✅ Session management
- ✅ Role-based access control (RBAC)
- ✅ Audit logging
- ✅ Input validation and sanitization

### Best Practices
1. Change default passwords immediately
2. Use HTTPS in production
3. Regular database backups
4. Keep PHP and MySQL updated
5. Monitor audit logs regularly

---

## 📱 Responsive Design

The system is fully responsive and works on:
- 💻 Desktop (1920px+)
- 💻 Laptop (1366px - 1920px)
- 📱 Tablet (768px - 1366px)
- 📱 Mobile (320px - 768px)

---

## 🆘 Support

### Test Credentials
After importing `seed_data.sql`:

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**Worker Accounts:**
- Username: `worker1` / Password: `password123`
- Username: `worker2` / Password: `password123`

### Troubleshooting

#### Database Connection Errors
1. Verify credentials in `config/database.php`
2. Check MySQL service is running
3. Verify user has proper permissions

#### Session Issues
1. Check PHP session configuration
2. Verify write permissions on session directory
3. Clear browser cookies

#### Chart Display Issues
1. Charts are limited to 30 data points by design
2. Check browser console for JavaScript errors
3. Verify Chart.js library is loading

---

## 📝 License

This software is proprietary to Swissturn and licensed for internal use only.

---

## 🔄 Version History

**Version 1.0.0** (2025-10-31)
- Initial release
- Core tool management features
- User role system
- Lifecycle management (time and usage-based)
- Purchase order automation
- Report generation with chart data point limiting
- RESTful API
- Responsive UI with Tailwind CSS

---

## 📊 Project Structure

```
swissturn-tool-management/
├── index.php                 # Main entry point
├── README.md                 # This file
├── .htaccess                 # Apache configuration
├── config/                   # Configuration files
│   ├── database.php
│   └── constants.php
├── database/                 # Database files
│   ├── schema.sql
│   └── seed_data.sql
├── api/                      # REST API endpoints
│   ├── tools.php
│   ├── transactions.php
│   ├── orders.php
│   ├── reports.php
│   ├── users.php
│   └── suppliers.php
├── auth/                     # Authentication
│   ├── login.php
│   ├── logout.php
│   └── session.php
├── pages/                    # Application pages
│   ├── admin/               # Admin pages
│   │   ├── dashboard.php
│   │   ├── tools.php
│   │   ├── orders.php
│   │   ├── suppliers.php
│   │   ├── users.php
│   │   └── reports.php
│   └── worker/              # Worker pages
│       ├── dashboard.php
│       ├── checkout.php
│       └── history.php
├── components/              # Reusable components
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── alerts.php
├── includes/                # Utility files
│   ├── functions.php
│   └── validation.php
└── assets/                  # Frontend assets
    ├── css/
    │   └── custom.css
    └── js/
        ├── main.js
        ├── charts.js
        ├── tools.js
        └── transactions.js
```

---

**Built with ❤️ for Swissturn**

For questions or support, please contact your system administrator.
