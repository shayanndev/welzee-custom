# Welzee — Namecheap cPanel Production Deployment Guide

This guide walks you through deploying the **Welzee** custom PHP website directly to **Namecheap Linux shared hosting with cPanel**.

---

## 1. Hosting Environment Summary

| Parameter | Value |
|---|---|
| **Hosting Account Home** | `/home/welzywbw/` |
| **Web Document Root** | `/home/welzywbw/public_html/` |
| **Private Config Directory** | `/home/welzywbw/welzee_private/` (outside web root) |
| **Private Config File** | `/home/welzywbw/welzee_private/config.php` |
| **Live Domain** | `https://welzee.com` |
| **Target Database Engine** | MySQL 5.7+ / MariaDB 10.3+ |
| **Target PHP Version** | PHP 8.1 or 8.2 (64-bit recommended) |
| **Required PHP Extensions** | `mysqli`, `fileinfo`, `mbstring`, `json`, `openssl`, `gd` (or `imagick`), `curl` |

---

## 2. Pre-Deployment Checklist

1. [x] **Local WordPress backup confirmed**: Your WordPress files, database dump, and cPanel backups are safely stored on your local machine.
2. [ ] **Namecheap cPanel login ready**: You can log into `https://cpanel.welzee.com` or through your Namecheap dashboard.
3. [ ] **Deployment files ready locally**:
   - `deployment_output/welzee-production.zip`
   - `deployment_output/welzee-database.sql`
   - `deployment_output/private/config.php.sample`

---

## 3. Step-by-Step Deployment Instructions

### Step 1: Configure PHP Version & Extensions in cPanel
1. Log into your **cPanel** dashboard.
2. Under the **Software** section, click **Select PHP Version** (PHP Selector).
3. Set the current PHP version to **8.1** or **8.2**.
4. In the **Extensions** tab, verify that the following extensions are checked/enabled:
   - `mysqli`
   - `fileinfo` (crucial for secure file upload MIME sniffing)
   - `mbstring`
   - `json`
   - `openssl`
   - `gd`
   - `curl`
5. In the **Options** tab (optional but recommended):
   - `upload_max_filesize`: `16M` (or higher)
   - `post_max_size`: `20M`
   - `memory_limit`: `256M`
   - `display_errors`: `Off` (already handled in code)

---

### Step 2: Create the MySQL Database & User in cPanel
1. In cPanel, navigate to **Databases** > **MySQL® Databases**.
2. **Create New Database**:
   - Enter database name, e.g., `welzee` (full name will look like `welzywbw_welzee`).
   - Click **Create Database**.
3. **Create Database User**:
   - Scroll down to **MySQL Users** > **Add New User**.
   - Username: e.g., `welzee_user` (full username will look like `welzywbw_welzee_user`).
   - Password: Click **Password Generator**, copy the generated secure password, and save it in a private note.
   - Click **Create User**.
4. **Add User To Database**:
   - Under **Add User To Database**, select the user (`welzywbw_welzee_user`) and the database (`welzywbw_welzee`).
   - Click **Add**.
   - Check **ALL PRIVILEGES**.
   - Click **Make Changes**.

---

### Step 3: Import the Database (`welzee-database.sql`)
1. In cPanel, navigate to **Databases** > **phpMyAdmin**.
2. In the left sidebar, click on your newly created database (e.g., `welzywbw_welzee`).
3. Click the **Import** tab at the top.
4. Under **File to Import**, click **Choose File** and select `welzee-database.sql` from your local `deployment_output/` folder.
5. Ensure the character set is **utf-8**.
6. Click **Import** (or **Go**) at the bottom.
7. Verify success: You should see three tables created:
   - `blog_posts` (populated with starter articles)
   - `enquiries` (clean, 0 rows)
   - `enquiry_items` (clean, 0 rows)

---

### Step 4: Create the Private Configuration File Outside `public_html`
To protect your database credentials and SMTP password from any web browser access, Welzee looks for its configuration outside the web root at `/home/welzywbw/welzee_private/config.php`.

1. In cPanel, open **File Manager** (under **Files**).
2. Ensure you are at `/home/welzywbw/` (one level ABOVE `public_html`).
3. Click **+ Folder** at the top left:
   - Folder Name: `welzee_private`
   - Destination: `/home/welzywbw/`
   - Click **Create New Folder**.
4. Double-click to enter `/home/welzywbw/welzee_private/`.
5. Upload the file `deployment_output/private/config.php.sample`, and rename it to `config.php`.  
   *(Or click **+ File**, name it `config.php`, right-click it, choose **Edit**, and paste the contents of `config.php.sample`)*.
