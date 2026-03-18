# Edgie's POS System v1.2 - Complete Documentation

> **Using v1.3?** See `USER_GUIDE.md` for the **v1.3 user guide** written from the actual v1.3 screens/workflows.

## Table of Contents

1. [System Overview](#system-overview)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [User Guide](#user-guide)
5. [Administration](#administration)
6. [Maintenance](#maintenance)
7. [Troubleshooting](#troubleshooting)
8. [Technical Details](#technical-details)
9. [Security](#security)
10. [Backup & Recovery](#backup--recovery)

---

## System Overview

### Introduction
Edgie's POS System is a comprehensive Point of Sale solution designed for restaurants and event venues. The system provides complete order management, inventory tracking, transaction processing, and administrative controls.

### Key Features
- **Dual Order Types**: Take Out and Dine In
- **Role-Based Access**: Admin and Cashier roles with different permissions
- **Two-Factor Authentication**: Enhanced security with 2FA
- **Real-Time Updates**: Live dashboard and transaction updates
- **Comprehensive Reporting**: Sales reports, transaction history, and analytics
- **Inventory Management**: Real-time stock tracking and management
- **Receipt Printing**: ESC/POS compatible receipt printing
- **Backup & Recovery**: Automated and manual backup system

### System Requirements
- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: PHP 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: 
  - mysqli
  - gd (for image processing)
  - mbstring
  - openssl (for 2FA)
- **Browser**: Modern browsers (Chrome, Firefox, Edge, Safari)

---

## Installation & Setup

### Step 1: Server Setup
1. Install XAMPP, WAMP, or LAMP stack
2. Ensure Apache and MySQL services are running
3. Verify PHP version: `php -v` (should be 7.4+)

### Step 2: Database Setup
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `edgies_pos`
3. Import the database file from `database/` folder:
   - Select the `edgies_pos` database
   - Click "Import"
   - Choose the SQL file from the `database/` folder
   - Click "Go"

### Step 3: File Installation
1. Copy the entire project folder to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\`
   - **WAMP**: `C:\wamp64\www\`
   - **Linux**: `/var/www/html/`
2. Ensure proper file permissions (755 for directories, 644 for files)

### Step 4: Configuration
1. Open `config/connection.php`
2. Update database credentials:
   ```php
   $servername = "localhost";
   $username = "root";        // Your MySQL username
   $password = "";            // Your MySQL password
   $dbname = "edgies_pos";    // Database name
   ```
3. Save the file

### Step 5: Install Dependencies
1. Open terminal/command prompt in the project directory
2. Run: `composer install`
3. Wait for dependencies to install

### Step 6: Initial Access
1. Open browser and navigate to: `http://localhost/Edgies POS System v1.2/`
2. You should see the login page
3. Use default admin credentials (if provided) or create first admin account

### Step 7: First Admin Setup
If no admin account exists:
1. Register a new account through the registration page
2. Complete 2FA setup when prompted
3. Set up security questions for password recovery

---

## Configuration

### Database Configuration
**File**: `config/connection.php`

```php
$servername = "localhost";    // Database server
$username = "root";            // Database username
$password = "";                // Database password
$dbname = "edgies_pos";        // Database name
```

### Session Configuration
- Session timeout: 30 minutes of inactivity
- Multiple login prevention: Enabled
- Session security: Back button prevention enabled

### Backup Configuration
**File**: `tools/scheduled_backup.php`

To set up automatic backups:
1. **Windows Task Scheduler**:
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `-f "C:\xampp\htdocs\Edgies POS System v1.2\tools\scheduled_backup.php"`
   - Trigger: Weekly (recommended: Monday, 10:00 AM)

2. **Linux Cron**:
   ```bash
   0 10 * * 1 /usr/bin/php /var/www/html/Edgies\ POS\ System\ v1.2/tools/scheduled_backup.php
   ```

### Receipt Printer Configuration
- **Interface**: ESC/POS compatible printers
- **Connection**: USB, Network, or Serial
- **Library**: mike42/escpos-php
- Configuration is handled automatically when printer is connected

---

## User Guide

> For the updated **v1.3** end-user guide (Admin & Cashier), see `USER_GUIDE.md`.

### Login Process

1. **Navigate to Login Page**
   - Open the system URL in your browser
   - You'll see the login page

2. **Enter Credentials**
   - Username: Your assigned username
   - Password: Your password

3. **Two-Factor Authentication (2FA)**
   - If 2FA is enabled, scan QR code with authenticator app
   - Enter the 6-digit code from your app
   - Click "Verify"

4. **Account Lockout**
   - After failed login attempts:
     - 1st lockout: 1 minute
     - 2nd lockout: 3 minutes
     - 3rd lockout: 5 minutes
     - 4th+ lockout: 10 minutes

### Cashier Interface

#### Take Out Orders
1. Click **"Take Out"** in the navigation
2. Browse products by category
3. Click products to add to cart
4. Adjust quantities as needed
5. Select order type: "Dine In" or "Take Out"
6. Apply discount if applicable (PWD/Senior Citizen - 20%)
7. Proceed to checkout
8. Select payment method:
   - **Cash**: Enter amount received
   - **GCash**: Enter reference number
   - **PayMaya**: Enter reference number
9. Complete transaction
10. Print receipt

#### Dine In Orders
1. Click **"Dine In"** in the navigation
2. Click **"Add Table"** to create a new table
3. Select table number
4. Add items to table's cart
5. Manage multiple tables simultaneously
6. Process payment when ready
7. Table is cleared after payment

### Admin Interface

#### Dashboard
- View real-time sales statistics
- Monitor today's sales and orders
- View sales charts and analytics
- Filter by date range

#### Product Management
1. **Add Product**:
   - Click "Manage Product"
   - Click "Add Product"
   - Enter product details (name, price, category, stock, image)
   - Save

2. **Edit Product**:
   - Find product in list
   - Click edit icon
   - Update information
   - Save

3. **Delete Product**:
   - Find product
   - Click delete icon
   - Confirm deletion

#### Category Management
1. **Add Category**:
   - Go to "Category" page
   - Enter category name
   - Save

2. **Edit/Delete Category**:
   - Use edit/delete buttons
   - Confirm actions

#### User Management
1. **Add User**:
   - Go to "User Management"
   - Click "Add User"
   - Fill in user details
   - Assign role (Admin/Cashier)
   - Save

2. **Edit User**:
   - Find user in list
   - Click edit
   - Update information
   - Save

3. **Delete User**:
   - Find user
   - Click delete
   - Confirm

#### Transaction History
- View all transactions
- Search by transaction number, date, cashier
- Filter by date range
- View transaction details
- Reprint receipts

#### Reports
- Generate sales reports
- Filter by date range
- View product performance
- Export reports (if available)

---

## Administration

### User Roles

#### Admin
- Full system access
- Product management
- Category management
- User management
- Transaction history
- Reports and analytics
- Backup and recovery
- System logs

#### Cashier
- POS access (Take Out and Dine In)
- Transaction processing
- Receipt printing
- View own transaction history
- View own sales reports

### Security Features

#### Password Requirements
- Minimum length enforced
- Secure hashing (bcrypt)
- Password recovery via security questions

#### Session Management
- Automatic logout after 30 minutes of inactivity
- Single active session per user
- Session validation on each page

#### Activity Logging
- All actions are logged
- Logs include: user, action, timestamp
- Viewable in "Log History"

---

## Maintenance

### Routine Maintenance Tasks

Maintaining the Edgie's POS System requires consistent and systematic attention to ensure optimal performance, data integrity, and system reliability. Regular maintenance activities are essential for preventing system failures, protecting critical business data, and maintaining a secure operational environment. The maintenance strategy is structured into daily, weekly, monthly, and quarterly tasks, each designed to address specific aspects of system health and operational efficiency.

#### Daily Tasks

Daily maintenance activities focus on monitoring system health and ensuring that all critical operations are functioning correctly. These tasks should be performed at the beginning or end of each business day to maintain continuous system availability and prevent issues from escalating.

- Check system logs through the Admin → Log History interface to identify any errors, warnings, or unusual activities
- Review error logs and warnings that may indicate system malfunctions
- Monitor for unusual user activity patterns that could signal security concerns
- Verify that recent backups exist in the `backup/` folder and check backup file integrity
- Review transaction history to identify any processing issues or failed transactions
- Check for failed transactions that require attention
- Verify payment processing systems are functioning properly
- Review low stock items to prevent stockouts
- Update product availability status based on current inventory levels
- Coordinate with suppliers for restocking as needed

#### Weekly Tasks

Weekly maintenance tasks focus on system performance optimization, user management, and ensuring that backup systems are functioning correctly. These activities provide a more comprehensive review of system operations beyond daily monitoring.

- Verify that scheduled backups ran successfully during the week
- Test backup restoration procedures in a test environment to confirm backup integrity
- Archive old backups according to retention policies (keep last 4 weeks of backups)
- Monitor server resources including CPU usage, memory consumption, and disk space
- Review database query performance to identify slow queries
- Analyze system response times and identify areas for optimization
- Review active user accounts and their access levels
- Identify and deactivate unused or inactive accounts
- Update user permissions based on role changes or organizational needs
- Clear browser cache and temporary files
- Remove old session files that are no longer needed
- Clean up temporary upload files
- Monitor disk space usage to prevent storage issues

#### Monthly Tasks

Monthly maintenance activities provide a comprehensive system review, focusing on database optimization, security auditing, and system updates. These tasks ensure long-term system health and security.

- Run `OPTIMIZE TABLE` commands on all database tables to reclaim unused space
- Check table sizes and growth patterns to identify potential issues
- Review and archive old transaction data if necessary to maintain performance
- Analyze database indexes to ensure they are optimized for current query patterns
- Review user access logs to identify unusual access patterns
- Check for suspicious activity that might indicate security breaches
- Update passwords for administrative accounts as part of security best practices
- Review two-factor authentication (2FA) enrollment to ensure all users have proper security measures
- Verify that security patches have been applied and are up to date
- Check for PHP updates and apply security patches
- Review and apply security patches for all system components
- Update dependencies using `composer update` to ensure compatibility and security
- Test updates in a development environment before applying to production
- Move old backups to long-term archive storage
- Maintain monthly backups for at least 6 months for historical reference
- Keep weekly backups for 3 months for more recent recovery needs

#### Quarterly Tasks

Quarterly maintenance provides an opportunity for comprehensive system review, performance tuning, and disaster recovery planning. These activities ensure long-term system sustainability and reliability.

- Create complete backups of both system files and database
- Store backups in off-site locations for disaster recovery purposes
- Test disaster recovery procedures to ensure backups can be restored successfully
- Document backup and recovery procedures for future reference
- Analyze query performance over time to identify trends
- Optimize slow queries that may have developed over time
- Review database indexing strategies to ensure optimal query performance
- Update system documentation to reflect any changes or improvements
- Review maintenance procedures to ensure they remain effective
- Update user guides to reflect any interface or feature changes

### Upgrading Procedures

System upgrades are critical processes that require careful planning, thorough testing, and systematic execution to ensure minimal disruption to business operations. The upgrading procedures for the Edgie's POS System are designed to minimize risks and ensure a smooth transition from one version to another while maintaining data integrity and system functionality.

#### Pre-Upgrade Preparation

Before initiating any upgrade process, comprehensive preparation is essential to ensure a successful upgrade with minimal risk to system operations and data integrity.

**Complete System Backup**: The first and most critical step in any upgrade procedure is creating comprehensive backups of all system components. This includes both the database and all system files to ensure that the system can be restored to its previous state if the upgrade encounters any issues. The backup process should include:
- Creating a complete database backup using MySQL dump commands:
  ```bash
  mysqldump -u root -p edgies_pos > backup_before_upgrade.sql
  ```
- Copying the entire project folder to a secure backup location
- Verifying that all backups are complete and accessible before proceeding
- Documenting the backup location and restoration procedures

**Release Notes Review**: Thoroughly reviewing release notes and upgrade documentation is crucial for understanding what changes will be implemented and what preparations are necessary. This review should cover:
- Reading all upgrade instructions provided by the development team
- Identifying any breaking changes that may affect current system configurations
- Noting required PHP and MySQL versions to ensure compatibility
- Understanding new features and how they may impact current workflows
- Identifying any deprecated features that may need to be replaced

**Test Environment Setup**: Establishing a test environment that mirrors the production system is essential for safely testing upgrades before applying them to the live system. The test environment should:
- Be set up to replicate the production environment as closely as possible
- Allow for complete testing of the upgrade process without affecting live operations
- Enable verification that all system features work correctly after the upgrade
- Provide a safe space to practice the upgrade procedure and identify potential issues

#### Upgrade Execution Steps

The upgrade process must be executed systematically, following each step carefully to ensure a successful transition.

**Service Management**: Before beginning the upgrade, it may be necessary to temporarily stop system services to prevent data corruption during the upgrade process. This involves:
- Stopping the Apache web server or other web server services
- Ensuring that no active transactions are in progress
- Notifying users of the maintenance window if the system will be unavailable
- Verifying that all active sessions have been properly closed

**Version Documentation**: Before making any changes, document the current system version and configuration to enable proper rollback if needed. This documentation should include:
- Recording the current system version number
- Creating a complete backup of the current version
- Documenting any custom configurations or modifications that have been made
- Creating a checklist of all components that need to be upgraded

**File Updates**: Updating system files requires careful attention to preserve important configurations and data. The file update process should:
- Download the new version from the official source
- Replace system files while preserving critical configuration files
- Maintain the `config/connection.php` file to preserve database connection settings
- Preserve the `backup/` folder to maintain existing backup files
- Preserve the `images/` folder to maintain product images and other media files
- Verify that all file permissions are correctly set after the update

**Database Migration**: Database updates may be required to support new features or schema changes. The database update process should:
- Run any provided database migration scripts in the correct order
- Check for new tables or columns that have been added
- Verify that all existing data has been properly migrated
- Confirm data integrity by comparing record counts and key data points
- Test database queries to ensure they function correctly with the new schema

**Dependency Updates**: Updating system dependencies ensures compatibility with the new system version. This process includes:
- Running `composer install` to install any new dependencies
- Running `composer update` to update existing dependencies to compatible versions
- Verifying that all dependencies are compatible with the new system version
- Testing that all third-party libraries function correctly

**Configuration Verification**: After updating files and dependencies, verify that all system configurations are correct. This verification should include:
- Checking the `config/connection.php` file to ensure database connection settings are correct
- Verifying that file permissions are set appropriately for all system files
- Testing the database connection to ensure it is functioning properly
- Confirming that all configuration files are in the correct format

**System Testing**: Comprehensive testing after the upgrade ensures that all system components are functioning correctly. Testing should cover:
- User authentication and login functionality
- POS transaction processing and checkout operations
- Administrative features including product management, user management, and reporting
- Report generation and data export functionality
- Backup and restore operations to ensure data protection systems are working

**Rollback Preparation**: Having a rollback plan ready is essential in case the upgrade encounters critical issues. The rollback plan should include:
- Procedures for restoring files from backup if file updates cause issues
- Procedures for restoring the database from backup if database migration fails
- Verification steps to confirm that the system works correctly after rollback
- Documentation of any issues encountered during the upgrade process

#### Post-Upgrade Activities

After completing the upgrade, ongoing monitoring and communication are necessary to ensure the upgrade is successful and users are properly informed.

**System Monitoring**: Continuous monitoring after an upgrade helps identify any issues that may not have been apparent during initial testing. Post-upgrade monitoring should include:
- Watching for errors in system logs and error reporting
- Checking application logs for any unusual activity or errors
- Monitoring system performance to ensure it meets expected levels
- Tracking user-reported issues that may be related to the upgrade

**User Communication**: Effective communication with system users is essential for a smooth transition. Post-upgrade communication should include:
- Informing users of any changes to the system interface or functionality
- Providing training materials or sessions if new features require user education
- Updating user documentation to reflect any changes in system operation
- Establishing a support channel for users who encounter issues with the upgraded system

### Bug Fixing Guidelines

Effective bug fixing is essential for maintaining system reliability and user satisfaction. The bug fixing process for the Edgie's POS System follows a systematic approach that ensures bugs are properly identified, classified, resolved, and documented. This process helps maintain code quality, prevent regression issues, and improve overall system stability.

#### Bug Identification and Reporting

The first step in the bug fixing process is proper identification and documentation of the issue. Accurate bug reports are essential for efficient resolution and help developers understand the problem context.

**User Bug Reports**: When users report bugs, it is important to gather comprehensive information to facilitate quick and accurate resolution. User bug reports should be documented with the following information:
- A clear description of what happened when the bug occurred
- The exact time and date when the issue occurred
- The specific actions the user was attempting to perform when the bug manifested
- Any error messages that were displayed to the user
- Step-by-step instructions to reproduce the bug
- The user's role (Admin or Cashier) and the web browser being used
- Screenshots or screen recordings if available to provide visual context

**System Log Analysis**: System logs provide valuable information for identifying bugs that may not be immediately apparent from user reports. Log analysis should include:
- Reviewing error logs in the system's log history interface
- Checking PHP error logs for server-side errors and exceptions
- Examining database error logs for query failures or connection issues
- Reviewing application logs for unusual patterns or error sequences
- Correlating log entries with user-reported issues to identify root causes

**Bug Reproduction**: Successfully reproducing a bug is crucial for understanding its cause and verifying that fixes are effective. The reproduction process should:
- Attempt to reproduce the bug in a test environment that mirrors the production system
- Document the exact steps required to reproduce the issue consistently
- Capture screenshots or videos of the bug occurring to provide visual documentation
- Note the system state, including data conditions, user permissions, and system configuration at the time of the bug
- Identify any specific conditions or data that must be present for the bug to occur

#### Bug Classification and Prioritization

Bugs must be classified according to their severity and impact on system operations to ensure that critical issues are addressed promptly while managing development resources effectively.

**Critical Bugs**: These are the most severe issues that require immediate attention as they significantly impact system operations. Critical bugs include:
- Complete system failures that prevent the system from functioning
- Data loss or corruption that affects business operations
- Security breaches that compromise system or user data security
- Issues that prevent all users from accessing critical system functions

**High Priority Bugs**: These bugs significantly impact system functionality but may have workarounds available. High priority bugs include:
- Major features that are completely broken and prevent normal business operations
- Significant data issues that affect multiple users or transactions
- Security vulnerabilities that could lead to data exposure
- Issues that prevent specific user roles from performing essential functions

**Medium Priority Bugs**: These bugs affect system functionality but have workarounds or affect non-critical features. Medium priority bugs include:
- Features that are partially broken but still functional with limitations
- Issues that have workarounds available to users
- Problems that affect secondary features or non-essential functionality
- Performance issues that degrade user experience but do not prevent system use

**Low Priority Bugs**: These are minor issues that do not significantly impact system functionality. Low priority bugs include:
- Minor user interface issues that do not affect functionality
- Cosmetic problems that do not impact user experience significantly
- Minor display inconsistencies that do not affect data accuracy
- Issues that affect rarely used features or edge cases

#### Bug Fixing Process

The bug fixing process follows a systematic approach that ensures bugs are properly resolved while maintaining code quality and preventing regression issues.

**Test Case Development**: Before attempting to fix a bug, create a test case that reliably reproduces the issue. This test case should:
- Clearly demonstrate the bug by reproducing the issue consistently
- Document the expected behavior versus the actual behavior observed
- Serve as a verification tool to confirm that the fix resolves the issue
- Be added to the test suite to prevent regression of the bug in the future

**Root Cause Analysis**: Identifying the root cause of a bug is essential for developing an effective fix. Root cause analysis should involve:
- Reviewing the relevant code sections to understand the logic and identify potential issues
- Checking the database for data inconsistencies or corruption that may be causing the problem
- Reviewing system logs to identify error patterns or sequences that may indicate the cause
- Using debugging tools such as breakpoints, logging statements, and database query analyzers to trace the issue

**Fix Development**: Once the root cause is identified, develop a fix that addresses the underlying issue. Fix development should:
- Be implemented in a test environment to prevent affecting production systems
- Follow established coding standards and best practices to maintain code quality
- Include appropriate comments, especially for complex fixes, to aid future maintenance
- Consider edge cases and potential side effects of the fix
- Ensure the fix is minimal and focused on resolving the specific issue without unnecessary changes

**Fix Testing**: Comprehensive testing ensures that the fix resolves the bug without introducing new issues. Testing should verify:
- The fix successfully resolves the reported bug
- The fix does not break other system features or functionality
- Edge cases and boundary conditions are handled correctly
- The fix works correctly with different user roles and permission levels
- Performance is not negatively impacted by the fix

**Code Review**: When working in a team environment, code reviews help ensure code quality and catch potential issues. Code reviews should:
- Have another developer review the fix to identify potential issues or improvements
- Check for security vulnerabilities that may have been introduced
- Verify that coding standards and best practices are followed
- Ensure that the fix is maintainable and well-documented

**Fix Deployment**: Deploying fixes to production requires careful planning to minimize disruption. The deployment process should:
- Create a backup of the system before deploying the fix to enable rollback if needed
- Deploy the fix to production during a maintenance window if possible
- Monitor the system closely after deployment to identify any issues
- Verify that the fix is working correctly in the production environment

**Documentation**: Proper documentation ensures that bug fixes are tracked and can be referenced in the future. Documentation should include:
- Updating the bug tracker with the resolution details and fix information
- Adding comments in the code to explain the fix and why it was necessary
- Updating user documentation if the fix changes system behavior or requires user action
- Documenting any workarounds or special considerations related to the fix

#### Common Bug Categories

Understanding common bug categories helps developers quickly identify and resolve issues. The system typically encounters bugs in the following areas:

**Database-Related Issues**: Database bugs can significantly impact system functionality and data integrity. Common database issues include:
- Connection errors that prevent the system from accessing the database
- Query failures that result from syntax errors or logical issues
- Data corruption that affects data accuracy or completeness
- Foreign key violations that occur when referential integrity is compromised

**Authentication and Security Issues**: Authentication bugs can prevent users from accessing the system or compromise security. Common authentication issues include:
- Login failures that prevent legitimate users from accessing the system
- Session problems that cause users to be logged out unexpectedly
- Two-factor authentication (2FA) issues that prevent proper verification
- Permission errors that grant or deny access incorrectly

**Transaction Processing Issues**: Transaction bugs can directly impact business operations and financial accuracy. Common transaction issues include:
- Payment processing errors that prevent transactions from completing
- Receipt printing failures that affect customer service
- Stock update issues that result in inaccurate inventory levels
- Transaction data not being saved correctly to the database

**User Interface and Experience Issues**: UI/UX bugs affect user experience and system usability. Common UI/UX issues include:
- Display problems that prevent information from being shown correctly
- Navigation issues that make it difficult to access system features
- Form validation errors that prevent users from submitting data
- Responsive design problems that affect system usability on different devices

#### Debugging Tools and Techniques

Effective debugging requires the use of appropriate tools and techniques to identify and resolve issues efficiently.

**Development Tools**: Various tools are available to assist in debugging system issues:
- **PHP Error Reporting**: Enabling comprehensive error reporting in the development environment helps identify PHP errors and warnings
- **Browser Console**: Checking the browser's developer console helps identify JavaScript errors and client-side issues
- **Network Tab**: Using browser developer tools to monitor network requests helps identify API call issues
- **Database Logs**: Reviewing database query logs helps identify slow queries or database-related problems
- **Application Logs**: Checking system application logs provides insight into system behavior and error patterns

#### Bug Prevention Strategies

Preventing bugs is more efficient than fixing them, and several strategies can help reduce the occurrence of bugs in the system.

**Code Quality Practices**: Maintaining high code quality helps prevent bugs from being introduced:
- **Code Reviews**: Having code reviewed by other developers before merging helps catch issues early
- **Comprehensive Testing**: Testing code thoroughly before deployment helps identify bugs before they reach production
- **Error Handling**: Implementing proper error handling ensures that errors are caught and handled gracefully
- **Logging**: Comprehensive logging helps identify issues quickly and provides information for debugging
- **Documentation**: Keeping code well-documented helps developers understand the codebase and prevents misunderstandings that can lead to bugs

---

## Troubleshooting

### Common Issues

#### Database Connection Errors
**Problem**: "Connection failed" error

**Solutions**:
1. Check MySQL service is running
2. Verify credentials in `config/connection.php`
3. Check database exists: `edgies_pos`
4. Verify user has proper permissions
5. Check firewall settings

#### Login Issues
**Problem**: Cannot log in

**Solutions**:
1. Check account is not locked (wait for lockout period)
2. Verify username and password
3. Check 2FA code is correct
4. Clear browser cache and cookies
5. Check session storage is not full
6. Verify database connection

#### Session Problems
**Problem**: Logged out frequently

**Solutions**:
1. Check session timeout settings
2. Verify session storage permissions
3. Check server time is correct
4. Review session cleanup scripts
5. Check for multiple login prevention

#### Receipt Printing Issues
**Problem**: Receipts not printing

**Solutions**:
1. Verify printer is connected
2. Check printer drivers installed
3. Test printer with other software
4. Check ESC/POS library is installed
5. Verify printer compatibility
6. Check USB/network connection

#### Performance Issues
**Problem**: System is slow

**Solutions**:
1. Check server resources (CPU, RAM)
2. Optimize database queries
3. Check for large image files
4. Review indexing
5. Clear browser cache
6. Check network latency
7. Review server logs

#### Image Upload Issues
**Problem**: Cannot upload product images

**Solutions**:
1. Check `images/` folder permissions (755)
2. Verify PHP upload limits in `php.ini`:
   - `upload_max_filesize`
   - `post_max_size`
3. Check disk space
4. Verify file type is allowed (jpg, png)
5. Check file size limits

### Error Messages

#### "Access Denied"
- Check user role has permission
- Verify session is valid
- Check if account is active

#### "Database Error"
- Check database connection
- Verify table exists
- Check query syntax
- Review error logs

#### "File Not Found"
- Check file path is correct
- Verify file exists
- Check file permissions

### Getting Help

1. **Check Logs**
   - Admin → Log History
   - Server error logs
   - Browser console

2. **Document Issue**
   - Screenshot error
   - Note steps to reproduce
   - Check system logs

3. **Contact Support**
   - Provide error details
   - Include system information
   - Share relevant logs

---

## Technical Details

### System Architecture

#### Frontend
- **HTML5**: Structure
- **CSS3**: Styling
- **JavaScript**: Interactivity
- **Chart.js**: Data visualization
- **SweetAlert2**: Alerts and modals
- **Font Awesome**: Icons

#### Backend
- **PHP**: Server-side logic
- **MySQLi**: Database operations
- **Session Management**: User sessions
- **Server-Sent Events (SSE)**: Real-time updates

#### Libraries
- **sonata-project/google-authenticator**: 2FA
- **endroid/qr-code**: QR code generation
- **mike42/escpos-php**: Receipt printing

### Database Structure

#### Key Tables
- `loginaccount`: User accounts
- `products`: Product catalog
- `categories`: Product categories
- `transactions`: Transaction records
- `transaction_items`: Transaction line items
- `log_history`: System activity logs
- `login_attempts`: Login attempt tracking
- `active_sessions`: Active user sessions

### File Structure
```
/
├── admin/              # Admin interface pages
├── api/                # API endpoints
├── assets/             # CSS and static assets
├── authentication/     # Login, 2FA, password recovery
├── backup/             # Database backups
├── cashier/            # Cashier interface pages
├── config/             # Configuration files
├── database/           # Database SQL files
├── images/             # Product images
├── includes/           # Shared PHP includes
├── lib/                # Helper libraries
├── tools/              # Maintenance tools
└── vendor/             # Composer dependencies
```

### API Endpoints

#### SSE (Server-Sent Events)
- `/api/sse_handler.php`: Real-time dashboard updates

#### Product Search
- `/api/search_products.php`: Product search functionality

#### Transaction
- `/api/fetch_transactions.php`: Fetch transaction data

#### User Management
- `/api/check_user_exists.php`: Check username availability

### Security Features

#### Input Validation
- All user inputs are validated
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)

#### Authentication
- Password hashing (bcrypt)
- Two-factor authentication
- Session security
- Account lockout system

#### Authorization
- Role-based access control
- Page-level permission checks
- API endpoint protection

---

## Security

### Best Practices

1. **Password Security**
   - Use strong passwords
   - Change passwords regularly
   - Never share passwords
   - Enable 2FA

2. **Access Control**
   - Limit admin accounts
   - Review user permissions regularly
   - Deactivate unused accounts
   - Monitor login attempts

3. **Data Protection**
   - Regular backups
   - Encrypt sensitive data
   - Secure database access
   - Protect backup files

4. **System Security**
   - Keep PHP updated
   - Keep MySQL updated
   - Use HTTPS in production
   - Regular security audits

### Security Checklist
- [ ] Strong database passwords
- [ ] 2FA enabled for all admin accounts
- [ ] Regular security updates
- [ ] HTTPS enabled (production)
- [ ] Firewall configured
- [ ] Regular backups
- [ ] Access logs reviewed
- [ ] Unused accounts deactivated

---

## Backup & Recovery

### Manual Backup

1. **Via Admin Interface**
   - Navigate to Admin → Backup & Restore
   - Click "Backup Database"
   - Download backup file
   - Store in secure location

2. **Via Command Line**
   ```bash
   mysqldump -u root -p edgies_pos > backup_$(date +%Y%m%d).sql
   ```

### Automatic Backup

#### Setup Scheduled Backup
1. **Windows Task Scheduler**
   - Create new task
   - Set trigger (weekly recommended)
   - Action: Run PHP script
   - Script: `tools/scheduled_backup.php`

2. **Linux Cron**
   ```bash
   0 10 * * 1 /usr/bin/php /path/to/tools/scheduled_backup.php
   ```

### Recovery Procedure

1. **Stop System** (if possible)
   - Prevent new transactions
   - Notify users

2. **Restore Database**
   - Via Admin Interface:
     - Admin → Backup & Restore
     - Upload backup file
     - Click "Restore"
   - Via Command Line:
     ```bash
     mysql -u root -p edgies_pos < backup_file.sql
     ```

3. **Verify Recovery**
   - Check data integrity
   - Test system functionality
   - Verify transactions

4. **Resume Operations**
   - Start system
   - Notify users
   - Monitor for issues

### Backup Retention
- **Daily**: Keep for 7 days
- **Weekly**: Keep for 4 weeks
- **Monthly**: Keep for 6 months
- **Yearly**: Keep indefinitely

---

## Appendix

### Version History
- **v1.2**: Current version
  - Dual order types (Take Out / Dine In)
  - Enhanced navigation
  - Improved table management

### Support Contacts
- **Technical Support**: [Your contact information]
- **Documentation**: This file
- **Issue Reporting**: [Your issue tracker]

### Additional Resources
- System Features: See `SYSTEM_FEATURES.md`
- Database Schema: See `database/` folder
- Test Cases: See `Manual_Test_Cases.xlsx`

---

**Last Updated**: 2024
**System Version**: v1.2
**Documentation Version**: 1.0
