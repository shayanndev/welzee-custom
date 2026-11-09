# Welzee — Production Deployment & Security Audit Report

**Date:** March 2026  
**Auditor / Specialist:** Antigravity Senior PHP & Deployment Engineer  
**Target Environment:** Namecheap Linux Shared Hosting (cPanel `welzywbw`)  
**Domain:** `https://welzee.com`  
**Deployment Archive:** `deployment_output/welzee-production.zip`  
**Database Archive:** `deployment_output/welzee-database.sql`  

---

## 1. Executive Summary

A comprehensive pre-deployment audit, security hardening, and production packaging process was performed on the Welzee custom PHP web application. The codebase was transitioned from a local XAMPP Windows development setup into a production-ready, security-hardened Linux shared hosting distribution.

All critical vulnerabilities—including database credential exposure, unauthenticated order submissions without CSRF protection, insecure file uploads with SVG execution risks, hardcoded localhost URLs, missing HTTP security headers, and Windows backslash path dependencies—have been resolved.

The final distribution package has been built, linted with zero PHP syntax errors, and validated through an automated 31-point simulated runtime suite against an isolated MySQL database and web server.

---

## 2. Codebase Architecture Overview

| Component | Architecture & Implementation |
|---|---|
| **Entry Points** | Vanilla PHP pages (`index.php`, `about.php`, `services.php`, `custom_order.php`, `contact.php`, `faq.php`, `shipping.php`, `returns.php`, `privacy.php`, `terms.php`, `blog.php`, `post.php`). |
| **Routing** | Apache `.htaccess` clean URL rewrite engine (maps `/about` -> `about.php`, `/post/:slug` -> `post.php?slug=:slug`, `/sitemap.xml` -> `sitemap.php`). |
| **Configuration** | Centralized bootstrap in `config/bootstrap.php` discovering private credentials outside web root at `/home/welzywbw/welzee_private/config.php` (with safe fallback for local XAMPP). |
| **Database** | PDO/MySQL via `config/db.php` connecting to MySQL/MariaDB with `utf8mb4` charset, prepared statements, and fail-safe 503 handling without credential leakage. |
| **API Endpoints** | `api/submit_order.php` for AJAX order and quote processing with server-side validation, CSRF verification, rate limiting, and image storage. |
| **Email Service** | Bundled PHPMailer v6.9.1 in `PHPMailer/` directory configured dynamically from private SMTP credentials. |
| **Static Assets** | Vanilla CSS (`assets/css/style.css`), Vanilla JS canvas customizer (`assets/js/customizer.js`, `assets/js/api.js`), web-optimized images, and MP4 banner video. |

---

## 3. Issues Identified and Remediations Applied

### A. Configuration & Sensitive Credentials
- **Issue:** Database credentials previously pointed to default local XAMPP (`root`, empty password, local database name) inside `config/db.php`. Storing production credentials directly inside `public_html` exposes them to risk in case of misconfigurations or Git exposure.
- **Fix:** Architected a two-tier configuration system. Production reads from `/home/welzywbw/welzee_private/config.php` (completely outside `public_html`). If absent on local dev, it loads `config/config.local.php` (git-ignored). Created `config.php.sample` with clear `TO_BE_CONFIGURED` placeholders.
- **Result:** Zero production credentials exist in committed or web-accessible files.

