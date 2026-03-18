# Edgie's POS System v1.2 - Database Schema Documentation

## Table of Contents

1. [Overview](#overview)
2. [Database Information](#database-information)
3. [Entity Relationship Diagram](#entity-relationship-diagram)
4. [Table Descriptions](#table-descriptions)
5. [Relationships](#relationships)
6. [Indexes](#indexes)
7. [Data Types Reference](#data-types-reference)

---

## Overview

The Edgie's POS System uses a MySQL/MariaDB database named `edgies_pos`. The database consists of 13 main tables that handle user authentication, product management, transactions, inventory, logging, and system configuration.

**Database Name**: `edgies_pos`  
**Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_general_ci`  
**Engine**: `InnoDB`

---

## Database Information

### Connection Details
- **Default Host**: `localhost`
- **Default Port**: `3306`
- **Default Username**: `root` (configurable)
- **Default Password**: (configurable)
- **Database Name**: `edgies_pos`

### Configuration File
Database connection is configured in: `config/connection.php`

---

## Entity Relationship Diagram

### Detailed ERD with Table Structures

```
┌─────────────────────────────────────────────────────────────┐
│                        loginaccount                          │
│                         (Users)                              │
├─────────────────────────────────────────────────────────────┤
│ UserID              int(11)           PK, N                  │
│ Role                varchar(255)     N                       │
│ FullName            varchar(255)     N                       │
│ Username            varchar(255)     N, UNIQUE               │
│ Email               varchar(255)     Y                       │
│ Password            varchar(255)     N                       │
│ RecoveryQuestion    varchar(255)     N                       │
│ RecoveryAnswer      varchar(255)     N                       │
│ Date_Created        datetime         N                       │
│ two_factor_secret   varchar(255)     Y                       │
└────────────┬────────────────────────────────────────────────┘
             │
             │ (1:N)
             ├──────────────────────────────┐
             │                              │
             ▼                              ▼
┌─────────────────────────────┐  ┌─────────────────────────────┐
│      active_sessions         │  │     login_attempts          │
│       (Sessions)             │  │      (Security)            │
├─────────────────────────────┤  ├─────────────────────────────┤
│ id              int(11)     PK│ │ id              int(11)     PK│
│ user_id         int(11)     FK│ │ username         varchar(255)│
│ session_id      varchar(255) N│ │ attempt_time     datetime    │
│ last_activity   timestamp    N│ │ ip_address       varchar(45) │
└─────────────────────────────┘  │ lockout_level    int(11)     │
                                 │ lockout_start     datetime    │
                                 └─────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                        categories                           │
│                      (Categories)                           │
├─────────────────────────────────────────────────────────────┤
│ CategoryID        int(11)           PK, N                  │
│ CategoryName      varchar(255)       N, UNIQUE              │
│ date_created      datetime          Y                      │
└────────────┬───────────────────────────────────────────────┘
             │
             │ (1:N)
             ▼
┌─────────────────────────────────────────────────────────────┐
│                        inventory                             │
│                       (Products)                             │
├─────────────────────────────────────────────────────────────┤
│ InventoryID       int(11)           PK, N                  │
│ CategoryID        int(11)           FK, N                  │
│ InventoryName     varchar(255)       N                      │
│ Price             float              N                      │
│ Quantity          int(11)            N                     │
│ ImagePath         varchar(255)       Y                      │
│ date_created      datetime          Y                      │
│ is_available      tinyint(1)         N                      │
└────────────┬───────────────────────────────────────────────┘
             │
             │ (1:N)
             ├──────────────────────────────┐
             │                              │
             ▼                              ▼
┌─────────────────────────────┐  ┌─────────────────────────────┐
│    inventory_update          │  │         sales                │
│   (Stock History)            │  │      (Sales Data)            │
├─────────────────────────────┤  ├─────────────────────────────┤
│ InventoryID      int(11)   FK│ │ SalesID          int(11)    PK│
│ CategoryID       int(11)    N│ │ Purchased_Date   datetime    N│
│ Quantity         int(11)     N│ │ Payment_Amount  float       N│
│ Date_Updated     datetime    N│ │ InventoryID     int(11)    FK│
│ Action           varchar(50) N│ │ CategoryID      int(11)     N│
└─────────────────────────────┘  └─────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      transactions                            │
│                    (Transactions)                            │
├─────────────────────────────────────────────────────────────┤
│ id                int(11)           PK, N                   │
│ serial_num        varchar(10)       N                       │
│ transaction_num  int(11)            N                       │
│ transaction_date  datetime          N                       │
│ total_amount      decimal(10,2)     N                       │
│ details           text              N                       │
│ processed_by      varchar(255)      Y                       │
│ payment           decimal(10,2)     Y                       │
│ change_amount     decimal(10,2)     Y                       │
│ payment_method    varchar(20)       Y                       │
│ reference_no      varchar(60)       Y                       │
└────────────┬───────────────────────────────────────────────┘
             │
             │ (1:N)
             ▼
┌─────────────────────────────────────────────────────────────┐
│                      order_items                             │
│                    (Order Items)                            │
├─────────────────────────────────────────────────────────────┤
│ id                int(11)           PK, N                   │
│ OrderID           int(11)           FK, N                   │
│ product_name      varchar(255)       N                       │
│ quantity          int(11)            N                       │
│ price             decimal(10,2)      N                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                        orders                                │
│                    (Legacy Orders)                           │
├─────────────────────────────────────────────────────────────┤
│ OrderID           int(11)           PK, N                   │
│ Username          varchar(255)       N                       │
│ Total             decimal(10,2)     N                       │
│ Cash              decimal(10,2)     N                       │
│ T_Change          decimal(10,2)     N                       │
│ OrderDate         datetime          Y                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      system_logs                             │
│                    (Activity Log)                            │
├─────────────────────────────────────────────────────────────┤
│ log_id            int(11)           PK, N                   │
│ action_type       varchar(255)       N                       │
│ description       text              N                       │
│ user_fullname     varchar(255)       N                       │
│ log_timestamp     datetime          N                       │
│ transaction_id    int(11)            Y                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    recovery_items                            │
│                   (Soft Deletes)                             │
├─────────────────────────────────────────────────────────────┤
│ id                int(11)           PK, N                   │
│ item_type         varchar(50)        N                       │
│ source_table      varchar(100)       N                       │
│ reference_id      varchar(64)        Y                       │
│ item_name         varchar(255)       Y                       │
│ item_data         longtext          N                       │
│ removed_by        varchar(255)       N                       │
│ removed_at        datetime          Y                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    backup_settings                           │
│                  (Backup Config)                             │
├─────────────────────────────────────────────────────────────┤
│ id                int(11)           PK, N                   │
│ schedule_day      varchar(10)       N                       │
│ schedule_time     time              N                       │
│ enabled           tinyint(1)         N                       │
│ updated_at        timestamp         N                       │
│ updated_by        varchar(255)      Y                       │
└─────────────────────────────────────────────────────────────┘
```

### Relationship Legend
- **PK** = Primary Key
- **FK** = Foreign Key
- **N** = Not Null
- **Y** = Nullable
- **(1:N)** = One-to-Many relationship
- **UNIQUE** = Unique constraint

### Visual Relationship Flow

```
                    ┌─────────────────┐
                    │  loginaccount   │
                    │   (Users)       │
                    └────────┬────────┘
                             │
                ┌────────────┼────────────┐
                │            │            │
                ▼            ▼            ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │active_sessions│  │login_attempts│  │ system_logs  │
    │  (Sessions)   │  │  (Security)  │  │ (Activity)   │
    └──────────────┘  └──────────────┘  └──────────────┘

    ┌─────────────────┐
    │   categories    │
    │  (Categories)   │
    └────────┬────────┘
             │
             │ CategoryID
             │
             ▼
    ┌─────────────────┐
    │    inventory    │
    │   (Products)    │
    └────────┬────────┘
             │
    ┌────────┼────────┐
    │        │        │
    │        │        │ InventoryID
    │        │        │
    ▼        ▼        ▼
┌──────────┐ ┌──────────┐ ┌──────────┐
│inventory │ │  sales   │ │transactions│
│_update   │ │          │ │           │
└──────────┘ └──────────┘ └─────┬─────┘
                                 │
                                 │ transaction_num
                                 │
                                 ▼
                         ┌──────────────┐
                         │ order_items  │
                         │(Order Items) │
                         └──────────────┘

    ┌─────────────────┐
    │ recovery_items  │
    │  (Soft Deletes) │
    └─────────────────┘

    ┌─────────────────┐
    │ backup_settings │
    │ (Backup Config) │
    └─────────────────┘

    ┌─────────────────┐
    │     orders      │
    │ (Legacy Orders) │
    └─────────────────┘
```

### Key Relationships Explained

1. **User Management Flow**:
   - `loginaccount` → `active_sessions` (tracks user sessions)
   - `loginaccount` → `login_attempts` (tracks login attempts)
   - `loginaccount` → `system_logs` (logs user actions)

2. **Product Management Flow**:
   - `categories` → `inventory` (products belong to categories)
   - `inventory` → `inventory_update` (tracks stock changes)
   - `inventory` → `sales` (records sales by product)

3. **Transaction Flow**:
   - `transactions` → `order_items` (transaction contains items)
   - `inventory` → `order_items` (items reference products)
   - `transactions` → `system_logs` (logs transaction events)

4. **System Management**:
   - `recovery_items` (stores soft-deleted items)
   - `backup_settings` (backup configuration)
   - `system_logs` (all system activities)

---

## Table Descriptions

### 1. `active_sessions`

Tracks active user sessions to prevent multiple simultaneous logins.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | - | Primary key (auto-increment) |
| `user_id` | INT(11) | NO | - | Foreign key to `loginaccount.UserID` |
| `session_id` | VARCHAR(255) | NO | - | PHP session identifier |
| `last_activity` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last activity timestamp (auto-updates) |

**Primary Key**: `id`  
**Indexes**: 
- `idx_user_id` on `user_id`
- Foreign key constraint on `user_id` → `loginaccount.UserID`

**Purpose**: Prevents multiple simultaneous logins per user.

---

### 2. `backup_settings`

Stores configuration for automated database backups.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `schedule_day` | VARCHAR(10) | NO | 'Monday' | Day of week for backup |
| `schedule_time` | TIME | NO | '10:00:00' | Time for backup |
| `enabled` | TINYINT(1) | NO | 1 | Whether backup is enabled (1=yes, 0=no) |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp (auto-updates) |
| `updated_by` | VARCHAR(255) | YES | NULL | Username who updated settings |

**Primary Key**: `id`

**Purpose**: Configures automatic weekly database backups.

---

### 3. `categories`

Product categories for organizing inventory items.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `CategoryID` | INT(11) | NO | - | Primary key (auto-increment) |
| `CategoryName` | VARCHAR(255) | NO | - | Category name (unique) |
| `date_created` | DATETIME | YES | CURRENT_TIMESTAMP | Creation timestamp |

**Primary Key**: `CategoryID`

**Purpose**: Organizes products into categories (e.g., "All-Day Meals", "Sharing Menu").

---

### 4. `inventory`

Main product/inventory table storing all sellable items.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `InventoryID` | INT(11) | NO | - | Primary key (auto-increment) |
| `CategoryID` | INT(11) | NO | - | Foreign key to `categories.CategoryID` |
| `InventoryName` | VARCHAR(255) | NO | - | Product name |
| `Price` | FLOAT | NO | - | Product price |
| `Quantity` | INT(11) | NO | - | Stock quantity |
| `ImagePath` | VARCHAR(255) | YES | NULL | Path to product image |
| `date_created` | DATETIME | YES | CURRENT_TIMESTAMP | Creation timestamp |
| `is_available` | TINYINT(1) | NO | 1 | Availability status (1=available, 0=unavailable) |

**Primary Key**: `InventoryID`  
**Foreign Key**: `CategoryID` → `categories.CategoryID`

**Purpose**: Stores all products with pricing, stock, and availability information.

---

### 5. `inventory_update`

Historical log of inventory quantity changes.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `InventoryID` | INT(11) | NO | - | Foreign key to `inventory.InventoryID` |
| `CategoryID` | INT(11) | NO | - | Category reference |
| `Quantity` | INT(11) | NO | - | Quantity after update |
| `Date_Updated` | DATETIME | NO | CURRENT_TIMESTAMP | Update timestamp |
| `Action` | VARCHAR(50) | NO | - | Action type (e.g., "Stock In", "Sale") |

**Purpose**: Tracks inventory changes for audit and history purposes.

---

### 6. `login_attempts`

Logs login attempts for security and lockout management.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `username` | VARCHAR(255) | YES | NULL | Username attempted |
| `attempt_time` | DATETIME | YES | NULL | Timestamp of attempt |
| `ip_address` | VARCHAR(45) | YES | NULL | IP address of attempt |
| `lockout_level` | INT(11) | YES | 0 | Current lockout level (0=none, 1-4=locked) |
| `lockout_start` | DATETIME | YES | NULL | When lockout started |

**Primary Key**: `id`

**Purpose**: Implements progressive lockout system (1min, 3min, 5min, 10min).

---

### 7. `loginaccount`

User accounts for system access (Admin and Cashier roles).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `UserID` | INT(11) | NO | - | Primary key (auto-increment) |
| `Role` | VARCHAR(255) | NO | - | User role ('Admin' or 'Cashier') |
| `FullName` | VARCHAR(255) | NO | - | User's full name |
| `Username` | VARCHAR(255) | NO | - | Unique username |
| `Email` | VARCHAR(255) | YES | NULL | User email address |
| `Password` | VARCHAR(255) | NO | - | Hashed password (bcrypt) |
| `RecoveryQuestion` | VARCHAR(255) | NO | - | Security question for password recovery |
| `RecoveryAnswer` | VARCHAR(255) | NO | - | Hashed answer to security question |
| `Date_Created` | DATETIME | NO | - | Account creation date |
| `two_factor_secret` | VARCHAR(255) | YES | NULL | 2FA secret key (TOTP) |

**Primary Key**: `UserID`  
**Unique**: `Username`

**Purpose**: Stores all user accounts with authentication and security information.

---

### 8. `order_items`

Line items for each order/transaction.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `OrderID` | INT(11) | NO | - | Foreign key to `orders.OrderID` |
| `product_name` | VARCHAR(255) | NO | - | Product name at time of order |
| `quantity` | INT(11) | NO | - | Quantity ordered |
| `price` | DECIMAL(10,2) | NO | - | Price per unit at time of order |

**Primary Key**: `id`  
**Foreign Key**: `OrderID` → `orders.OrderID`

**Purpose**: Stores individual items within each order (historical snapshot).

---

### 9. `orders`

Legacy order table (may be replaced by `transactions` in newer versions).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `OrderID` | INT(11) | NO | - | Primary key (auto-increment) |
| `Username` | VARCHAR(255) | NO | - | Cashier username |
| `Total` | DECIMAL(10,2) | NO | - | Total order amount |
| `Cash` | DECIMAL(10,2) | NO | - | Cash received |
| `T_Change` | DECIMAL(10,2) | NO | - | Change given |
| `OrderDate` | DATETIME | YES | CURRENT_TIMESTAMP | Order timestamp |

**Primary Key**: `OrderID`

**Purpose**: Legacy order storage (may be deprecated in favor of `transactions`).

---

### 10. `recovery_items`

Soft-delete recovery system for deleted items.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `item_type` | VARCHAR(50) | NO | - | Type of item ('product', 'category', 'user') |
| `source_table` | VARCHAR(100) | NO | - | Source table name |
| `reference_id` | VARCHAR(64) | YES | NULL | Original item ID |
| `item_name` | VARCHAR(255) | YES | NULL | Item name for display |
| `item_data` | LONGTEXT | NO | - | JSON data of deleted item |
| `removed_by` | VARCHAR(255) | NO | - | Username who deleted item |
| `removed_at` | DATETIME | YES | CURRENT_TIMESTAMP | Deletion timestamp |

**Primary Key**: `id`  
**Indexes**: 
- `idx_item_type` on `item_type`
- `idx_removed_at` on `removed_at`

**Purpose**: Allows recovery of accidentally deleted products, categories, or users.

---

### 11. `sales`

Sales data tracking (may be legacy or for reporting).

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `SalesID` | INT(11) | NO | - | Primary key (auto-increment) |
| `Purchased_Date` | DATETIME | NO | CURRENT_TIMESTAMP | Sale date |
| `Payment_Amount` | FLOAT | NO | - | Amount paid |
| `InventoryID` | INT(11) | NO | - | Foreign key to `inventory.InventoryID` |
| `CategoryID` | INT(11) | NO | - | Category reference |

**Primary Key**: `SalesID`  
**Foreign Key**: `InventoryID` → `inventory.InventoryID`

**Purpose**: Historical sales tracking (may be used for reporting).

---

### 12. `system_logs`

Comprehensive activity logging for audit trail.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `log_id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `action_type` | VARCHAR(255) | NO | - | Type of action (e.g., 'Login', 'Product Created') |
| `description` | TEXT | NO | - | Detailed description of action |
| `user_fullname` | VARCHAR(255) | NO | - | Full name of user who performed action |
| `log_timestamp` | DATETIME | NO | CURRENT_TIMESTAMP | Action timestamp |
| `transaction_id` | INT(11) | YES | NULL | Related transaction ID (if applicable) |

**Primary Key**: `log_id`

**Purpose**: Complete audit trail of all system activities.

**Common Action Types**:
- Login/Logout
- POS Transaction
- Product Created/Updated/Removed
- Category Created/Updated/Deleted
- User Created/Updated/Deleted
- Report Generated
- Backup/Restore Operations
- Discount Applied/Removed

---

### 13. `transactions`

Main transaction table for all POS sales.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT(11) | NO | AUTO_INCREMENT | Primary key |
| `serial_num` | VARCHAR(10) | NO | - | Serial number (may be empty) |
| `transaction_num` | INT(11) | NO | - | Transaction number |
| `transaction_date` | DATETIME | NO | CURRENT_TIMESTAMP | Transaction timestamp |
| `total_amount` | DECIMAL(10,2) | NO | 0.00 | Total transaction amount |
| `details` | TEXT | NO | - | Transaction details (items, cashier) |
| `processed_by` | VARCHAR(255) | YES | NULL | Cashier username |
| `payment` | DECIMAL(10,2) | YES | 0.00 | Amount paid by customer |
| `change_amount` | DECIMAL(10,2) | YES | 0.00 | Change given to customer |
| `payment_method` | VARCHAR(20) | YES | 'cash' | Payment method ('cash', 'gcash', 'paymaya') |
| `reference_no` | VARCHAR(60) | YES | NULL | Reference number for digital payments |

**Primary Key**: `id`  
**Indexes**: 
- `idx_transaction_date` on `transaction_date`
- `idx_serial_num` on `serial_num`

**Purpose**: Primary table for all POS transactions with payment details.

---

## Relationships

### One-to-Many Relationships

1. **categories → inventory**
   - One category can have many products
   - `categories.CategoryID` → `inventory.CategoryID`

2. **loginaccount → active_sessions**
   - One user can have one active session
   - `loginaccount.UserID` → `active_sessions.user_id`

3. **inventory → inventory_update**
   - One product can have many update records
   - `inventory.InventoryID` → `inventory_update.InventoryID`

4. **inventory → sales**
   - One product can have many sales records
   - `inventory.InventoryID` → `sales.InventoryID`

5. **orders → order_items**
   - One order can have many items
   - `orders.OrderID` → `order_items.OrderID`

### Referential Integrity

- Foreign keys are enforced where defined
- Some relationships may be logical (not enforced by FK constraints)
- Soft deletes use `recovery_items` table instead of actual deletion

---

## Indexes

### Primary Indexes
All tables have primary keys on their ID columns for fast lookups.

### Secondary Indexes

1. **active_sessions**
   - `idx_user_id` on `user_id` - Fast user session lookup

2. **recovery_items**
   - `idx_item_type` on `item_type` - Filter by item type
   - `idx_removed_at` on `removed_at` - Sort by deletion date

3. **transactions**
   - `idx_transaction_date` on `transaction_date` - Date range queries
   - `idx_serial_num` on `serial_num` - Serial number lookup

### Index Usage
- Date-based queries use date indexes
- User lookups use user_id indexes
- Transaction searches use transaction_date indexes

---

## Data Types Reference

### Integer Types
- **INT(11)**: Standard integer (auto-increment for IDs)
- **TINYINT(1)**: Boolean-like (0/1 for flags)

### String Types
- **VARCHAR(n)**: Variable-length string (n = max characters)
  - Usernames: 255
  - Names: 255
  - Passwords: 255 (hashed)
  - Emails: 255
  - Session IDs: 255
- **TEXT**: Variable-length text (up to 65,535 characters)
- **LONGTEXT**: Very large text (up to 4GB) - used for JSON data

### Numeric Types
- **FLOAT**: Floating-point number (for prices in legacy tables)
- **DECIMAL(10,2)**: Fixed-point decimal (10 digits, 2 decimal places)
  - Used for: prices, amounts, payments, change

### Date/Time Types
- **DATETIME**: Date and time (YYYY-MM-DD HH:MM:SS)
- **TIMESTAMP**: Auto-updating timestamp
- **TIME**: Time only (HH:MM:SS)

### Special Types
- **AUTO_INCREMENT**: Automatically increments for primary keys
- **DEFAULT CURRENT_TIMESTAMP**: Sets current date/time on insert
- **ON UPDATE CURRENT_TIMESTAMP**: Updates timestamp on row update

---

## Common Queries

### Get All Products with Categories
```sql
SELECT i.*, c.CategoryName 
FROM inventory i 
LEFT JOIN categories c ON i.CategoryID = c.CategoryID 
WHERE i.is_available = 1;
```

### Get Recent Transactions
```sql
SELECT * FROM transactions 
ORDER BY transaction_date DESC 
LIMIT 10;
```

### Get User Activity Logs
```sql
SELECT * FROM system_logs 
WHERE user_fullname = 'John Doe' 
ORDER BY log_timestamp DESC;
```

### Get Low Stock Items
```sql
SELECT * FROM inventory 
WHERE Quantity < 10 AND is_available = 1;
```

### Get Sales by Category
```sql
SELECT c.CategoryName, SUM(s.Payment_Amount) as TotalSales
FROM sales s
JOIN inventory i ON s.InventoryID = i.InventoryID
JOIN categories c ON i.CategoryID = c.CategoryID
GROUP BY c.CategoryID, c.CategoryName;
```

---

## Database Maintenance

### Regular Maintenance Tasks

1. **Clean Old Sessions**
   ```sql
   DELETE FROM active_sessions 
   WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE);
   ```

2. **Clean Expired Lockouts**
   ```sql
   UPDATE login_attempts 
   SET lockout_level = 0, lockout_start = NULL 
   WHERE lockout_start < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
   ```

3. **Archive Old Logs** (if needed)
   ```sql
   -- Move logs older than 1 year to archive
   -- Implementation depends on archiving strategy
   ```

4. **Optimize Tables**
   ```sql
   OPTIMIZE TABLE transactions;
   OPTIMIZE TABLE system_logs;
   OPTIMIZE TABLE inventory;
   ```

### Backup Considerations

- All tables should be backed up regularly
- `recovery_items` contains soft-deleted data - important for recovery
- `system_logs` can grow large - consider archiving old logs
- `transactions` is critical - ensure regular backups

---

## Schema Version History

### Version 1.2 (Current)
- Added `payment_method` and `reference_no` to `transactions`
- Added `is_available` to `inventory`
- Added `two_factor_secret` to `loginaccount`
- Added `backup_settings` table
- Added `recovery_items` table

### Future Considerations
- Consider adding indexes on frequently queried columns
- Consider partitioning large tables (transactions, system_logs)
- Consider adding foreign key constraints where missing
- Consider adding triggers for automatic inventory updates

---

## Notes

1. **Legacy Tables**: `orders` and `sales` may be legacy tables. New transactions use `transactions` table.

2. **Soft Deletes**: Products, categories, and users are soft-deleted (stored in `recovery_items`) rather than permanently deleted.

3. **Session Management**: `active_sessions` prevents multiple simultaneous logins per user.

4. **Security**: Passwords are hashed using bcrypt. 2FA secrets are stored in `two_factor_secret`.

5. **Audit Trail**: All actions are logged in `system_logs` for complete audit trail.

6. **Data Integrity**: Some relationships are logical (not enforced by foreign keys) for flexibility.

---

**Last Updated**: 2024  
**Database Version**: v1.2  
**Schema Version**: 1.0
