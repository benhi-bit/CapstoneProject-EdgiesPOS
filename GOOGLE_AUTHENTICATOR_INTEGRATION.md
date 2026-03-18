# How Google Authenticator (2FA) Is Integrated in This System

This document explains in full sentences how two-factor authentication (2FA) using Google Authenticator (or any TOTP-compatible app) was integrated into the Edgies POS system.

---

## Overview

The system uses **time-based one-time passwords (TOTP)**. The same standard is used by Google Authenticator, so users can use Google Authenticator or any compatible app (e.g. Microsoft Authenticator, Authy). The PHP library used is **Sonata Google Authenticator** (`sonata-project/google-authenticator`) from Composer. The server generates a **secret key** per user, the user adds that secret into the app (by scanning a QR code or typing the key), and on each login the user enters the **6-digit code** shown in the app. The server checks that the code matches the secret and the current time window; if it does, login is completed.

---

## Where the Secret Is Stored

The system stores one 2FA secret per user in the database. The `loginaccount` table has a column named `two_factor_secret`. If the column is missing (e.g. on an older database), the file `lib/ensure_2fa_column.php` adds it automatically when any script that uses 2FA runs. So the integration assumes the `loginaccount` table eventually has a `two_factor_secret` column (VARCHAR, nullable). When the column is empty for a user, the system treats that user as “2FA not set up” and sends them to the setup page after they enter the correct password. When the column has a value, the system treats 2FA as enabled and sends them to the verification page after password.

---

## What Happens When the User Submits Username and Password

The login form on the main page posts to the same page (or to `authentication/login_process.php` depending on how the form is configured). The server checks the username and password against the `loginaccount` table. If the password is wrong, the user sees an error and stays on the login page. If the password is correct, the server does **not** log the user in yet. Instead, it loads the user’s `two_factor_secret` from the database. If that field is empty, the server decides the user must set up 2FA for the first time; if it is not empty, the server decides the user must verify with a 6-digit code. In both cases, the server stores only “pending” login data in the session: `pending_user_id`, `pending_username`, and `pending_role`. It does not set `UserID`, `Username`, or `Role` in the session yet, and it does not set `2fa_verified`. Then the server redirects the browser: either to `authentication/setup_2fa.php` (first-time setup) or to `authentication/verify_2fa.php` (normal 2FA check). So after a correct password, the user always lands on one of these two pages and must complete 2FA before being considered logged in.

---

## First-Time Setup: How the Secret Is Created and Shown to the User

The setup page is `authentication/setup_2fa.php`. It runs only if the session already contains the pending login variables (otherwise it redirects back to the main login page). It uses the Sonata library: it creates a `GoogleAuthenticator` instance and, on a **GET** request (first time opening the page), generates a new secret with `$ga->generateSecret()`. That secret is stored temporarily in the session under `$_SESSION['2fa_secret']` and is not yet written to the database. The page then builds a QR code URL with `GoogleQrUrl::generate()`, passing the user’s display name, the secret, and an issuer name (e.g. “Edgie's POS System”). That URL is typically a Google Charts API URL that returns an image of a QR code. The page displays that image and also shows the secret as text so the user can type it into the app if they cannot scan. So in full: the server generates a secret, keeps it in the session, and shows the user a QR code (and the raw secret) so they can add the account in Google Authenticator or another TOTP app.

---

## First-Time Setup: How the User Proves Setup and Completes Login

On the same setup page there is a form where the user enters the 6-digit code from the authenticator app. When the user submits the form (POST), the server reads the code from `$_POST['code']` and the secret from `$_SESSION['2fa_secret']`. It then calls `$ga->checkCode($secret, $code)` to verify that the code is valid for that secret in the current time window. If the code is wrong, the user sees an error and can try again. If the code is correct, the server saves the secret permanently: it runs an UPDATE on `loginaccount` and sets `two_factor_secret` for that user to the same secret that was shown in the QR code. After that, the server clears the temporary `$_SESSION['2fa_secret']`, assigns the full login session variables (`UserID`, `Username`, `Role`, and `2fa_verified = true`), records the session in the `active_sessions` table, clears the pending session variables, writes a log entry (e.g. “2FA setup completed”), and redirects the user to the admin dashboard or the cashier POS depending on their role. So the user proves they have added the account in the app by entering a valid code once, and only then is the secret saved and login completed.

---

## Every Later Login: How the 6-Digit Code Is Checked

The verification page is `authentication/verify_2fa.php`. It also checks that the pending login variables are in the session; otherwise it redirects to the main login page. It loads the user’s stored secret from the database: `SELECT two_factor_secret FROM loginaccount WHERE UserID = ?` using `pending_user_id`. If the user has no secret (e.g. data inconsistency), the page shows an error. When the user submits the form, the server reads the 6-digit code from the request and calls `$ga->checkCode($userData['two_factor_secret'], $code)`. If the code does not match (wrong code or expired), the user sees “Invalid verification code” and can try again. If the code matches, the server completes the login the same way as after setup: it sets `UserID`, `Username`, `Role`, and `2fa_verified` in the session, inserts a row into `active_sessions`, clears the pending variables, logs the event (e.g. “2FA verified”), and redirects to the dashboard or POS. So on every login after the first, the user enters the code from the same authenticator account, and the server validates it against the stored secret.

---

## How Protected Pages Know the User Passed 2FA

Admin and cashier pages (e.g. dashboard, POS, user management) do not rely only on username or role. They also require that 2FA was completed in this session. At the top of those PHP files there is a check similar to: if the session does not contain `Username` or if `2fa_verified` is not set or not true, the script redirects the user to the login page. So even if someone had stolen the session before 2FA was done, or if the session were tampered with, they would not reach the protected content. Only after the user has entered a valid 2FA code on either the setup or the verify page does the server set `2fa_verified = true`, and only then do those pages allow access. So the integration ensures that every protected page is accessed only after password and 2FA have both been completed.

---

## Summary in One Paragraph

The system integrates Google Authenticator (TOTP) by storing a per-user secret in the `two_factor_secret` column of `loginaccount`. After the user enters a correct username and password, the server does not log them in immediately; it only stores pending login data and sends them to either a setup page (if they have no secret) or a verify page (if they already have a secret). On setup, the server generates a secret with the Sonata library, shows a QR code (and the secret as text) so the user can add the account in an authenticator app, and only when the user submits a valid 6-digit code does it save the secret to the database and complete the login. On every later login, the verify page loads the stored secret, checks the submitted code with `checkCode()`, and only then sets the full session and redirects to the app. Protected pages require both a valid session and `2fa_verified === true`, so access is only allowed after password and 2FA have both been successfully completed.
