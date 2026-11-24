# Welzee 3D customizer: launch checklist

Not deployed and not served: the deploy workflow skips `*.md` and the root `.htaccess` returns 404 for `.md`.

Plan: merge to `main` with the switch **OFF**, test the hidden pages on the live site, then switch it **ON** in the live config. Rollback = switch OFF (instant) or revert the merge.

---

## 0. Before merging (on your computer)

- [ ] All local browser tests passed (3D customizer, rack, order submit, classic fallback).
- [ ] The optimised models are committed (`assets/models/optimized/*.glb`, no `REPORT.md` needed). Without them the live site shows placeholder shapes.
- [ ] Repository history cleaned so the 13.7 MB original `hoodie.glb` (commit `617c08a`) is not pushed, and the leftover `assets/models/hoodie_opt.glb` is removed. (Separate, approved step.)
- [ ] `docs/MODEL_LICENSES.md` licence table filled in (private, not deployed).
- [ ] `git status` clean except files that must never be committed (`tools/*.sql`, `newDownloadedModels/`, `CLAUDE.local.md`).

## 1. Backup (live)

- [ ] cPanel > **Backup** (or Backup Wizard): download a **home directory** backup.
- [ ] cPanel > **phpMyAdmin** > the Welzee database > **Export** (Quick, SQL): save the `.sql` file.
- [ ] Copy `welzee_private/config.php` (one level above `public_html`) to your computer.
- [ ] Note the current `main` commit: `git rev-parse origin/main` (needed for rollback).

## 2. PHP settings (live, once)

cPanel > **MultiPHP INI Editor** (or Select PHP Version > Options) for `public_html`:

- [ ] `post_max_size` = **32M** or more (the 3D order can send up to 30 MB: design image + up to 6 logos).
- [ ] `upload_max_filesize` = **32M** (keep equal; the 3D order is JSON, but keep both in step).
- [ ] `memory_limit` = **256M** or more.
- No new writable folder: the 3D endpoint uses the same `uploads/designs`, `uploads/logos` and temp folder as today.

## 3. Merge and deploy (switch still OFF)

- [ ] Confirm `welzee_private/config.php` does **not** contain `use_3d_customizer` (or has it `false`).
- [ ] Merge `feature/3d-customizer` into `main` and push `main` (approved step).
- [ ] GitHub > **Actions** > "Deploy Welzee to Namecheap": wait for the green tick. If it fails, nothing changed on the server before the FTP step; read the log.

## 4. Smoke tests on the live site (switch OFF)

The public site must look and behave exactly as before:

- [ ] `https://welzee.com/`: "Design Your Own" goes to `/custom_order`; no "3D Rack" button.
- [ ] Header, footer, FAQ, services, contact, a blog post: customise links still go to `/custom_order`.
- [ ] `/custom_order` (classic) still submits an enquiry.

Hidden pages (not linked anywhere yet):

- [ ] `https://welzee.com/custom_order_3d.php`: poster shows at once; the 3D model replaces it within a few seconds; switch product (all 7) and colour; Front/Back switch; add text and a logo.
- [ ] View source shows `<meta name="robots" content="noindex, nofollow">`; response header `X-Robots-Tag: noindex, nofollow` (browser dev tools > Network).
- [ ] "Classic view" link opens `/custom_order`; going back to the 3D page shows the poster with **Try 3D**; pressing it starts 3D again.
- [ ] Phone (mobile data): the page opens fast; 3D starts by itself; if it takes over 6 s the "Taking long?" banner appears.
- [ ] Submit **one** test order on the 3D page with name `TEST 3D ORDER (delete me)`, front + back logo. Success modal appears; notification email arrives with both sides listed.
- [ ] `https://welzee.com/rack.php`: photos first, then 3D pieces on hangers/cords; hover/tap swings and turns; tap again opens the 3D customizer for that product.
- [ ] Browser console (F12): no red errors on both pages. `/assets/models/optimized/cap.glb` loads (HTTP 200).

## 5. Delete the test enquiry

- [ ] phpMyAdmin > `enquiries`: find the row with name `TEST 3D ORDER (delete me)`; note its `id`, `design_image_path`, `logo_image_path` and the logo paths in `message`.
- [ ] Delete its rows in `enquiry_items` (`enquiry_id` = that id), then the `enquiries` row.
- [ ] File Manager: delete those files in `public_html/uploads/designs/` and `public_html/uploads/logos/`.

## 6. Switch ON

- [ ] Edit `welzee_private/config.php`: inside the returned array add the line
  `'use_3d_customizer' => true,`
  (must be the word `true` without quotes).
- [ ] Reload `https://welzee.com/`: "Design Your Own" now opens `custom_order_3d.php`; the category cards open the right product; a "Browse the 3D Rack" button appears; header/footer links go to the 3D page.
- [ ] Place one more real-path check (open, do not submit) on a phone.

## 7. Rollback

- **Fast (seconds):** in `welzee_private/config.php` set `'use_3d_customizer' => false,` (or delete the line). All links return to `/custom_order` immediately; nothing else changes.
- **Full:** `git revert -m 1 <merge commit>` on `main`, push, wait for the Action. Note: the deploy never deletes server files, so `custom_order_3d.php`, `rack.php`, `api/submit_order_3d.php` and `assets/js/3d/`, `assets/models/` stay on the server (unlinked, noindex). Delete them in File Manager if wanted.
- **Last resort:** restore the cPanel backup and the database export from step 1.

## 8. Going public later (search engines)

Only when you want Google to index the 3D pages:

- `custom_order_3d.php`: delete line 6 `header('X-Robots-Tag: noindex, nofollow');` and the line `<meta name="robots" content="noindex, nofollow">` in `$extraHead`.
- `rack.php`: delete `header('X-Robots-Tag: noindex, nofollow');` and the same `<meta name="robots" ...>` line in `$extraHead`.
- Optionally add them to `sitemap.php` (separate change).
