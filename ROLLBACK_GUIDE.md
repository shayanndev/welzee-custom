# Welzee — Emergency Rollback Guide (Restoring WordPress)

This guide provides immediate step-by-step instructions for rolling back to your prior **WordPress** website and database if the Welzee PHP deployment encounters an unexpected issue on Namecheap hosting.

---

## 1. Rollback Overview & Prerequisites

Because you previously downloaded and secured:
1. Your WordPress files archive (e.g. `wp-content`, `wp-config.php`, core files)
2. Your WordPress MySQL database dump (`.sql` file)
3. Full cPanel account backup

You can restore your prior WordPress website to full operation within **5 to 10 minutes** without data loss.

---

## 2. Step-by-Step Restoration Procedure

### Step 1: Clear Current `public_html`
1. Log into **cPanel** and open **File Manager**.
2. Navigate to `/home/welzywbw/public_html/`.
3. If you want to keep the Welzee files for inspection:
   - Select all files inside `public_html/`.
   - Click **Move** in the top toolbar.
   - Move them to a temporary diagnostic folder: `/home/welzywbw/welzee_failed_attempt/`.
4. Ensure `/home/welzywbw/public_html/` is now empty.

---

### Step 2: Restore WordPress Files
1. In cPanel File Manager, ensure you are inside `/home/welzywbw/public_html/`.
2. Click **Upload** and upload your WordPress backup ZIP archive.
3. Once the upload finishes (100% green), return to File Manager.
4. Select the uploaded WordPress ZIP file and click **Extract**.
5. Extract files directly into `/home/welzywbw/public_html/`.
6. Confirm that the standard WordPress structure is restored at the root:
   - `wp-admin/`
   - `wp-content/`
   - `wp-includes/`
   - `index.php`
   - `wp-config.php`
   - `.htaccess`
7. Delete the uploaded WordPress ZIP archive to save disk space.

---

### Step 3: Restore the WordPress Database
*(Skip this step if you did not overwrite the original WordPress database during Welzee setup and created a separate database instead)*.

If your original WordPress database was altered or dropped:
1. In cPanel, navigate to **Databases** > **phpMyAdmin**.
2. In the left panel, select your original WordPress database name (check `wp-config.php` for `DB_NAME`).
3. Click the **Import** tab at the top.
4. Under **File to Import**, click **Choose File** and select your prior WordPress database `.sql` backup.
5. Click **Import** (or **Go**) at the bottom.
6. Verify that standard WordPress tables (`wp_posts`, `wp_options`, `wp_users`, etc.) are present and intact.

---

### Step 4: Verify `wp-config.php` Connection
1. In cPanel File Manager, open `/home/welzywbw/public_html/wp-config.php` for editing.
2. Confirm the database credentials match your active MySQL database in cPanel:
   ```php
   define( 'DB_NAME', 'welzywbw_your_original_wp_db' );
   define( 'DB_USER', 'welzywbw_your_wp_user' );
   define( 'DB_PASSWORD', 'your_wp_password' );
   define( 'DB_HOST', 'localhost' );
   ```
3. Save changes if any edits were made.

---

### Step 5: Verify Standard WordPress `.htaccess`
Confirm that `/home/welzywbw/public_html/.htaccess` contains the standard WordPress rewrite rules:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

---

### Step 6: Post-Restoration Verification
1. Open an incognito browser window and visit:
   - Homepage: `https://welzee.com/`
   - Admin Login: `https://welzee.com/wp-admin`
2. Log in with your WordPress administrator credentials.
3. In WordPress dashboard:
   - Go to **Settings** > **Permalinks** and click **Save Changes** (to refresh WordPress URL rewrite rules).
   - Clear any active WordPress caching plugins (e.g., WP Rocket, LiteSpeed Cache, W3 Total Cache).
4. Verify that the previous WordPress site is functioning normally.