6. Edit the values inside `config.php`:
   ```php
   <?php
   return [
       'app_env'   => 'production',
       'debug'     => false,
       'site_url'  => 'https://welzee.com',

       'db' => [
           'host'     => 'localhost',
           'name'     => 'welzywbw_welzee',        // <-- YOUR CPANEL DB NAME
           'user'     => 'welzywbw_welzee_user',   // <-- YOUR CPANEL DB USER
           'password' => 'YOUR_STRONG_PASSWORD',   // <-- YOUR CPANEL DB PASSWORD
           'port'     => 3306,
           'charset'  => 'utf8mb4',
       ],

       'mail' => [
           'enabled'    => true,
           'host'       => 'mail.welzee.com',      // Or Namecheap cPanel mail server
           'port'       => 465,                    // 465 for SSL, 587 for TLS
           'encryption' => 'ssl',                  // 'ssl' or 'tls'
           'username'   => 'orders@welzee.com',    // Dedicated cPanel email account
           'password'   => 'YOUR_EMAIL_PASSWORD',
           'from_email' => 'orders@welzee.com',
           'from_name'  => 'Welzee Orders',
           'to_email'   => 'welzeecustom@gmail.com',
       ],

       'security' => [
           'session_lifetime' => 86400,
           'rate_limit_order' => 10,
       ],
   ];
   ```
7. Click **Save Changes**.
8. Set permissions on `/home/welzywbw/welzee_private/config.php` to `0600` (right-click -> **Change Permissions** -> check only Owner Read/Write).

---

### Step 5: Clean `public_html` & Extract `welzee-production.zip`
1. In cPanel File Manager, navigate inside `/home/welzywbw/public_html/`.
2. **Move or Delete Old WordPress Files**:
   - Because your WordPress files and DB are already safely backed up, select all existing WordPress files/folders inside `public_html` (`wp-admin`, `wp-content`, `wp-includes`, `index.php`, etc.) and move them into a temporary folder (e.g. `/home/welzywbw/wp_old_backup/`) or delete them so `public_html` is empty.
3. Click **Upload** at the top.
4. Select `deployment_output/welzee-production.zip`.
5. Once upload completes (100% green bar), go back to File Manager in `/home/welzywbw/public_html/`.
6. Click `welzee-production.zip`, then click **Extract** in the top toolbar.
7. Extract destination: `/home/welzywbw/public_html`.
8. Verify that files are extracted directly into the root of `public_html`:
   - `index.php`
   - `.htaccess`
   - `robots.txt`
   - `404.php`, `500.php`
   - `api/`, `assets/`, `config/`, `includes/`, `PHPMailer/`, `uploads/`
9. **Delete** `welzee-production.zip` from `public_html` so it is not publicly downloadable.

---

### Step 6: Verify Permissions
In cPanel File Manager, confirm:
- All directories under `public_html`: `755`
- All files under `public_html`: `644`
- Upload directories (`public_html/uploads/designs` and `public_html/uploads/logos`): `755` (must be writable by the web server user).
- Private config (`/home/welzywbw/welzee_private/config.php`): `600`

---

### Step 7: Verify SSL (AutoSSL / Namecheap SSL)
1. In cPanel, navigate to **Security** > **SSL/TLS Status**.
2. Verify that `welzee.com` and `www.welzee.com` have valid active SSL certificates.
3. If not, click **Run AutoSSL** and allow 2–5 minutes to complete.
4. Our `.htaccess` automatically forces all HTTP traffic to `https://welzee.com` and redirects `www.welzee.com` to `https://welzee.com`.

---

## 4. Production Verification Checklist

Once uploaded and configured, run through this smoke test:

| Test Item | URL / Action | Expected Result |
|---|---|---|
| **Homepage** | `https://welzee.com/` | Loads in 200 OK. Banner video plays, styling intact, brand is Welzee. |
| **HTTPS Redirection** | `http://welzee.com` | Redirects 301 to `https://welzee.com/`. |
| **www Redirection** | `https://www.welzee.com/` | Redirects 301 to `https://welzee.com/`. |
| **Clean URLs** | `https://welzee.com/about`<br>`https://welzee.com/services`<br>`https://welzee.com/contact` | Pages load without `.php` extensions. |
| **Blog System** | `https://welzee.com/blog`<br>`https://welzee.com/post/what-is-embroidery-patch` | Fetches articles from MySQL database cleanly. |
| **XML Sitemap** | `https://welzee.com/sitemap.xml` | Returns valid XML with dynamic post URLs under `https://welzee.com/`. |
| **Robots.txt** | `https://welzee.com/robots.txt` | Returns clean rules disallowing `/api/` and `/uploads/`. |
| **Custom 404** | `https://welzee.com/non-existent-page-xyz` | Returns 404 HTTP status code with styled Welzee 404 page (no server info leak). |
| **Private Config Guard** | `https://welzee.com/config/bootstrap.php` | Returns 403 Forbidden. |
| **Direct Uploads Guard** | `https://welzee.com/uploads/` | Returns 403 Forbidden (directory listing blocked). |
| **Customizer & Order Flow** | `https://welzee.com/custom_order` | 1. Visual canvas customizer works.<br>2. Can add to cart & fill customer enquiry form.<br>3. Submits via AJAX.<br>4. Success modal displays enquiry ID.<br>5. Check phpMyAdmin: new record appears in `enquiries` table.<br>6. Check uploads directory: files stored with randomized names.<br>7. Email notification sent to admin. |
