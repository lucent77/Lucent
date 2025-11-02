# CREODENT Web Portal - Deployment Checklist

Use this checklist to ensure proper deployment of the CREODENT system on Hostinger.

## Pre-Deployment

- [ ] **Review System Requirements**
  - [ ] PHP 8.0+ available on hosting
  - [ ] MySQL 5.7+ available
  - [ ] Required PHP extensions enabled (PDO, curl, simplexml, json, mbstring)

- [ ] **Prepare Evolution API Credentials**
  - [ ] Evolution Web Portal URL
  - [ ] API Username
  - [ ] API Password
  - [ ] Test credentials work with Evolution portal

## File Upload

- [ ] **Upload Project Files**
  - [ ] Upload all files to hosting directory
  - [ ] Ensure proper directory structure is maintained
  - [ ] Verify `public/` is set as document root (or adjust accordingly)

- [ ] **Set File Permissions**
  ```bash
  chmod 755 public
  chmod 644 public/.htaccess
  chmod 644 public/index.php
  chmod 600 config/config.php
  ```

## Configuration

- [ ] **Create config.php**
  - [ ] Copy `config/config.example.php` to `config/config.php`
  - [ ] Update database credentials:
    - Host: `127.0.0.1`
    - Database: `u359033001_CADCAM_WORK`
    - Username: `u359033001_CADCAM_WORK`
    - Password: `Creo$10001`
  - [ ] Update Evolution credentials
  - [ ] Set `app.env` to `production`
  - [ ] Set `app.debug` to `false`
  - [ ] Update `app.url` to your domain
  - [ ] Enable session security (secure: true, httponly: true)

## Database Setup

- [ ] **Access Hostinger MySQL**
  - [ ] Login to Hostinger control panel
  - [ ] Open phpMyAdmin
  - [ ] Select database `u359033001_CADCAM_WORK`

- [ ] **Run SQL Scripts**
  - [ ] Execute `database/schema.sql` to create tables
  - [ ] Execute `database/seed.sql` to load sample data
  - [ ] Verify all tables created successfully:
    - [ ] users
    - [ ] departments
    - [ ] cases
    - [ ] case_items
    - [ ] case_audit_logs
    - [ ] import_jobs
    - [ ] solidex_orders
    - [ ] print3d_orders
    - [ ] cocr_orders
    - [ ] sessions

## SSL/HTTPS Setup

- [ ] **Enable SSL Certificate**
  - [ ] Install free SSL via Hostinger control panel
  - [ ] Verify HTTPS access works
  - [ ] Update config.php to use https:// URL
  - [ ] Test that HTTP redirects to HTTPS

## Cron Job Setup

- [ ] **Configure Evolution Import Cron**
  - [ ] Access Hostinger Cron Jobs section
  - [ ] Add new cron job:
    - Command: `/usr/bin/php /home/your-username/domains/your-domain.com/public_html/cron/import_evo.php`
    - Schedule: Every hour (e.g., `0 * * * *`)
  - [ ] Test manually: `php cron/import_evo.php`
  - [ ] Verify import_jobs table is populated

## Initial Testing

- [ ] **Test Login**
  - [ ] Navigate to https://your-domain.com/login
  - [ ] Login as admin (username: `admin`, password: `password123`)
  - [ ] Verify redirect to dashboard

- [ ] **Test Database Connection**
  - [ ] Dashboard loads without errors
  - [ ] Statistics display correctly
  - [ ] Recent cases show (if any)

- [ ] **Test Evolution Connection**
  - [ ] Go to Admin → Test Connection
  - [ ] Verify connection succeeds
  - [ ] If fails, check credentials and firewall

- [ ] **Test Manual Import**
  - [ ] Go to Admin → Import Monitor
  - [ ] Click "Run Import Now"
  - [ ] Monitor import_jobs table for results
  - [ ] Check if cases were imported

- [ ] **Test Case Management**
  - [ ] Create a new case manually
  - [ ] Edit the case
  - [ ] Verify optimistic locking (open in two browsers, edit both)
  - [ ] Check audit log shows changes