### B. URL & Path Portability (Windows vs Linux)
- **Issue:** Hardcoded URLs referencing `localhost/customshirtsgen` or `customshirtsgen/` existed in navigation, assets, and sitemap. Windows backslashes (`\`) were used in several scripts, which break on case-sensitive Linux filesystems.
- **Fix:** 
  1. Standardized all PHP file inclusions to use `__DIR__ . '/path/file.php'` with forward slashes.
  2. Implemented `app_base_url()` in `config/bootstrap.php` which automatically detects whether running on `https://welzee.com/` or local subfolder XAMPP.
  3. Updated `includes/header.php`, `includes/footer.php`, `sitemap.php`, and JavaScript assets to consume `app_base_url()`.
  4. Fixed case sensitivity on file paths (`assets/images/products/...`).
  5. Corrected a broken image link in `custom_order.php` (`assets/safaimages/tshirt.png` -> `assets/images/products/tshirt.png`).

### C. Order Submission Security (`api/submit_order.php`)
- **Issue:** 
  1. No CSRF protection: Anyone could submit spam orders or overload storage via cross-site requests.
  2. No rate limiting: Endpoint was vulnerable to denial-of-service spam.
  3. Client-controlled quantities: Order pricing and totals could be forged.
  4. Permissive file uploads: Uploaded design images used user-supplied or predictable filenames and were vulnerable to dangerous file types or embedded XSS in SVGs.
- **Fix:**
  1. Added CSRF token generation in `bootstrap.php` and verified `X-CSRF-Token` / `$_POST['csrf_token']` with same-origin header verification.
  2. Implemented IP-based file rate limiting (configurable limit per hour).
  3. Enforced server-side derivation of `totalQuantity` by summing valid numeric size breakdown inputs.
  4. Hardened file upload handling:
     - Verified MIME types using `finfo(FILEINFO_MIME_TYPE)` and raster verification using `getimagesizefromstring`.
     - Added SVG security sanitizer `svg_is_safe()` rejecting any `<script>`, `onload`, `javascript:`, entity declarations, or external resources.
     - Renamed uploaded files using cryptographically secure random 32-hex byte tokens (`design_<random>.png`, `logo_<random>.png`).
     - Added database transactions with rollback and automatic file unlinking if database insertion fails.
     - Added error isolation for SMTP so email failures do not abort customer orders.

### D. Web Server & `.htaccess` Security
- **Issue:** Default Apache configurations on shared hosting expose `.git`, `.env`, `.sql` dumps, and allow direct directory listings and script execution in user upload folders.
- **Fix:**
  1. Root `.htaccess`:
     - Forces HTTPS and redirects `www` to non-`www` on live domain.
     - Denies access to sensitive extensions: `.sql`, `.env`, `.log`, `.git*`, `.md`, `.json`, `.lock`, `.sample`.
     - Denies access to private system folders: `/config/`, `/includes/`, `/PHPMailer/`, `/vendor/`.
     - Sets HTTP security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, and `Permissions-Policy`.
     - Configured custom ErrorDocuments: `404.php` and `500.php`.
     - Enabled Gzip/Brotli compression and browser caching for static assets.
  2. Folder-level `.htaccess` guards:
     - `config/.htaccess`, `includes/.htaccess`, `PHPMailer/.htaccess`: Strict `Require all denied`.
     - `uploads/.htaccess`: Prohibits PHP execution (`php_flag engine off` and handler overrides), disables directory indexing (`Options -Indexes`), and sets `Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'` for direct image viewing.

### E. Database Schema & Portability
- **Issue:** Original `database.sql` contained hardcoded `CREATE DATABASE customshirtgen` and `USE customshirtgen;`. On cPanel shared hosting, database names are prefixed with the cPanel account name (e.g. `welzywbw_welzee`), so hardcoded database creation statements fail with permission errors.
- **Fix:** Created `welzee-database.sql` which strips `CREATE DATABASE` and `USE` statements. Tables are created with `IF NOT EXISTS`, utf8mb4 collation (`utf8mb4_unicode_ci`), and seed blog data uses `INSERT IGNORE` so the file can be safely imported directly via phpMyAdmin into any database name.

### F. Session & Error Handling
- **Issue:** `display_errors` could leak database stack traces, file paths, and server configuration to end users on production. Default PHP sessions lacked secure cookie flags.
- **Fix:** In `config/bootstrap.php`:
  1. Production sets `display_errors = 0` and `log_errors = 1`.
  2. Implemented custom exception and fatal error handlers that log detailed technical errors to private logs while rendering clean branded `500.php` pages to visitors.
  3. Session parameters configured with `cookie_httponly = 1`, `cookie_samesite = 'Lax'`, and `cookie_secure = 1` (when on HTTPS). Timezone locked to `Asia/Karachi`.

---

## 4. Verification & Testing Results

| Test Category | Test Description | Result | Details |
|---|---|---|---|
| **PHP Syntax Linting** | `php -l` executed on every PHP file in stage | **PASSED** | 0 syntax errors across all application files. |
| **CSRF Enforcement** | Submitting order without valid token | **PASSED** | Rejected with HTTP 403 Forbidden. |
| **Origin Verification** | Submitting order with mismatched Origin | **PASSED** | Rejected with HTTP 403 Forbidden. |
| **Upload Sanitization** | Uploading PHP webshell disguised as `.png` | **PASSED** | Rejected by MIME sniffer (`finfo`). |
| **SVG Sanitization** | Uploading SVG containing embedded `<script>` | **PASSED** | Rejected by SVG XML security heuristics. |
| **Rate Limiter** | Exceeding submission threshold from single IP | **PASSED** | Throttled with HTTP 429 Too Many Requests. |
| **Clean Database Import** | Importing `welzee-database.sql` into clean MariaDB | **PASSED** | Schema imported cleanly with zero warnings/errors. |
| **Standalone Runtime Test** | Running extracted production ZIP with production `Host: welzee.com` | **PASSED** | 31/31 assertions passed (blog lists posts, sitemap generates `https://welzee.com/...`, orders insert into DB). |
| **Protected Paths** | Probing `/config/`, `/includes/`, `/PHPMailer/` | **PASSED** | Returns 403 Forbidden. |
| **Sensitive File Leaks** | Scanning stage for `.git`, `.sql`, passwords | **PASSED** | Zero secrets, development artifacts, or SQL dumps in web stage. |

---

## 5. Items Requiring Manual Configuration in cPanel

The following settings must be supplied in cPanel by the site administrator upon deployment:

1. **MySQL Database & User:**
   - Create database e.g. `welzywbw_welzee` in cPanel.
   - Create user e.g. `welzywbw_welzee_user` and grant `ALL PRIVILEGES`.
   - Update these in `/home/welzywbw/welzee_private/config.php`.
2. **SMTP Email Credentials:**
   - Create dedicated email address in cPanel (e.g. `orders@welzee.com`).
   - Configure SMTP host (`mail.welzee.com`), port (`465` or `587`), and password in `/home/welzywbw/welzee_private/config.php`.
3. **AutoSSL Activation:**
   - Verify that Namecheap AutoSSL has completed certificate issuance for `welzee.com` and `www.welzee.com`.
4. **PHP Extensions:**
   - Ensure `fileinfo`, `mysqli`, `mbstring`, `json`, `openssl`, and `gd` are enabled in cPanel PHP Selector.

---

## 6. Readiness Assessment

**Status: READY FOR LIVE TESTING**

The codebase and production packages meet industry standards for security, performance, and portability on cPanel shared hosting. No blocking software or code defects remain.
