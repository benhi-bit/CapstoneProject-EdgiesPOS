## Edgie’s POS System v1.3 — User Guide (Admin & Cashier)

This guide is written **specifically for the v1.3 codebase in this folder**. It describes only screens, labels, and workflows that exist in the system.

### Quick start

- **Open the system**: go to the site root (the `index.php` login page).
- **Roles**:
  - **Admin** → redirected to `admin/dashboard.php`
  - **Cashier** → redirected to `cashier/pos.php`

---

## Login, 2FA, and Password Recovery

### Logging in

1. On the **Login** page, enter **Username** and **Password**.
2. Click **Login**.

### Progressive lockout (too many attempts)

After repeated failed logins, the account is temporarily locked using a progressive delay:

- **1st lockout**: 1 minute
- **2nd lockout**: 3 minutes
- **3rd lockout**: 5 minutes
- **4th+ lockout**: 10 minutes

### Active session protection

If you see an “active session” error, the account is already logged in elsewhere. Log out on the other device/session, or wait until the session expires (inactivity timeout).

### Two-Factor Authentication (2FA)

After a correct username/password:

- **If 2FA is not yet set up**: you’ll be sent to **Setup Two-Factor Authentication** (`authentication/setup_2fa.php`)
  - Scan the QR code using an authenticator app (TOTP).
  - Enter the 6-digit code into the page and submit to finish setup.
- **If 2FA is already set up**: you’ll be sent to **Two-Factor Authentication** (`authentication/verify_2fa.php`)
  - Enter the 6-digit code from your authenticator app and submit.

After 2FA, you will be redirected by role (Admin/Cashier).

### First-login password change

If the account is marked as “first login”, you will be forced to **Change Password - First Login** (`authentication/change_password_first.php`).

- **Password rules**: at least **6 characters**, includes **1 uppercase letter** and **1 number**
- You cannot reuse your current password.

### Forgot Password (Security Questions)

On the login page, click **Forgot Password?** to open the guided modal:

- **Step 1 · Verify Username**: enter your username → **Continue**
- **Step 2 · Security Question**: answer your saved security question → **Verify Answer**
- **Step 3 · Set New Password**: set and confirm a new password → **Update Password**

---

## Cashier Guide (`cashier/pos.php`)

### Navigation (sidebar)

Cashier POS uses a compact sidebar with these items:

- **Take Out** (POS “Order” view)
- **Dine In** (Tables view)
- **Account** (cashier password change screen)
- **Logout**

### Take Out (Order screen)

The screen is split into:

- **MENU** (left): categories + **Search products…** + product grid
- **BILL** (right): current bill/cart and totals

#### Add items to the bill

- Click a **category** (or **All**) to filter products.
- Use **Search products…** to search by name.
- Click a product tile to add it to the bill.
  - Products marked **Unavailable** cannot be clicked/added.

#### Edit quantities / remove items

For each bill item you can:

- Use **- / +** buttons
- Type a quantity in the quantity input
- Click the **trash** icon to remove the item

#### Discounts, VAT, and manual discount

Per item, you can toggle:

- **PWD/Senior (20%)** (checkbox)
  - Applies a **20% discount** to that item
  - Discounted items are treated as **VAT Exempt**

In the BILL totals area:

- **Subtotal (VAT Inclusive)**
- **VAT Amount (12%)**
- **VAT Exempt Amount**
- **Manual Discount (%)** (input)
  - Enter a **percentage** (0–100). The discount amount is applied to the total before manual discount (after PWD discount if any).
- **PWD Discount** line appears when any item discount is applied.

#### Order type

In BILL totals:

- **Order Type** dropdown: **Dine In** or **Take Out**

#### Payment methods

In BILL totals:

- **Payment Method**:
  - **Cash**
  - **GCash**
  - **Maya**

Rules:

- **Cash**: enter the **Cash** amount; the system calculates **Change**.
- **GCash**: requires **Reference No** of exactly **9 digits**.
- **Maya**: requires **Reference No** of exactly **6 digits**.

#### Checkout (Pay)

