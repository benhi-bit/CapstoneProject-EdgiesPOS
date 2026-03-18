# Edgie's POS System v1 - Feature List

## 🔐 Authentication & Security Features

### Login & Access Control
- **Secure Login System** with username and password authentication
- **Two-Factor Authentication (2FA)** - TOTP-based 2FA using QR codes
- **Progressive Account Lockout System** - Automatic lockout after failed attempts:
  - 1st lockout: 1 minute
  - 2nd lockout: 3 minutes
  - 3rd lockout: 5 minutes
  - 4th+ lockout: 10 minutes
- **Password Recovery System** - Multi-step recovery using security questions
- **Active Session Management** - Prevents multiple simultaneous logins
- **Role-Based Access Control** - Admin and Cashier roles with different permissions
- **Session Security** - Back button prevention and session validation

---

## 👨‍💼 Admin Features

### Dashboard & Analytics
- **Real-Time Dashboard** with live statistics
- **Sales Analytics**:
  - Total Orders counter
  - Total Sales amount
  - Today's Sales tracking
  - Today's Orders counter
  - Total Categories count
- **Interactive Sales Charts**:
  - Line chart for sales over time (customizable date range)
  - Monthly sales donut chart (yearly view)
  - Most sold products table with quantity and sales tracking
- **Real-Time Updates** - Server-Sent Events (SSE) for live data updates
- **Date Range Filtering** - Custom date range selection for sales reports
- **Year/Month Filtering** - Filter reports by year and month

### Product Management
- **Add Products** - Create new products with:
  - Product name
  - Price
  - Category assignment
  - Quantity/Stock
  - Product images
  - Availability toggle
- **Edit Products** - Update existing product information
- **Delete Products** - Remove products from inventory
- **Product Search** - Search products by name
- **Product Images** - Upload and manage product images
- **Product Availability** - Toggle product availability status
- **Duplicate Product Check** - Prevents adding duplicate product names

### Category Management
- **Add Categories** - Create product categories
- **Edit Categories** - Update category names
- **Delete Categories** - Remove categories
- **Category Validation** - Prevents duplicate category names
- **Category Assignment** - Assign products to categories

### User Management
- **Add Users** - Create new user accounts with:
  - Full name
  - Username
  - Email
  - Role assignment (Admin/Cashier)
  - Password setup
- **Edit Users** - Update user information
- **Delete Users** - Remove user accounts
- **User List View** - View all users with sorting capabilities
- **User Sorting** - Sort by Role, Full Name, Username, or Date Created
- **User Registration** - New user registration system

### Transaction Management
- **Transaction History** - View all transactions with:
  - Transaction number
  - Date and time
  - Total amount
  - Payment method
  - Cashier name
  - Payment details
- **Transaction Search** - Search transactions by various criteria
- **Transaction Sorting** - Sort by date, amount, transaction number
- **Transaction Details View** - Detailed view of each transaction
- **Receipt Reprinting** - Reprint receipts for any transaction
- **Transaction Filtering** - Filter by date range

### Sales Reports
- **Generate Sales Reports** - Create comprehensive sales reports
- **Date Range Reports** - Generate reports for specific date ranges
- **Export Capabilities** - Export reports (if implemented)
- **Sales Analytics** - Detailed sales breakdown and analysis

### System Logs
- **Activity Logging** - Track all system activities:
  - User logins/logouts
  - Product additions/edits/deletions
  - Category changes
  - User management actions
  - Transaction completions
  - Backup operations
- **Log History View** - View all system logs with timestamps
- **Log Filtering** - Filter logs by action type or date
- **User Activity Tracking** - Track which user performed each action

### Backup & Recovery
- **Database Backup** - Manual and automatic database backups
- **Backup Scheduling** - Schedule automatic backups
- **Backup Recovery** - Restore from backup files
- **Recovery Point Management** - View and manage recovery points
- **Item Recovery** - Restore deleted items (products, categories, users)
- **Backup File Management** - Manage backup files

---

## 💰 Cashier Features

