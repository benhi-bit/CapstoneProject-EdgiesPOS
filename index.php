<?php
session_start();
require_once __DIR__ . '/config/connection.php'; // Include your database connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Create login_attempts table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255),
    attempt_time DATETIME,
    ip_address VARCHAR(45),
    lockout_level INT DEFAULT 0,
    lockout_start DATETIME NULL
)";
$conn->query($createTable);

// Add new columns if they don't exist (for existing tables)
// Check if lockout_level column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM login_attempts LIKE 'lockout_level'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE login_attempts ADD COLUMN lockout_level INT DEFAULT 0");
}

// Check if lockout_start column exists
$checkColumn2 = $conn->query("SHOW COLUMNS FROM login_attempts LIKE 'lockout_start'");
if ($checkColumn2->num_rows == 0) {
    $conn->query("ALTER TABLE login_attempts ADD COLUMN lockout_start DATETIME NULL");
}

// Cleanup: Clear expired lockouts and invalid lockout levels
// This ensures we don't have stale lockout data causing issues
$cleanupExpired = "UPDATE login_attempts 
                   SET lockout_level = 0, lockout_start = NULL 
                   WHERE lockout_start IS NOT NULL 
                   AND (
                       (lockout_level = 1 AND lockout_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE))
                       OR (lockout_level = 2 AND lockout_start < DATE_SUB(NOW(), INTERVAL 3 MINUTE))
                       OR (lockout_level = 3 AND lockout_start < DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                       OR (lockout_level = 4 AND lockout_start < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
                       OR (lockout_level > 4 OR lockout_level < 1)
                   )";
$conn->query($cleanupExpired);

// Progressive lockout system: 1 min, 3 min, 5 min, 10 min, etc.
// Function to calculate lockout time based on level
function getLockoutTime($level) {
    // Validate level is reasonable (max level 4 = 10 minutes)
    $level = max(1, min(4, (int)$level)); // Clamp between 1 and 4
    $lockoutTimes = [1 => 60, 2 => 180, 3 => 300, 4 => 600]; // 1 min, 3 min, 5 min, 10 min
    return $lockoutTimes[$level] ?? 600; // Default to 10 minutes for level 4+
}

$loginError = false;
$accountLocked = false;
$userNotFound = false;
$remainingAttempts = 5;
$timeRemaining = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // First, check if the username exists (attempt tracking applies only to existing users) - case-sensitive
    $stmt = $conn->prepare("SELECT UserID, Username, Password, Role, FullName, two_factor_secret FROM loginaccount WHERE BINARY Username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("User not found: " . $user);
        $loginError = true;
        $userNotFound = true;
    } else {
        $row = $result->fetch_assoc();

        // Get the most recent lockout information
        $getLockout = $conn->prepare("SELECT lockout_level, lockout_start 
                                      FROM login_attempts 
                                      WHERE BINARY username = ? 
                                      AND lockout_start IS NOT NULL 
                                      ORDER BY lockout_start DESC 
                                      LIMIT 1");
        $getLockout->bind_param("s", $user);
        $getLockout->execute();
        $lockoutData = $getLockout->get_result()->fetch_assoc();
        $getLockout->close();

        $currentLockoutLevel = 0;
        $lockoutStartTime = null;
        $isCurrentlyLocked = false;
        $timeRemaining = 0;

        if ($lockoutData) {
            $currentLockoutLevel = (int)($lockoutData['lockout_level'] ?? 0);
            $lockoutStartTime = $lockoutData['lockout_start'];
            
            // Validate lockout level is reasonable (1-4)
            if ($currentLockoutLevel > 4 || $currentLockoutLevel < 1) {
                // Invalid lockout level, reset it
                $currentLockoutLevel = 0;
                $lockoutStartTime = null;
            } else if ($lockoutStartTime && $currentLockoutLevel > 0) {
                $lockoutDuration = getLockoutTime($currentLockoutLevel);
                $lockoutEndTime = strtotime($lockoutStartTime) + $lockoutDuration;
                $currentTime = time();
                
                if ($currentTime < $lockoutEndTime) {
                    // Still locked
                    $isCurrentlyLocked = true;
                    $timeRemaining = $lockoutEndTime - $currentTime;
                } else {
                    // Lockout has expired, clear the lockout data for this username
                    // This ensures we don't use stale lockout information
                    $clearExpiredLockout = $conn->prepare("UPDATE login_attempts 
                                                           SET lockout_level = 0, 
                                                               lockout_start = NULL 
                                                           WHERE BINARY username = ? 
                                                           AND lockout_start IS NOT NULL");
                    $clearExpiredLockout->bind_param("s", $user);
                    $clearExpiredLockout->execute();
                    $clearExpiredLockout->close();
                    $lockoutStartTime = null;
                    $currentLockoutLevel = 0;
                }
            }
        }

        // Count attempts since the last lockout expired (or since beginning if no lockout)
        // Only count if not currently locked (if locked, we'll just show lockout message)
        if (!$isCurrentlyLocked) {
            if ($lockoutStartTime && $currentLockoutLevel > 0) {
                // Count attempts after the last lockout expired
                $lockoutDuration = getLockoutTime($currentLockoutLevel);
                $lockoutEndTime = date('Y-m-d H:i:s', strtotime($lockoutStartTime) + $lockoutDuration);
                $countAttempts = $conn->prepare("SELECT COUNT(*) as attempts 
                                                 FROM login_attempts 
                                                 WHERE BINARY username = ? 
                                                 AND attempt_time > ?");
                $countAttempts->bind_param("ss", $user, $lockoutEndTime);
            } else {
                // No previous lockout, count attempts in the last 15 minutes to prevent infinite accumulation
                $countAttempts = $conn->prepare("SELECT COUNT(*) as attempts 
                                                 FROM login_attempts 
                                                 WHERE BINARY username = ? 
                                                 AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                $countAttempts->bind_param("s", $user);
            }
            $countAttempts->execute();
            $attemptResult = $countAttempts->get_result()->fetch_assoc();
            $attemptCount = (int)($attemptResult['attempts'] ?? 0);
            $countAttempts->close();
        } else {
            // Currently locked, don't count attempts (they can't attempt while locked)
            $attemptCount = 0;
        }

        $remainingAttempts = max(5 - $attemptCount, 0);

        if ($isCurrentlyLocked) {
            error_log("Account locked due to too many attempts: " . $user . " (Level: " . $currentLockoutLevel . ")");
            $accountLocked = true;
            $loginError = true;
        } else {
            // Check password
            if (password_verify($pass, $row['Password'])) {
                error_log("Successful login for username: " . $user);

                // Clear login attempts on successful login (case-sensitive)
                $clearAttempts = $conn->prepare("DELETE FROM login_attempts WHERE BINARY username = ?");
                $clearAttempts->bind_param("s", $user);
                $clearAttempts->execute();
                $clearAttempts->close();

                // Check if 2FA is enabled for this user
                $has2FA = !empty($row['two_factor_secret']);

                // Store pending login info in session (don't set full session yet)
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
            } else {
                // Wrong password -> record attempt (existing users only)
                error_log("Invalid password for username: " . $user);
                $logAttempt = $conn->prepare("INSERT INTO login_attempts (username, attempt_time, ip_address) VALUES (?, NOW(), ?)");
                $logAttempt->bind_param("ss", $user, $ip);
                $logAttempt->execute();
                $logAttempt->close();

                $attemptCountAfter = $attemptCount + 1;
                $remainingAttempts = max(5 - $attemptCountAfter, 0);

                // Check if we've reached 5 attempts and need to lock
                if ($attemptCountAfter >= 5) {
                    // Calculate new lockout level (increment from previous level, max level 4)
                    $newLockoutLevel = min(4, max(1, $currentLockoutLevel + 1));
                    $lockoutDuration = getLockoutTime($newLockoutLevel);
                    
                    // Get the ID of the most recent attempt (the one we just inserted)
                    $getLastAttempt = $conn->prepare("SELECT id FROM login_attempts WHERE BINARY username = ? ORDER BY attempt_time DESC LIMIT 1");
                    $getLastAttempt->bind_param("s", $user);
                    $getLastAttempt->execute();
                    $lastAttemptResult = $getLastAttempt->get_result();
                    $lastAttemptRow = $lastAttemptResult->fetch_assoc();
                    $getLastAttempt->close();
                    
                    if ($lastAttemptRow && isset($lastAttemptRow['id'])) {
                        $lastAttemptId = (int)$lastAttemptRow['id'];
                        
                        // Update the most recent attempt with lockout information
                        $updateLockout = $conn->prepare("UPDATE login_attempts 
                                                         SET lockout_level = ?, 
                                                             lockout_start = NOW() 
                                                         WHERE id = ?");
                        $updateLockout->bind_param("ii", $newLockoutLevel, $lastAttemptId);
                        $updateLockout->execute();
                        $updateLockout->close();
                    }
                    
                    $accountLocked = true;
                    $timeRemaining = $lockoutDuration;
                    error_log("Account locked for " . $lockoutDuration . " seconds (Level: " . $newLockoutLevel . ") for username: " . $user);
                }

                $loginError = true;
            }
        }
    }

    $stmt->close();

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Basic reset for the page */
        body, h2, form, input {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
            padding-right: calc(100vw - 100%); /* Add padding to compensate for scrollbar */
        }

        /* Styling for the form container */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
            position: fixed; /* Fix position to prevent movement */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .login-container h2 {
            margin-bottom: 30px;
            color: #2d3748;
            font-size: 24px;
            font-weight: 600;
        }

        /* Form elements styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #face0b;
            box-shadow: 0 0 0 3px rgba(250, 206, 11, 0.1);
        }

        /* Submit button */
        button {
            width: 100%;
            padding: 12px;
            background: #face0b;
            color: #000000;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(250, 206, 11, 0.2);
            background: #e6c009;
        }

        /* Link styling */
        a {
            color: #2d3748;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #face0b;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        .modal.show {
            display: flex;
            opacity: 1;
            pointer-events: auto;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #ffffff;
            margin: auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            animation: slideIn 0.3s cubic-bezier(.4,0,.2,1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .modal-content.modal-modern {
            padding: 0;
            border: none;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
        }

        .modal-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .modal-header .modal-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: rgba(255,255,255,0.14);
            display: flex;
            align-items: center;
            justify-content:center;
            font-size: 22px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }

        .modal-header p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.88);
            font-size: 14px;
        }

        .form-group small.form-hint {
            color: #64748b;
            font-size: 12px;
            margin-top: 6px;
            display: block;
            line-height: 1.5;
        }

        .modal-body {
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 22px;
            border: 1px solid rgba(15,23,42,0.08);
            box-shadow: 0 12px 26px rgba(15,23,42,0.08);
        }

        .section-card h3 {
            margin-top: 0;
            color: #0f172a;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .modal-actions .btn-secondary {
            padding: 11px 20px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            background: #e2e8f0;
            color: #0f172a;
            transition: background 0.15s ease;
        }

        .modal-actions .btn-secondary:hover {
            background: #cbd5f5;
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(-50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 12px;
            right: 18px;
            cursor: pointer;
            transition: color 0.3s;
            z-index: 10;
        }

        .close:hover,
        .close:focus {
            color: #000;
        }

        /* Forgot password form styling */
        #forgotPasswordForm, #answerForm {
            margin-top: 20px;
        }

        #recoveryQuestion {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        #questionText {
            color: #4a5568;
            margin-bottom: 15px;
            font-size: 15px;
        }

        /* Question selector styling */
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            margin-bottom: 15px;
            background-color: white;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: #face0b;
            box-shadow: 0 0 0 3px rgba(250, 206, 11, 0.1);
        }

        select option {
            padding: 10px;
        }

        #questionText {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        /* SweetAlert2 Custom Styles */
        .swal2-popup {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            border-radius: 15px !important;
        }

        .swal2-title {
            color: #2d3748 !important;
            font-size: 24px !important;
            font-weight: 600 !important;
        }

        .swal2-html-container {
            color: #4a5568 !important;
            font-size: 16px !important;
        }

        .swal2-confirm {
            background-color: #face0b !important;
            color: #000000 !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
        }

        .swal2-confirm:hover {
            background-color: #e6c009 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(250, 206, 11, 0.2);
        }

        .swal2-icon {
            border-width: 3px !important;
        }

        /* Remove yellow validation borders from SweetAlert */
        .swal2-input,
        .swal2-textarea,
        .swal2-select,
        .swal2-file {
            border: 1px solid #d1d5db !important;
            box-shadow: none !important;
        }
        
        .swal2-input.swal2-inputerror,
        .swal2-textarea.swal2-inputerror,
        .swal2-select.swal2-inputerror,
        .swal2-file.swal2-inputerror {
            border: 1px solid #d1d5db !important;
            box-shadow: none !important;
        }
        
        .swal2-validation-message {
            display: none !important;
        }
        
        /* Remove yellow borders from SweetAlert icons and popup */
        .swal2-icon.swal2-question {
            border-color: #c9dae1 !important;
            color: #87adbd !important;
        }
        
        .swal2-icon.swal2-warning {
            border-color: #face0b !important;
        }
        
        .swal2-popup {
            border: none !important;
        }
        
        /* Remove any yellow outlines or borders */
        .swal2-popup * {
            outline: none !important;
        }
        
        .swal2-popup:focus,
        .swal2-popup:focus-within {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Add styles for when SweetAlert is shown */
        body.swal2-shown {
            overflow: hidden;
        }

        .swal2-shown .login-container {
            filter: blur(2px);
            pointer-events: none;
            user-select: none;
        }

        .swal2-container {
            z-index: 9999 !important;
        }

        /* Input with icon styling */
        .input-with-icon {
            position: relative;
        }

        .input-with-icon i:not(#togglePassword) {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .input-with-icon input {
            padding-left: 35px !important;
        }

        .input-with-icon #togglePassword {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="images/logo.png" alt="Edgie's Logo" style="max-width: 110px; margin-bottom: 10px;">
            <h2>Edgie's Restaurant and Events</h2>
        </div>
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
            <button type="submit">
                <i class="fas fa-sign-in-alt" style="margin-right: 6px;"></i>
                Login
            </button>
        </form>
    </div>

    <!-- Modal for Forgot Password -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content modal-modern">
            <span class="close" id="closeModal">&times;</span>
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-unlock-alt"></i>
                </div>
                <div>
                    <h2>Forgot Password</h2>
                    <p>Follow the guided steps to securely reset your account credentials.</p>
                </div>
            </div>
            <div class="modal-body">
                <!-- Step 1: Username Form -->
                <div id="usernameStep" class="section-card">
                    <h3>Step 1 · Verify Username</h3>
                    <form id="forgotPasswordForm">
                        <div class="form-group">
                            <label for="forgotUsername">Username</label>
                            <input type="text" id="forgotUsername" name="forgotUsername" required>
                            <small class="form-hint">Enter your username to begin password recovery</small>
                        </div>
                        <button type="submit">
                            <i class="fas fa-arrow-right" style="margin-right: 6px;"></i>
                            Continue
                        </button>
                    </form>
                </div>

                <!-- Step 2: Security Question -->
                <div id="securityQuestionStep" class="section-card" style="display:none;">
                    <h3>Step 2 · Security Question</h3>
                    <div class="form-group">
                        <label>Security Question</label>
                        <p id="questionText" style="margin: 10px 0; padding: 10px; background-color: #f8f9fa; border-radius: 8px; color: #4a5568; font-size: 14px;"></p>
                        <form id="answerForm">
                            <label for="answer">Your Answer</label>
                            <input type="text" id="answer" name="answer" required>
                            <small class="form-hint">Enter the answer to your security question</small>
                            <button type="submit">
                                <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
                                Verify Answer
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Step 3: New Password -->
                <div id="newPasswordStep" class="section-card" style="display:none;">
                    <h3>Step 3 · Set New Password</h3>
                    <form id="newPasswordForm">
                        <input type="hidden" id="resetUsername" name="username">
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <div style="position: relative;">
                                <input type="password" id="newPassword" name="newPassword" required
                                       pattern="^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{6,}$"
                                       title="Password must be at least 6 characters long and contain at least one uppercase letter and one number">
                                <i class="fas fa-eye" id="toggleNewPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                            </div>
                            <small>Password must be at least 6 characters long and contain at least one uppercase letter and one number</small>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <div style="position: relative;">
                                <input type="password" id="confirmPassword" name="confirmPassword" required>
                                <i class="fas fa-eye" id="toggleConfirmPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                            </div>
                            <small class="form-hint">Re-enter your new password to confirm</small>
                        </div>
                        <button type="submit">
                            <i class="fas fa-save" style="margin-right: 6px;"></i>
                            Update Password
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Get modal elements
        var modal = document.getElementById("forgotPasswordModal");
        var forgotPasswordLink = document.getElementById("forgotPasswordLink");
        var closeModal = document.getElementById("closeModal");
        var usernameStep = document.getElementById("usernameStep");
        var securityQuestionStep = document.getElementById("securityQuestionStep");
        var newPasswordStep = document.getElementById("newPasswordStep");

        // Show modal when "Forgot Password?" is clicked
        forgotPasswordLink.onclick = function() {
            modal.style.display = "flex";
            modal.classList.add('show');
            resetForms();
        }

        // Close modal when "x" is clicked
        closeModal.onclick = function() {
            modal.style.display = "none";
            modal.classList.remove('show');
            resetForms();
        }

        // Close modal when clicking outside of the modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                modal.classList.remove('show');
                resetForms();
            }
        }

        // Reset all forms and steps
        function resetForms() {
            usernameStep.style.display = "block";
            securityQuestionStep.style.display = "none";
            newPasswordStep.style.display = "none";
            document.getElementById("forgotPasswordForm").reset();
            document.getElementById("answerForm").reset();
            document.getElementById("newPasswordForm").reset();
        }

        // Custom SweetAlert2 configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Handle username form submission
        document.getElementById("forgotPasswordForm").addEventListener("submit", function(event) {
            event.preventDefault();
            var username = document.getElementById("forgotUsername").value;

            // Show loading state
            var submitButton = this.querySelector("button[type='submit']");
            var originalText = submitButton.innerHTML;
            submitButton.innerHTML = "Checking...";
            submitButton.disabled = true;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "authentication/retrieve_question.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onload = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Display the saved recovery question
                            document.getElementById("questionText").textContent = response.currentQuestion;
                            
                            usernameStep.style.display = "none";
                            securityQuestionStep.style.display = "block";
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || "Username not found",
                                confirmButtonText: 'Try Again',
                                confirmButtonColor: '#face0b',
                                customClass: {
                                    popup: 'animated fadeInDown'
                                }
                            });
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error processing server response',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#face0b'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#face0b'
                    });
                }
            };

            xhr.onerror = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#face0b'
                });
            };

            xhr.send("username=" + encodeURIComponent(username));
        });

        // Handle security answer submission
        document.getElementById("answerForm").addEventListener("submit", function(event) {
            event.preventDefault();
            var answer = document.getElementById("answer").value;
            var username = document.getElementById("forgotUsername").value;

            // Show loading state
            var submitButton = this.querySelector("button[type='submit']");
            var originalText = submitButton.innerHTML;
            submitButton.innerHTML = "Verifying...";
            submitButton.disabled = true;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "authentication/validate_answer.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onload = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Set the username in the hidden field
                            document.getElementById("resetUsername").value = username;
                            securityQuestionStep.style.display = "none";
                            newPasswordStep.style.display = "block";
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Incorrect Answer',
                                text: response.message || "Please try again",
                                confirmButtonText: 'Try Again',
                                confirmButtonColor: '#face0b'
                            });
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error processing server response',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#face0b'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#face0b'
                    });
                }
            };

            xhr.onerror = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = true;
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#face0b'
                });
            };

            xhr.send("username=" + encodeURIComponent(username) + "&answer=" + encodeURIComponent(answer));
        });

        // Handle new password submission
        document.getElementById("newPasswordForm").addEventListener("submit", function(event) {
            event.preventDefault();
            var newPassword = document.getElementById("newPassword").value;
            var confirmPassword = document.getElementById("confirmPassword").value;
            var username = document.getElementById("resetUsername").value;

            if (newPassword !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'The passwords do not match. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b'
                });
                return;
            }

            // Show loading state
            var submitButton = this.querySelector("button[type='submit']");
            var originalText = submitButton.innerHTML;
            submitButton.innerHTML = "Updating...";
            submitButton.disabled = true;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "authentication/update_password.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onload = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Your password has been updated successfully. You can now login with your new password.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#face0b'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    modal.style.display = "none";
                                    modal.classList.remove('show');
                                    resetForms();
                                    // Clear the login form
                                    document.getElementById("username").value = "";
                                    document.getElementById("password").value = "";
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Failed',
                                text: response.message || "Error updating password",
                                confirmButtonText: 'Try Again',
                                confirmButtonColor: '#face0b'
                            });
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error processing server response',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#face0b'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#face0b'
                    });
                }
            };

            xhr.onerror = function() {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Please check your internet connection',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#face0b'
                });
            };

            xhr.send("username=" + encodeURIComponent(username) + "&newPassword=" + encodeURIComponent(newPassword));
        });

        <?php if ($loginError): ?>
        window.onload = function() {
            <?php if ($accountLocked && $timeRemaining > 0): ?>
                // Convert PHP seconds to JavaScript timestamp
                let lockEndTime = new Date().getTime() + (<?php echo $timeRemaining; ?> * 1000);
                
                // Create and show the alert
                let timerAlert = Swal.fire({
                    icon: 'error',
                    title: 'Account Locked',
                    html: 'Your account has been temporarily locked due to too many failed attempts.<br><br>' +
                          'Time remaining: <strong id="countdown">calculating...</strong>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                });

                // Update the countdown timer every second
                let countdownTimer = setInterval(function() {
                    let now = new Date().getTime();
                    let distance = lockEndTime - now;
                    
                    if (distance <= 0) {
                        clearInterval(countdownTimer);
                        Swal.close();
                        return;
                    }

                    let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    let seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    document.getElementById('countdown').innerHTML = 
                        minutes + " minute" + (minutes !== 1 ? "s" : "") + " " +
                        seconds + " second" + (seconds !== 1 ? "s" : "");
                }, 1000);
            <?php elseif ($userNotFound): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Account Not Found',
                    text: "Username or password doesn't exist.",
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b',
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid username or password. You have <?php echo $remainingAttempts; ?> attempt<?php echo ($remainingAttempts !== 1 ? "s" : ""); ?> remaining before your account is temporarily locked.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b',
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                });
            <?php endif; ?>
        };
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'active_session'): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Active Session Detected',
                    text: 'You are already logged in on another device or browser. Please log out from the other session first.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b',
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid username or password. You have <?php echo $remainingAttempts; ?> attempt<?php echo ($remainingAttempts !== 1 ? "s" : ""); ?> remaining before your account is temporarily locked.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#face0b',
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                });
            <?php endif; ?>
        <?php endif; ?>

        // Add password toggle functionality
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Initialize password toggles
        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('newPassword', 'toggleNewPassword');
        togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword');
    </script>
</body>
</html>
