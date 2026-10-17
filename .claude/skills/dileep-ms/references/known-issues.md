# Known issues & traps

Review of Oct 2026. Most items were fixed in commits cfe9dce, 62cf46f, 7bcf2b8 and ce9e84e
(Oct 15, 2026). Check the code before assuming an item is still open.

## Invariants added by those fixes (keep them when editing nearby code)

- **CSRF**: `Auth::isLoggedIn()` rejects any signed-in POST/PUT/PATCH/DELETE without the session
  token. `includes/navbar.php` adds the `csrf_token` hidden field to POST forms and the
  `X-CSRF-Token` header to same-origin non-GET `fetch()`. A new page that POSTs must include
  `navbar.php`, or must send the token itself (`Auth::csrfToken()`). jQuery `$.ajax` POSTs are NOT covered
  (none exist today), so use `fetch()`.
- **Sessions** are re-read from `users` once per request (role, province, is_active), and logins
  regenerate the session ID. Failed logins are logged as `login_failed` and throttled per IP
  (`Auth::LOGIN_MAX_FAILURES` within `LOGIN_WINDOW_MINUTES`).
- **Auth redirects** use `Auth::appPath()`, so they work from `api/` too.
- **Notifications, map popups, CQPR**: queries are province-scoped and user text is escaped before
  `innerHTML`. JSON written into `<script>` in `index.php` uses `JSON_HEX_*` flags. The CQPR template
  builds conditions with a `{t}` alias token; never `str_replace` column names (that rewrites
  `:placeholders` too).
- **Settings**: full backup and Google Drive are super_admin only. Export, import and counts are
  province-scoped, and import always sets `province`.
- **Fieldwork** province = the assigned user's province (`resolveProvinceForAssignee()`). Writes are POST only.
- **Returns API**: admin/encoder only, and the proponent must be inside the user's province.
- **Report date filters**: when a date field is picked, only rows with that date in range match; with no
  field picked, rows missing `date_approved` fall back to `created_at` (both models' `getAll()`).
- **Fieldwork delete** is admin-only (`hasRole(['admin'])`) in the API and the page's menu.
- **Fieldwork reminder** (fixed 2026-10-17): it never fired because `autoUpdateStatuses()` turned every
  past-due row into `missed` before the overdue query ran. Auto-missing is gone; past-due rows stay
  pending/ongoing and show as overdue until an editor answers. Rows auto-missed before this fix can't be
  told apart from real misses. Same pass fixed a stored XSS in the table's Delete link (titles were put
  in inline `onclick` strings), "Auto-detect" status failing on save, and reschedules keeping `missed`.
- **Session cookie** is HTTPS-only via `.user.ini` (`session.cookie_secure = 1`); prod redirects http→https.

## Still open

1. **Leaked secrets**: git history was purged and force-pushed on 2026-10-16, but the DB password and the
   production admin password still need rotating (user, in cPanel / the app), and GitHub Support must
   purge cached objects (old hashes like `cfe9dce` still resolve).
2. **Live server**: since 2026-10-16 the deploy lands in the real `public_html` (FTP account
   `mikoyadmin@dileepsys.com`, home = `public_html`, `FTP_SERVER_DIR` = `./`). The live `.htaccess` is
   hand-managed (template minus PHP settings). LiteSpeed seems to ignore `.user.ini`, so the session cookie
   lacks HttpOnly/SameSite there; set them in cPanel's PHP options or in code. Old dumps/scripts sit in
   `/home/dilemvwz/offline-files/`; `migrations/backfill_fieldwork_province.sql` still needs a run in phpMyAdmin.
3. Activity-log scoping is by the **acting user's** province (`activity_logs.province` is never filled),
   so super_admin/RD edits to a province's records don't show in that province's admin log view.
4. `api/settings-gdrive-auth.php?action=disconnect` is a GET (CSRF-able disconnect; low impact).
5. `getOfficeForProvince(null)` falls back to the label 'NOFO'.

## Correctness / maintenance traps

9. Province lat/lng boxes are duplicated in `beneficiary-form.php`, `proponent-form.php` and
   `api/geocode.php`, while `getMapData()` uses one broad box. Keep them in sync.
10. Beneficiary/worker type option arrays are duplicated in both forms (and in import/report code).
11. `Proponent::saveAssociations()` deletes and reinserts all association rows, outside a transaction.
12. Models run `information_schema` checks / `CREATE TABLE IF NOT EXISTS` on every instantiation.
    `ensureBeneficiaryDetailColumns()` would create `beneficiary_full_name` as VARCHAR(255) (too short for
    the JSON). Make sure `expand_proponent_beneficiary_columns.sql` has run.
13. Several flows were added and reverted (commits 3047f30 → c549523 → 5850d19). Check `git log -p`
    before "re-fixing" proponent linking, autosave or the CQPR province filter.
14. Local PHP 8.5 prints a `PDO::MYSQL_ATTR_INIT_COMMAND` deprecation into pages (prod is 8.3). Run the
    local server with `-d error_reporting="E_ALL & ~E_DEPRECATED"`.
15. Repo clutter: `inspirations/` (Next.js mock-up), `.DS_Store` files, many overlapping migration READMEs.
