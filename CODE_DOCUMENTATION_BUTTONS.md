# Edgies POS System – Important Buttons (Code Documentation)

This document contains code snippets for important buttons used in the capstone project, suitable for inclusion in technical documentation.

---

## 1. Login Button

### Frontend (HTML) – `index.php`

The login form posts to the same page (`action=""`). The submit button triggers form submission.

<form method="POST" action="">
    <div class="form-group">
        <label for="username">Username:</label>
        <div class="input-with-icon">
            <i class="fas fa-user"></i>
            <input type="text" id="username" name="username" required>
        </div>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" required>
            <i class="fas fa-eye" id="togglePassword" style="right: 10px; cursor: pointer;"></i>
        </div>
    </div>
    <a href="#" id="forgotPasswordLink">Forgot Password?</a>
    <br>
    <button type="submit">Login</button>
</form>

### Backend (PHP) – `index.php` (main login flow)

Login is processed in `index.php` when `$_SERVER["REQUEST_METHOD"] == "POST"`. Key logic includes:

- Case-sensitive username lookup from `loginaccount`
- Progressive lockout after failed attempts (1 min, 3 min, 5 min, 10 min)
- `password_verify()` for password check
- On success: clear login attempts, set `$_SESSION['pending_user_id']`, `pending_username`, `pending_role`, then redirect to 2FA:
  - If 2FA enabled → `authentication/verify_2fa.php`
  - If 2FA not set → `authentication/setup_2fa.php`

// index.php (excerpt – after lockout checks)
if (password_verify($pass, $row['Password'])) {
    $clearAttempts = $conn->prepare("DELETE FROM login_attempts WHERE BINARY username = ?");
    $clearAttempts->bind_param("s", $user);
    $clearAttempts->execute();
    $clearAttempts->close();

    $has2FA = !empty($row['two_factor_secret']);
    $_SESSION['pending_user_id'] = $row['UserID'];
    $_SESSION['pending_username'] = $row['Username'];
    $_SESSION['pending_role'] = $row['Role'];

    if ($has2FA) {
        header("Location: authentication/verify_2fa.php");
        exit();
    } else {
        header("Location: authentication/setup_2fa.php");
        exit();
    }
}

---

## 2. Add New User Button

### Button and modal trigger – `admin/user_management.php`

<button id="addUserBtn" class="button"><i class="fas fa-user-plus"></i> Add User</button>

### Modal and form (Add New User) – `admin/user_management.php`

<div id="userModal" class="modal">
    <div class="modal-content add-user-modal">
        <span class="close">&times;</span>
        <div class="modal-header">
            <div class="modal-icon"><i class="fas fa-user-plus"></i></div>
            <div>
                <h2>Add New User</h2>
                <p>Create a new user account with role and security settings</p>
            </div>
        </div>
        <form id="addUserForm" method="POST" action="add_user.php" class="modal-body" novalidate>
            <!-- Account Information: fullName, username, email, role -->
            <!-- Password & Security: password, confirmPassword -->
            <!-- Account Recovery: recoveryQuestion, recoveryAnswer -->
            <div class="modal-actions">
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

### JavaScript (open modal and submit via AJAX) – `admin/js/user_management.js`

// Open Add User modal
const addUserBtn = document.getElementById('addUserBtn');
if (addUserBtn) {
    addUserBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (userModal) {
            userModal.style.display = 'flex';
            setTimeout(() => userModal.classList.add('show'), 10);
        }
    });
}

// Submit Add User form (AJAX)
const addUserForm = document.getElementById('addUserForm');
if (addUserForm) {
    addUserForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        }
        const formData = new FormData(addUserForm);
        const response = await fetch('add_user.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            // Close modal, show success SweetAlert, reload user_management.php
        } else {
            // Show error SweetAlert
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText || '<i class="fas fa-user-plus"></i> Create User';
        }
    });
}

### Backend (add user) – `admin/add_user.php`

- Validates role (Admin/Cashier), fullName, username, email, password (pattern: 6+ chars, 1 uppercase, 1 number), confirmPassword, recovery question/answer.
- Checks duplicate username, full name, email.
- Hashes password with `password_hash($password, PASSWORD_DEFAULT)`.
- Inserts into `loginaccount`; on success logs via `addSystemLog($conn, "User Created", ...)` and returns JSON for AJAX or redirects with success/error.

