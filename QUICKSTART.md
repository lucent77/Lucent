# CREODENT Web Portal - Quick Start Guide

Get up and running in 5 minutes!

## 🚀 Quick Installation

### 1. Upload Files
Upload the entire project to your Hostinger web directory.

### 2. Configure
```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` - update only these sections:
```php
// Database (already configured for Hostinger)
'database' => [
    'database' => 'u359033001_CADCAM_WORK',
    'username' => 'u359033001_CADCAM_WORK',
    'password' => 'Creo$10001',
],

// Evolution API
'evolution' => [
    'base_url' => 'YOUR_EVOLUTION_URL',
    'username' => 'YOUR_EVO_USERNAME',
    'password' => 'YOUR_EVO_PASSWORD',
],
```

### 3. Setup Database

Via phpMyAdmin in Hostinger:
```sql
-- Run these two files in order:
1. database/schema.sql
2. database/seed.sql
```

### 4. Login

Navigate to: `https://your-domain.com/login`

**Default credentials**:
- Username: `admin`
- Password: `password123`

**⚠️ Change this password immediately after first login!**

## 📋 Daily Operations

### Viewing Cases
1. Click "Cases" in navigation
2. Use filters to search
3. Click any case to view/edit

### Creating a Case
1. Cases → New Case
2. Fill in details
3. Save

### Editing a Case
1. Open case
2. Edit master info or use tabs (SOLIDEX, 3D PRINT, CoCr)
3. Save (optimistic locking prevents conflicts)

### Running Manual Import
1. Admin → Import Monitor
2. Click "Run Import Now"
3. Wait for completion

## 🔧 Cron Setup (Important!)

Set up automated Evolution imports:

**Hostinger cPanel → Cron Jobs**:
```bash
Command: /usr/bin/php /home/your-username/domains/your-domain.com/public_html/cron/import_evo.php
Schedule: 0 * * * * (hourly)
```

**Test manually**:
```bash
php cron/import_evo.php
```

## 👥 User Roles

| Role | Can Do |
|------|--------|
| **super_admin** | Everything |
| **admin** | Most admin functions |
| **manager** | Manage department, assign work |
| **worker** | View and edit assigned work |

## 🏗 Three Work Types

The system supports 3 work domains from the old VB.NET program:

1. **SOLIDEX** - Solidex work processing
2. **3D PRINT** - 3D printing operations
3. **ZEST / CoCr** - CoCr and Zest work

Each case can have items in multiple departments. Use the tabs to switch between them.

## 🛡 Security Features

✅ **Optimistic Locking**: Multiple users can open the same case, but only one edit succeeds. Others get a conflict warning and must refresh.

✅ **Audit Logging**: Every change is logged with user, timestamp, and before/after values.

✅ **CSRF Protection**: All forms are protected against cross-site request forgery.

✅ **Password Hashing**: Passwords stored using bcrypt.

## 📊 Dashboard

The dashboard shows:
- Cases by status (last 30 days by default)
- Cases by department
- Daily case chart (date-filtered, max 90 days)
- Recent cases
- My assigned items (for workers)
- Recent imports

**Important**: All charts use date ranges to prevent infinite data loading. Use the date selector to change the range (7, 30, or 90 days).

## 🔍 Troubleshooting

### Can't Login
- Check username/password
- Verify database is accessible
- Check if user status is "active"

### Evolution Import Fails
- Admin → Test Connection
- Check credentials in config.php
- Verify Evolution portal is online
- Check firewall/network access

### "CONFLICT" Error When Saving
- This means someone else edited the case
- Click "Refresh" to see latest version
- Re-apply your changes
- This is **normal behavior** preventing data loss

### Charts Show "Too Much Data"
- This shouldn't happen - charts are date-limited
- If it does, use the date selector to reduce range
- Maximum is 90 days
- Report if this occurs

## 📁 Project Structure

```
/config         → Configuration files
/public         → Web root (index.php, .htaccess)
/app            → Application code
  /Controllers  → HTTP request handlers
  /Services     → Business logic
  /Repositories → Database access
/views          → HTML templates
/database       → SQL schema and seeds
/cron           → Scheduled jobs
```

## 🔗 Key URLs

- **Login**: `/login`
- **Dashboard**: `/dashboard`
- **Cases**: `/cases`
- **Admin**: `/admin`
- **API Health**: `/api/health`

## 📞 Support

- **Technical Issues**: Contact IT Department
- **User Questions**: Ask your department manager
- **Bug Reports**: Document and report to system administrator

## 📚 Full Documentation

For complete documentation, see:
- `README.md` - Comprehensive system documentation
- `DEPLOYMENT_CHECKLIST.md` - Deployment guide

---

**Need More Help?** Read the full README.md for detailed information on all features, configuration options, and troubleshooting steps.
