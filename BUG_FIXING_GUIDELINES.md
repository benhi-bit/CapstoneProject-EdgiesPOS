# Bug Fixing Guidelines

The table below logs reported issues, their severity, and current resolution status to maintain effective and efficient operations of the Edgie's POS System v1.3.

| Bug ID | Description | Resolution |
|--------|-------------|------------|
| B001 | Database connection fails when accessing system | Check MySQL service is running, verify credentials in `config/connection.php`, ensure database `edgies_pos` exists, verify user has proper permissions, check firewall settings. |
| B002 | User cannot log in after multiple failed attempts | Check account lockout status in `login_attempts` table, wait for lockout period to expire (1 min, 3 min, 5 min, or 10 min based on lockout level), verify username and password are correct, clear browser cache and cookies. |
| B003 | Two-factor authentication (2FA) code verification fails | Verify system time is synchronized correctly, check QR code was scanned properly, ensure TOTP secret is correctly stored in database, verify `sonata-project/google-authenticator` library is properly installed, check for time zone discrepancies. |
| B004 | Transaction not saved to database after checkout | Check database connection status, verify transaction table structure is intact, review transaction processing code for errors, check for foreign key constraint violations, verify stock quantities are sufficient before transaction completion. |
| B005 | Receipt printing fails or produces blank documents | Verify printer is connected and powered on, check printer drivers are installed correctly, test printer with other software, verify `mike42/escpos-php` library is properly installed, check USB/network connection, verify printer compatibility with ESC/POS commands. |
| B006 | Product images fail to upload or display incorrectly | Check `images/` folder permissions (should be 755 or 777), verify PHP upload limits in `php.ini` (`upload_max_filesize` and `post_max_size`), check available disk space, verify file type is allowed (jpg, png), check file size does not exceed limits, verify GD extension is enabled. |
| B007 | Stock quantity not updating after transaction completion | Verify transaction items are properly linked to products, check stock update query execution, review transaction rollback mechanisms, ensure database transaction is committed successfully, check for concurrent transaction conflicts. |
| B008 | Sales report generation fails or shows incorrect data | Check date range parameters are valid, verify database queries are executing correctly, review report generation code for calculation errors, check for time zone issues affecting date filtering, verify transaction data integrity in database. |
| B009 | User session expires unexpectedly or logs out frequently | Check session timeout settings in PHP configuration, verify session storage directory has proper permissions, check server time is synchronized correctly, review session cleanup scripts, check for multiple login prevention conflicts, verify session cookie settings. |
| B010 | Payment processing fails for GCash or PayMaya transactions | Verify reference number is being captured correctly, check payment method validation logic, review transaction processing workflow, ensure payment data is properly stored in database, check for network connectivity issues during payment processing. |
| B011 | Product search functionality returns no results or incorrect results | Check database connection, verify search query syntax, review product name matching logic, check for case sensitivity issues, verify product availability status, check database indexes on product name column. |
| B012 | Dashboard real-time updates (SSE) not working | Verify Server-Sent Events (SSE) handler is running correctly, check browser compatibility with SSE, review `api/sse_handler.php` for errors, check server configuration allows SSE connections, verify event stream is properly formatted. |
| B013 | User cannot access admin features despite having admin role | Check user role in `loginaccount` table, verify role-based access control (RBAC) logic, review session role validation, check for case sensitivity in role comparison, verify user account is active and not locked. |
| B014 | Backup creation fails or backup file is corrupted | Check database connection before backup, verify backup directory has write permissions, check available disk space, review backup script execution, verify MySQL dump command is working, check for file system errors. |
| B015 | Database restore from backup fails | Verify backup file is not corrupted, check database connection credentials, ensure target database exists or can be created, verify user has CREATE DATABASE privileges, check for SQL syntax errors in backup file, review restore script execution. |
| B016 | Discount calculation incorrect in transaction totals | Verify discount percentage (20%) is applied correctly, check discount application logic in checkout process, review VAT calculation when discount is applied (VAT should be exempt when discounted), verify total calculation formula, check for rounding errors. |
| B017 | Transaction history shows incorrect cashier information | Verify cashier ID is properly captured during transaction, check session user information is correct, review transaction insertion query, verify foreign key relationship between transactions and users, check for session hijacking or impersonation issues. |
| B018 | Category deletion fails when products are still assigned | Check for foreign key constraints preventing deletion, verify category deletion logic handles product reassignment, review error messages for specific constraint violations, ensure products are moved to another category or deleted before category removal. |
| B019 | Product availability toggle not working correctly | Verify product availability status update query, check AJAX request is properly sent, review response handling in frontend, verify database field is being updated correctly, check for JavaScript errors in browser console. |
| B020 | Log history not recording user actions | Check log insertion queries are executing, verify log table structure is correct, review logging function calls in code, check for database connection issues during logging, verify user session information is available for logging. |
| B021 | Duplicate product names can be added despite validation | Verify duplicate check query is executing correctly, check case sensitivity in product name comparison, review validation logic before product insertion, ensure unique constraint or validation is applied, check for race conditions in concurrent requests. |
| B022 | Real-time sales statistics on dashboard show incorrect values | Verify data aggregation queries are correct, check date filtering logic, review cache invalidation for real-time updates, verify transaction data integrity, check for time zone issues affecting date calculations. |
| B023 | Password recovery via security questions not working | Verify security questions are stored correctly in database, check answer comparison logic (case sensitivity), review email sending functionality if applicable, verify recovery process workflow, check for session issues during recovery. |
| B024 | Product stock shows negative values | Check stock validation before transaction completion, verify stock update queries use proper locking mechanisms, review concurrent transaction handling, ensure stock cannot go below zero, check for data integrity issues in database. |
| B025 | Receipt reprint from transaction history fails | Verify transaction data is available in database, check receipt generation code for transaction retrieval, review printer connection status, verify transaction ID is valid, check for missing transaction item data. |

