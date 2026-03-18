# Sonata Google Authenticator PHP Library – Explained

This document explains what the **Sonata Google Authenticator** library is, how it works, and what each part does in your project.

---

## What It Is

**Sonata Google Authenticator** is a PHP library from the Sonata Project. It implements the same **TOTP (Time-based One-Time Password)** algorithm that Google Authenticator and similar apps use. So:

- Your **server** (PHP) and the user’s **phone app** (e.g. Google Authenticator) both use the same secret and the same time-based math to generate and check 6-digit codes.
- The library does **not** talk to Google’s servers. It only implements the standard (RFC 6238 / Google’s Key URI format) so your app can work with any TOTP app.

You install it with Composer: `sonata-project/google-authenticator`. In your project it lives under `vendor/sonata-project/google-authenticator/`.

---

## Main Classes and What They Do

### 1. `GoogleAuthenticator` (the core)

This class does the real work: **generate secrets**, **compute codes**, and **verify codes**.

**Constructor (defaults):**

- `$passCodeLength = 6` → codes are 6 digits.
- `$secretLength = 10` → secret is 10 bytes (then Base32-encoded for display).
- `$codePeriod = 30` → a new code every 30 seconds (standard for Google Authenticator).

**Main methods:**

| Method | What it does |
|--------|----------------|
| **`generateSecret()`** | Creates a random secret key. Uses `random_bytes($secretLength)` and then encodes it in **Base32** (A–Z, 2–7) so it can be shown to the user and typed into the app. Returns a string like `JBSWY3DPEHPK3PXP`. |
| **`checkCode($secret, $code, $discrepancy = 1)`** | Checks if the 6-digit `$code` is valid for the given `$secret`. It computes the expected code for the current 30-second window and for a few windows before/after (controlled by `$discrepancy`) so small clock differences between server and phone are allowed. Uses **constant-time comparison** (`hash_equals`) so timing cannot be used to guess the code. Returns `true` if the code matches, `false` otherwise. |
| **`getCode($secret, $time = null)`** | Internal: given a secret and a time, it returns the 6-digit code that a TOTP app would show at that time. Uses HMAC-SHA1 and a specific truncation (same as the standard). Your setup/verify pages usually call `checkCode()`, not `getCode()` directly. |

So in short: **`generateSecret()`** gives you the key to store and show in the QR; **`checkCode()`** is what you use on login to accept or reject the user’s 6-digit code.

---

### 2. `GoogleQrUrl` (QR code for the app)

Authenticator apps add an account by **scanning a QR code**. The QR code encodes a special URL (the “Key URI”) that contains the secret and labels. The library does not draw the QR image itself; it **builds the URL** that you put into an image so the user sees a scannable QR.

**Method:**

- **`GoogleQrUrl::generate($accountName, $secret, $issuer = null, $size = 200)`**
  - `$accountName`: Label for the account (e.g. user name or "John (admin)").
  - `$secret`: The same secret string returned by `generateSecret()`.
  - `$issuer`: Optional; e.g. `"Edgie's POS System"`. Many apps show “Issuer: AccountName”.
  - `$size`: Width/height of the QR image in pixels (e.g. 200 → 200×200).

Inside, it builds a URL like:

`otpauth://totp/Edgie's%20POS%20System:John%20(admin)?secret=JBSWY3DPEHPK3PXP&issuer=...`

That string is the TOTP “Key URI” (Google’s format). Then the library wraps it in **another** URL: the **goqr.me API** URL, so that when you use this as an image source in HTML (`<img src="...">`), the browser gets a PNG of the QR code. So in your PHP you get one URL; when the user opens the page, they see a QR image. When they scan it, their app reads the secret and the labels and adds the account.

---

### 3. `FixedBitNotation` (Base32)

TOTP secrets are usually exchanged as **Base32** strings (letters A–Z and digits 2–7). The library uses `FixedBitNotation` to:

- **Encode** the random bytes from `generateSecret()` into that Base32 string.
- **Decode** the Base32 secret back to bytes when computing or checking a code (inside `getCode()`).

You don’t call this class directly in your app; `GoogleAuthenticator` uses it internally.

---

## How It Fits in Your Login Flow

1. **Setup (first time)**  
   You create `new GoogleAuthenticator()`, call `generateSecret()`, and store that string in the session. You pass the same secret to `GoogleQrUrl::generate()` to get the QR image URL. User scans the QR, gets the 6-digit code, and submits it. You call `checkCode($secret, $code)`; if it’s true, you save the secret in the database and complete login.

2. **Verify (every later login)**  
   You load the user’s secret from the database. User enters the 6-digit code. You call `checkCode($secret, $code)`; if it’s true, you complete the login (set session, redirect).

So: **Sonata Google Authenticator** = secret generation + code verification + QR URL generation. Your code handles the rest (sessions, database, redirects).

---

## Summary Table

| Component | Role |
|-----------|------|
| **GoogleAuthenticator** | `generateSecret()`, `checkCode()`, and internal `getCode()` for TOTP. |
| **GoogleQrUrl** | Builds the `otpauth://totp/...` URL and wraps it in a goqr.me image URL so you can show a QR code. |
| **FixedBitNotation** | Base32 encode/decode of the secret (used internally). |
| **Algorithm** | TOTP (time-based, 30-second windows, HMAC-SHA1, 6-digit code). Same as Google Authenticator app. |

No Google API key or account is required; the library is a standalone implementation of the TOTP standard that works with Google Authenticator and other compatible apps.