1. Click **Pay**.
2. Review the **Order Confirmation** window (cashier, transaction #, date & time, order type, items, totals).
3. Click **Confirm Order** to finalize the transaction.
4. A printable receipt window opens and prints (browser print dialog / auto-print behavior depends on browser settings).

### Dine In (Tables view)

Tables view lets you manage multiple open tables (each has its own bill).

#### Create a table

1. Go to **Dine In**.
2. Click **Add Table**.
3. Choose a table number from the grid (taken numbers are disabled).

#### Add items to a table bill

1. On the table card, click **Add**.
2. In the “Add Products” modal, choose a category / search, then click products to add them to that table.

#### Print bill → Pay → receipt

1. On the table, click **Print Bill**.
2. After printing, the table switches to **Select Payment Method** (Cash/GCash/Maya).
3. Enter Cash amount (for Cash) or Reference Number (for GCash/Maya), then click **Pay**.
4. On success:
   - A receipt window opens and prints.
   - The table is automatically closed/removed.

> Note: Table mode prints a **bill** first, then exposes the **Pay** step.

### Cashier Account (`cashier/account_settings.php`)

- Cashier can **change password** (current password + new password + confirm).
- Password rules match the system rules (min length + uppercase + number).

---

## Admin Guide

Admin pages use a sidebar with:

- **Dashboard**
- **Product Management**
- **Transaction History**
- **User Management**
- **Log History**
- **Backup & Restore**
- **Account Settings**
- **Logout**

### Dashboard (`admin/dashboard.php`)

The dashboard shows key totals and charts, including:

- Total Orders / Total Sales
- Today’s Sales / Today’s Orders
- Total Categories
- Sales Over Time chart with a **date range** filter
- Most Sold Products with **year/month** filters
- Monthly Sales Report donut chart with **year** filter

Cards update live via real-time updates.

### Product Management (`admin/product_management.php`)

This page contains **Categories** and **Products** management:

- **Categories**: Add / Edit / Remove, with search/filtering.
- **Products**: Add / Edit / Remove, filter by category and search by name.
- Product fields include **Product Name**, **Category**, **Price**, and optional **Product Image**.
- Products can be toggled **available/unavailable**.

### Transaction History (`admin/transaction_history.php`)

Admins can:

- Filter by **start date / end date**
- Search by **transaction number**
- Sort by transaction/date/amount/payment/change
- Open **View Details** to see:
  - Transaction summary
  - Ordered items
  - Payment summary (method + optional reference no)
- **Reprint** receipts (opens a print window)
- Generate reports from the page (date range modal and quick presets like daily/weekly/monthly)

### User Management (`admin/user_management.php`)

Admins can manage cashier accounts:

- View the list (sortable columns: role/name/username/email/date created)
- **Add User** (includes password + **Recovery Question** and **Recovery Answer**)
- **Edit User** (update profile fields; optionally set a new password)
- **Remove** users (removed accounts go to recovery in **Backup & Restore**)

### Log History (`admin/log_history.php`)

Admins can:

- View log totals (today / week / month / total)
- Filter logs by search text and date range
- Sort logs by different columns
- Open a “View” modal for POS transaction-related logs (when available)

### Backup & Restore (`admin/backup_recovery.php`)

Admins can:

- **Download Backup** (manual database backup)
- View/edit **Automatic Backup** schedule
- **Restore** database by uploading a `.sql` file (overwrites data)
- Manage **Recovery Items**:
  - Restore or permanently delete removed items

### Account Settings (`admin/account_settings.php`)

Admins can:

- Update **Full Name**, **Username**, and **Email Address** (with uniqueness validation)
- Change password (current + new + confirm)

---

## Appendix: Other Cashier pages found in `cashier/` (may not be linked from the POS sidebar)

Depending on how your deployment is wired, you may also see these pages:

- `cashier/cashier_transaction_history.php`: transaction list with **Start Date / End Date** filter and “View Details” links
- `cashier/cashier_sales_report.php`: sales overview with date filter and **Generate Sales Report** (downloads a CSV via `cashier/generate_cashier_report.php`)
- `cashier/cashier_inventory_history.php`: inventory update history table (from `inventory_update`)