## Bug Severity Classification

### Critical (P0)
- **B001**: Database connection failures - System completely unusable
- **B004**: Transaction not saved - Financial data loss risk
- **B007**: Stock not updating - Inventory accuracy compromised
- **B024**: Negative stock values - Data integrity issue

### High (P1)
- **B002**: Login failures - Users cannot access system
- **B003**: 2FA verification fails - Security and access issues
- **B005**: Receipt printing fails - Customer service impact
- **B009**: Session expiration issues - User experience degradation

### Medium (P2)
- **B006**: Image upload issues - Feature functionality
- **B008**: Report generation problems - Business intelligence impact
- **B010**: Payment processing errors - Transaction completion issues
- **B012**: Real-time updates not working - Feature functionality

### Low (P3)
- **B011**: Search functionality issues - User experience
- **B019**: Availability toggle issues - Feature functionality
- **B021**: Duplicate product validation - Data quality

## Bug Resolution Workflow

1. **Report**: Document the bug with Bug ID, description, and steps to reproduce
2. **Assign**: Assign bug to appropriate developer based on severity and area
3. **Investigate**: Review code, check logs, and identify root cause
4. **Fix**: Implement fix following coding standards and best practices
5. **Test**: Verify fix resolves the issue and does not introduce new problems
6. **Deploy**: Deploy fix to production with proper backup and monitoring
7. **Document**: Update this guideline table with resolution details
8. **Close**: Mark bug as resolved and update status

## Prevention Strategies

- Regular code reviews before deployment
- Comprehensive testing in development environment
- Database integrity checks and maintenance
- Regular backup verification
- Monitoring system logs for early issue detection
- User training on proper system usage
- Regular security audits and updates

---

**Last Updated**: [Date]  
**System Version**: Edgie's POS System v1.3  
**Maintained By**: Development Team