// add_user.php (excerpt)
$stmt = $conn->prepare("INSERT INTO loginaccount (Role, FullName, Username, Email, Password, RecoveryQuestion, RecoveryAnswer, Date_Created) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $role, $fullName, $username, $email, $password, $recoveryQuestion, $recoveryAnswer, $dateCreated);
if ($stmt->execute()) {
    addSystemLog($conn, "User Created", "New user account created: " . $username, $userFullName);
    echo json_encode(['success' => true, 'message' => 'User added successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding user: ' . $stmt->error]);
}

---

## 3. Edit User and Remove User Buttons

### Table action buttons – `admin/user_management.php`

<button class='editBtn button'
    data-id='<?php echo (int)$row["UserID"]; ?>'
    data-role='<?php echo $roleEscaped; ?>'
    data-fullname='<?php echo $fullNameEscaped; ?>'
    data-username='<?php echo $usernameEscaped; ?>'
    data-email='<?php echo $emailEscaped; ?>'>
    <i class='fas fa-edit'></i> Edit
</button>
<button class='removeBtn button' data-id='<?php echo $row["UserID"]; ?>'>
    <i class='fas fa-trash-alt'></i> Remove
</button>

- **Edit**: Opens edit modal and fills form from `data-*` attributes; form submits to `edit_user.php`.
- **Remove**: Confirmation (e.g. SweetAlert) then request to `remove_user.php` with user ID.

---

## 4. Logout Button

### Frontend (sidebar link) – e.g. `cashier/pos.php`, `admin/dashboard.php`

<a href="../authentication/logout.php" onclick="confirmLogout(event); return false;">
    <i class="fas fa-sign-out-alt"></i>
    <span>Logout</span>
</a>

### Logout confirmation (JavaScript)

function confirmLogout(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Logout Confirmation',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#face0b',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../authentication/logout.php';
        }
    });
}

### Backend – `authentication/logout.php`

<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../lib/log_helper.php';

$stmt = $conn->prepare("SELECT FullName, Role FROM loginaccount WHERE BINARY Username = ?");
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$userFullName = $userData['FullName'];
$userRole = $userData['Role'];

if (isset($_SESSION['UserID'])) {
    $removeSession = $conn->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_id = ?");
    $removeSession->bind_param("is", $_SESSION['UserID'], session_id());
    $removeSession->execute();
    $removeSession->close();
}

if ($userRole == 'Cashier') {
    addSystemLog($conn, "POS Logout", "Cashier ended POS session", $userFullName);
} else {
    addSystemLog($conn, "Logout", "User logged out", $userFullName);
}

session_destroy();
header("Location: ../index.php");
exit();
?>

---

## 5. Add Product Button

### Header button (opens Add Product modal) – `admin/manage_product.php`

<button type="button" id="addProductButton" onclick="openAddProductForm()">
    <i class="fas fa-plus"></i> Add Product
</button>

### Modal submit button (inside Add Product form) – `admin/manage_product.php`

<form method="POST" action="manage_product.php" enctype="multipart/form-data">
    <!-- fields: inventory_name, price, category_id, product_image -->
    <div class="modal-actions">
        <button type="submit" name="add_product" class="btn-primary">Add Product</button>
    </div>
</form>

### Backend (add product) – `admin/manage_product.php`

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $inventoryName = $_POST['inventory_name'];
    $price = $_POST['price'];
    $categoryId = $_POST['category_id'];
    // Optional image upload to ../images/
    $stmt = $conn->prepare("INSERT INTO inventory (CategoryID, InventoryName, Price, ImagePath) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $categoryId, $inventoryName, $price, $imagePath);
    if ($stmt->execute()) {
        addSystemLog($conn, "Product Created", $description, $userFullName);
        // redirect or set success message
    }
}

---

## 6. Confirm Order (Checkout) Button – POS

### Checkout confirmation modal – `cashier/pos.php`

<div class="checkout-actions">
    <button class="checkout-btn checkout-btn-cancel" onclick="closeWindow()">
        <i class="fas fa-times"></i> Cancel
    </button>
    <button class="checkout-btn checkout-btn-confirm" onclick="confirmOrder()">
        <i class="fas fa-check"></i> Confirm Order
    </button>
</div>

- `confirmOrder()` is defined in the same page and handles payment method, amount, and sending the order to the backend (e.g. `checkout.php` or equivalent) and updating UI/storage.

---

## 7. Edit Product and Remove Product Buttons

### Table row actions – `admin/manage_product.php` (generated in JS)

// Example structure (actual code may use template literals)
`<button onclick="openModal(${product.InventoryID}, '${escapedName}', ${parseFloat(product.Price)}, ${product.CategoryID})">
    <i class="fas fa-edit"></i> Edit
</button>
<button onclick="confirmRemoveProduct(${product.InventoryID}, '${escapedName}')">
    <i class="fas fa-trash-alt"></i> Remove
</button>`

- **Edit**: Opens edit product modal; form submits with `name="edit_product"` to `manage_product.php`.
- **Remove**: `confirmRemoveProduct()` shows confirmation then submits or requests removal (e.g. `remove_product` or dedicated endpoint).

---

## Summary Table

| Button / Action   | Location (file)              | Backend / Handler          |
|-------------------|------------------------------|----------------------------|
| Login             | `index.php`                  | `index.php` (POST)         |
| Add User          | `admin/user_management.php`  | `admin/add_user.php`       |
| Create User (submit) | Add User modal            | `admin/add_user.php` (AJAX)|
| Edit User         | `admin/user_management.php`  | `admin/edit_user.php`      |
| Remove User       | `admin/user_management.php`  | `admin/remove_user.php`    |
| Logout            | Sidebar (pos, dashboard, etc.) | `authentication/logout.php` |
| Add Product       | `admin/manage_product.php`   | `manage_product.php` (add_product) |
| Update Product    | Edit product modal           | `manage_product.php` (edit_product) |
| Remove Product    | Product table                | `manage_product.php` (remove_product) or remove handler |
| Confirm Order     | `cashier/pos.php`            | `confirmOrder()` → checkout logic |

---

*Generated for Edgies POS System v1.2 – Capstone Code Documentation.*