## Security Hardening

- [ ] **Change Default Passwords**
  - [ ] Login as admin
  - [ ] Change admin password from default `password123`
  - [ ] Change all other default user passwords

- [ ] **Verify Security Settings**
  - [ ] `config.php` has permission 600 (not readable by others)
  - [ ] Session uses secure cookies (HTTPS only)
  - [ ] CSRF protection working (test form submissions)
  - [ ] XSS protection enabled in headers

- [ ] **Configure Error Logging**
  - [ ] Verify `logging.enabled` is true in config
  - [ ] Create `storage/logs` directory if needed
  - [ ] Set write permissions: `chmod 755 storage/logs`
  - [ ] Test error logging works

## User Setup

- [ ] **Create Real Users**
  - [ ] Go to Admin → User Management
  - [ ] Create accounts for all users
  - [ ] Assign correct departments
  - [ ] Assign correct roles
  - [ ] Test each user can login

- [ ] **Configure Departments**
  - [ ] Go to Admin → Departments
  - [ ] Verify SOLIDEX, PRINT3D, COCR departments exist
  - [ ] Add any additional departments needed
  - [ ] Update department descriptions

## Final Verification

- [ ] **Functionality Checks**
  - [ ] Dashboard loads and shows stats
  - [ ] Cases list loads with pagination
  - [ ] Case detail shows 3 tabs (SOLIDEX, 3D PRINT, CoCr)
  - [ ] Case editing works (save master info, save items)
  - [ ] Audit log shows history
  - [ ] Admin functions accessible
  - [ ] Evolution import runs successfully
  - [ ] Cron job executes on schedule

- [ ] **Cross-Browser Testing**
  - [ ] Test in Chrome
  - [ ] Test in Firefox
  - [ ] Test in Safari (if available)
  - [ ] Test in Edge

- [ ] **Mobile Testing**
  - [ ] Test on mobile device
  - [ ] Verify responsive layout
  - [ ] Verify navigation works

- [ ] **Multi-User Testing**
  - [ ] Open same case in two browsers
  - [ ] Edit in browser 1, save
  - [ ] Edit in browser 2, save
  - [ ] Verify conflict detection works

## Backup Setup

- [ ] **Configure Automated Backups**
  - [ ] Set up daily database backups via Hostinger
  - [ ] Set up file backups
  - [ ] Test backup restoration process
  - [ ] Document backup schedule

## Documentation

- [ ] **User Training**
  - [ ] Create user accounts for training
  - [ ] Walk through login process
  - [ ] Demonstrate dashboard
  - [ ] Show case management
  - [ ] Explain 3 work types
  - [ ] Document common workflows

- [ ] **Admin Training**
  - [ ] User management
  - [ ] Department configuration
  - [ ] Import monitoring
  - [ ] Troubleshooting common issues

## Go-Live

- [ ] **Pre-Launch**
  - [ ] All checklist items completed
  - [ ] All tests passing
  - [ ] Users trained
  - [ ] Backup system verified

- [ ] **Launch**
  - [ ] Notify users of go-live
  - [ ] Monitor system for first 24 hours
  - [ ] Be ready for support requests

- [ ] **Post-Launch**
  - [ ] Monitor import_jobs daily for first week
  - [ ] Check error logs daily
  - [ ] Gather user feedback
  - [ ] Document any issues and resolutions

## Emergency Contacts

- **System Administrator**: ______________________
- **Database Administrator**: ______________________
- **Hostinger Support**: support@hostinger.com
- **Evolution Portal Support**: ______________________

## Rollback Plan

In case of critical issues:

1. [ ] Disable the application (maintenance mode)
2. [ ] Restore previous database backup
3. [ ] Restore previous file backup
4. [ ] Verify restoration successful
5. [ ] Document what went wrong
6. [ ] Plan remediation

---

**Deployment Date**: _______________
**Deployed By**: _______________
**Verified By**: _______________
**Sign-off**: _______________