### Point of Sale (POS)
- **POS Interface** - User-friendly point of sale interface
- **Product Selection** - Browse and select products by category
- **Shopping Cart** - Add/remove items from cart
- **Quantity Management** - Adjust item quantities
- **Real-Time Price Calculation** - Automatic subtotal calculation
- **Discount System** - Apply 20% transaction-level discounts
- **VAT Calculation** - Automatic VAT calculation (12% VAT, exempt when discounted)
- **Order Type Selection** - Dine-in or Take-out options

### Checkout & Payment
- **Multiple Payment Methods**:
  - Cash payments
  - GCash payments (with reference number)
  - PayMaya payments (with reference number)
- **Payment Processing** - Secure payment handling
- **Change Calculation** - Automatic change calculation for cash payments
- **Reference Number Entry** - For digital payment methods
- **Order Confirmation** - Confirm and process orders

### Receipt Generation
- **Receipt Printing** - Print receipts for transactions
- **Receipt Details** - Includes:
  - Transaction number
  - Date and time
  - Items purchased
  - Quantities
  - Prices
  - Subtotal
  - Discount (if applicable)
  - VAT (if applicable)
  - Total amount
  - Payment method
  - Cash received
  - Change amount
  - Cashier name
- **Receipt Reprint** - Reprint receipts from transaction history

### Cashier Reports
- **Sales Reports** - View cashier-specific sales reports
- **Transaction History** - View transactions processed by cashier
- **Inventory History** - View inventory changes
- **Date Range Filtering** - Filter reports by date range

---

## 📊 Inventory Management

### Stock Management
- **Stock Tracking** - Real-time inventory quantity tracking
- **Automatic Stock Deduction** - Stock automatically decreases on sale
- **Stock Levels** - View current stock levels
- **Low Stock Alerts** - (If implemented) Alerts for low stock items
- **Inventory History** - Track inventory changes over time

---

## 🎨 User Interface Features

### Navigation
- **Collapsible Sidebar** - Expandable/collapsible navigation sidebar
- **Sidebar State Persistence** - Remembers sidebar state (expanded/collapsed)
- **Responsive Design** - Works on different screen sizes
- **Icon-Based Navigation** - Visual navigation with icons
- **Active Page Highlighting** - Highlights current page in navigation

### UI Components
- **Modern Design** - Clean and modern user interface
- **Real-Time Clock** - Live date and time display in sidebar
- **User Information Display** - Shows logged-in user's full name
- **SweetAlert2 Integration** - Beautiful alert and confirmation dialogs
- **Chart.js Integration** - Interactive charts and graphs

---

## 🔧 System Features

### Data Management
- **MySQL Database** - Robust database management
- **Data Validation** - Input validation and sanitization
- **Error Handling** - Comprehensive error handling
- **Transaction Safety** - Database transaction support

### Real-Time Features
- **Server-Sent Events (SSE)** - Real-time data updates
- **Live Dashboard Updates** - Dashboard updates without page refresh
- **Real-Time Notifications** - (If implemented) Real-time system notifications

### Security Features
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Input sanitization
- **Password Hashing** - Secure password storage (bcrypt)
- **Session Security** - Secure session management
- **CSRF Protection** - (If implemented) Cross-site request forgery protection

### Performance
- **Optimized Queries** - Efficient database queries
- **Image Optimization** - Product image handling
- **Caching** - (If implemented) Data caching for performance

---

## 📱 Additional Features

### Search & Filter
- **Product Search** - Search products in POS
- **Transaction Search** - Search transaction history
- **Advanced Filtering** - Multiple filter options

### Reporting
- **Sales Reports** - Comprehensive sales reporting
- **Transaction Reports** - Detailed transaction reports
- **Product Reports** - Product performance reports
- **Date Range Reports** - Custom date range reporting

### Notifications
- **System Notifications** - (If implemented) System-wide notifications
- **Alert System** - User-friendly alert system

---

## 🛠️ Technical Stack

- **Backend**: PHP
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **Libraries**:
  - Chart.js (for charts)
  - SweetAlert2 (for alerts)
  - Font Awesome (for icons)
  - ESC/POS PHP (for receipt printing)
  - QR Code libraries (for 2FA)

---

## 📝 Notes

- All features are role-based and accessible according to user permissions
- System logs all important actions for audit purposes
- Backup and recovery system ensures data safety
- Real-time updates keep data current across sessions
- Modern UI/UX design for better user experience

---

*Last Updated: Based on codebase analysis*
*System Version: v1*
