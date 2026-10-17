---
name: dileep-ms
description: Project knowledge for DILEEP_MS, the DOLE DILEEP/DILP Monitoring & Encoding System (plain PHP 8 + MySQL/MariaDB, no framework) used by DOLE field offices in Negros Occidental, Negros Oriental and Siquijor. Use for ANY work in this repo — adding or changing pages, forms, models, API endpoints, reports (incl. CQPR), the dashboard/map, fieldwork schedule, notifications, users/roles, province scoping, settings/backups, SQL migrations, or Namecheap deployment — and when answering questions about how the system behaves.
---

# DILEEP_MS — DOLE DILEEP Monitoring System

Web app that encodes and monitors DOLE's Integrated Livelihood & Emergency Employment Program
(DILEEP, older name DILP) grants. Two record types:

- **Beneficiaries** (`beneficiaries`): individual grantees ("type I"), linked to a proponent.
- **Proponents** (`proponents`): group projects ("type G": LGUs, associations, ACPs), with long
  date pipelines, cheque/OR data, liquidation deadlines, associations and "returns".

Prod: Namecheap shared hosting (cPanel, MariaDB 11.4, PHP 8.3), domain dileepsys.com.
Local: any PHP 8.x + MySQL; this machine has PHP 8.5 and MySQL 8.0 (Homebrew).

Deep references (read when the task touches that area):
- [references/architecture.md](references/architecture.md): every page, API, include and JS module, plus the main flows.
- [references/database.md](references/database.md): schema, enums, migrations, which SQL file is canonical.
- [Implementation summaries](#implementation-summary-required-after-every-change): `docs/implementation-summaries/` holds the Word template, builder script and every dated summary.
- [references/known-issues.md](references/known-issues.md): bugs, security debt and traps found in review. **Read before touching auth, province scoping, reports, notifications or settings.**

## Stack & layout (flat, no router, no Composer, no build step)

```
*.php (root)        page controllers: logic at the top, HTML below, same file
api/*.php           JSON/file endpoints called by fetch() from pages
models/             Beneficiary.php, Proponent.php, FieldworkSchedule.php (PDO, province-scoped)
includes/           Auth.php, navbar/sidebar/footer, shared-styles.php (CSS), ux-utilities.php (window.DILP JS),
                    notification-*.php, cqpr-report-template.php
config/database.php .env loader + Database singleton (PDO)
assets/js/          location-autofill.js (DILPLocation), form-autosave.js (DILPAutoSave)
migrations/ fixes/  SQL migrations and one-off fix scripts (run by hand in phpMyAdmin/mysql)
philippines-region-province-citymun-brgy-master/   PSGC JSON used by api/get-locations.php
debug/ test_login.php migrate-database-online.php   diagnostic/ops scripts (do not ship publicly)
inspirations/       a Next.js login mock-up; NOT part of the app, ignore
docs/               user manual, install + Namecheap deploy guides, CQPR template image
```

Frontend is server-rendered HTML with CDN libraries: Bootstrap 5.3.0, Bootstrap Icons 1.10.0,
jQuery 3.7.0 + DataTables 1.13.6, Chart.js 4.4.0, Leaflet 1.9.4, FullCalendar 6.1.9, SheetJS xlsx 0.18.5,
Inter font. No npm. Keep using CDN tags with pinned versions, matching what pages already load.

## The page pattern (copy it exactly for new pages)

```php
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';          // as needed

$auth = new Auth();
$auth->requireLogin();                           // also enforces maintenance mode
$auth->requireRole(['admin', 'encoder']);        // when write access is needed

$sessionProvince = $auth->getProvince();         // null for cross-province roles
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince();     // super_admin OR regional_director
// ... handle POST, validate into $errors[], set $success, or redirect (PRG) ...
?>
<!DOCTYPE html> ... <head> Bootstrap CDN + <?php include 'includes/shared-styles.php'; ?>
                         <?php include 'includes/notification-styles.php'; ?>
<body> <?php $currentPage = 'beneficiaries'; ?>   <!-- sidebar active-state key -->
       <?php include 'includes/navbar.php'; ?>
       <div class="container-fluid"><div class="row">
         <?php include 'includes/sidebar.php'; ?>
         <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main"> ... </main>
       </div></div>
       bootstrap.bundle JS, <?php include 'includes/notification-script.php'; ?>,
       <?php include 'includes/ux-utilities.php'; ?>, <?php include 'includes/footer.php'; ?>
```

API endpoints: `session_start(); header('Content-Type: application/json');` then require
`dirname(__DIR__) . '/config/database.php'` and `Auth.php`; return
`{"success": bool, "message"|"error": ..., "data": ...}` and set an HTTP status code on failure.
Use `$auth->isLoggedIn()` + a 401 JSON instead of `requireLogin()` (which redirects with HTML).

Conventions: 4-space indent, LF, UTF-8 (`.editorconfig`); PDO prepared statements with `?`
placeholders everywhere (`ATTR_EMULATE_PREPARES=false`, `FETCH_ASSOC`, exceptions on);
`htmlspecialchars()` on every echoed value; section banners like
`// =========================================================`; models return `false` on failure
and log through `error_log()` (Proponent/FieldworkSchedule also expose `getLastError()`).
Timezone is Asia/Manila. Money is `DECIMAL(15,2)`, shown with the ₱ sign.

## Roles & province scoping (the core invariant)

Roles (`users.role` enum): `super_admin`, `admin`, `regional_director`, `encoder`, `user`.

| Role | Province | Can do |
|---|---|---|
| super_admin | NULL (all) | everything; passes every `hasRole()` check; Users, System Admin, Org Chart admin, Reset Records |
| regional_director | NULL (all) | admin-level data work across provinces; no Users / System Admin |
| admin | one province | CRUD + delete, Activity Logs, Settings (backup/import/export/maintenance) |
| encoder | one province | create/edit beneficiaries, proponents, fieldwork |
| user | one province | read-only |

`Auth::hasRole()` makes super_admin pass everything and lets regional_director pass
`admin`/`encoder`/`user` checks. Code that compares `$_SESSION['role']` directly bypasses this,
so list the extra roles yourself when you do that.

Provinces are the fixed list `Auth::PROVINCES` = Negros Occidental, Negros Oriental, Siquijor
(office codes NOCFO, NORFO, SFO via `Auth::getOfficeForProvince()`; NIR is the regional office).
Rules every data access must follow:

1. **Reads/updates/deletes** append the model's `provinceScope()` fragment:
   cross-province role → no filter; provincial user → `AND province = ?`; provincial user with
   NULL province → `AND 1 = 0` (fail closed). New queries on beneficiaries/proponents/fieldwork
   must do the same; raw SQL in pages/APIs must replicate it.
2. **Create**: provincial users always get their session province; cross-province roles choose,
   and the caller must check the value with `in_array($p, Auth::PROVINCES, true)` first.
3. **Province is immutable** after create (never in an UPDATE `SET`).
4. Pages pick `$provinceFilter` as `$_GET['province']` for cross-province roles and the session
   province otherwise; never trust a GET/POST province from a provincial user.

## Domain rules worth knowing

- **Status flows**: beneficiaries `pending → approved → implemented → monitored`; proponents add
  `liquidated` (`pending → approved → implemented → liquidated → monitored`). The map and CQPR show only
  approved and later statuses. Fieldwork: `pending/ongoing/completed/missed`; only pending→ongoing is automatic (past-due rows become "overdue" for the reminder, never auto-missed), via
  `FieldworkSchedule::autoUpdateStatuses()` unless `manual_override = 1`.
- **Liquidation deadline** = `date_turnover` + 10 days (LGU-associated) or + 60 days (every other
  type). Computed in `Proponent::calculateLiquidationDeadline()`, and also by DB triggers in the
  fresh-install schema (some prod DBs had triggers removed via `fixes/remove_triggers.sql`, so keep the PHP path).
- **Proponent types**: LGU-associated, Non-LGU-associated, By Administration, Others.
  **Category**: Formation / Enhancement / Restoration (CQPR codes F/E/R).
- **Type checkboxes**: `type_of_beneficiaries` and `type_of_worker(s)` are stored as `", "`-joined
  labels and validated against option arrays defined at the top of BOTH form files (keep them in sync).
- `proponents.beneficiary_full_name` stores a **JSON array** of names (legacy rows hold CSV);
  read it through `normalizeBeneficiaryNames()` in `proponent-form.php`. The count must equal
  `total_beneficiaries`, and male + female must not exceed the total.
- Beneficiaries **must link a proponent** (`proponent_id`, nullable FK, required by the form).
  The "Create new proponent" detour snapshots the beneficiary form into sessionStorage, opens
  `proponent-form.php?return=beneficiary-form`, then comes back with `?restore=1&link_proponent_id=…`.
- **Duplicate guards**: beneficiary = same first + last name + project name (case/trim-insensitive,
  within the user's province scope); proponent = same name + project title.
- **Coordinates**: validated against per-province lat/lng boxes (duplicated in both forms and
  `api/geocode.php`); `getMapData()` only plots points inside lat 9–12, lng 122–124.
- Geocoding uses OSM Nominatim via `api/geocode.php`; location dropdowns use PSGC codes
  (0645 Negros Occidental, 0746 Negros Oriental, 0761 Siquijor).
- Every write logs to `activity_logs` (user, action, table, record id, IP) through the model's
  `logActivity()` or `Auth::logActivity()`. Keep that for new write paths.
- Some models self-heal schema at construction time (`ensureProponentIdColumn`,
  `ensureBeneficiaryDetailColumns`, `ensureTable`, `CREATE TABLE IF NOT EXISTS` for associations,
  returns and system_settings). Still ship a real migration in `migrations/` for any schema change.

## Working locally

```bash
cp .env.namecheap.example .env    # then set DB_HOST=127.0.0.1, DB_NAME, DB_USER, DB_PASS, APP_ENV=local, APP_DEBUG=true
mysql -u root -e "CREATE DATABASE dilp_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -u root dilp_monitoring < migrations/fresh_install_production.sql
mysql -u root dilp_monitoring < migrations/add_proponent_id_to_beneficiaries.sql
mysql -u root dilp_monitoring < migrations/expand_proponent_beneficiary_columns.sql
php -S localhost:8000             # from repo root, then open http://localhost:8000/login.php
```

Seeded login: `admin` / `admin123` (super_admin). `.env.example` is a leftover Laravel template;
the app only reads `DB_*`, `APP_ENV`, `APP_DEBUG` and `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`.
With `APP_DEBUG=true` DB connection errors print full diagnostics.

There is no automated test suite. Verify changes with:
- `find . -name '*.php' -not -path './inspirations/*' -print0 | xargs -0 -n1 php -l` (currently all clean),
- then the real flow in a browser as **both** a provincial encoder/admin **and** a cross-province
  role, since most bugs here are role/province leaks.

## Making changes safely

- Schema change → new idempotent SQL file in `migrations/` (pattern: check
  `INFORMATION_SCHEMA`, then `PREPARE`/`EXECUTE`, as in `add_proponent_id_to_beneficiaries.sql`), update
  `fresh_install_production.sql` too, and note the order in `migrations/README.md`.
- New field on beneficiary/proponent → touch the model `create()` + `update()` (column list AND
  positional params, counted in order), the form's `$data` array + validation + HTML, the view
  page, `getAll()` filters/date-field whitelist if filterable, reports/CQPR/export/import if relevant.
- New date field used for report filtering → add it to the `$allowedDateFields` whitelist in the
  model (it is interpolated into SQL) and to the options in `reports.php`.
- Labels that mention an office must use `Auth::getForwardedToOfficeLabel($province)` /
  `getOfficeForProvince()`; do not hard-code "NOFO".
- Deletes are admin-only, confirm via POST on `*-delete.php`.
- Deploy = upload changed files to `public_html` via cPanel/FTP and run SQL in phpMyAdmin
  (`docs/NAMECHEAP_DEPLOYMENT_GUIDE.md`, `docs/DEPLOYMENT_CHECKLIST.md`). Take a DB backup first
  (Settings → Backup, or phpMyAdmin export).
- Never commit `.env`; never put real credentials in `*.example` files.
- Git: single `main` branch, author kimpegar00007; commit messages are plain sentences describing features.

## Implementation summary (REQUIRED after every change)

Every time you implement something (code, schema, config or behaviour change), finish by producing a
plain-language summary .docx in the same format as the reference Google Doc "DILEEPSYS UPDATES &
IMPROVEMENTS (10-16-26)". Do this without being asked, before the final reply.

1. **Write a spec JSON** in `docs/implementation-summaries/` named `spec-YYYY-MM-DD.json` (add a suffix such as `-b`
   if a spec for that date already exists):
   `{"date": "YYYY-MM-DD", "fixes": [[issue, description, "✔ Fixed"]], "features": [[feature, what it does, benefit]]}`.
   `date` is the day the change takes effect (use today's date; for a past implementation use its commit date).
   Omit `fixes` or `features` if the change has none.
2. **Build it**: `python3 docs/implementation-summaries/build_summary.py docs/implementation-summaries/spec-YYYY-MM-DD.json`.
   Output is `DILEEPSYS-Updates-and-Improvements-MM-DD-YY.docx` in the same folder. Never hand-build the file:
   `_template.docx` (exported from the reference doc) supplies the DOLE letterhead and logos in the header, the
   footer block, Times New Roman styling, margins (8.5x14 in), the numbered bold section headings and the grey-header
   bordered tables (ISSUES / DESCRIPTION / STATUS and FEATURE / WHAT IT DOES / BENEFIT).
3. **Layout the output must keep**: centred bold title "DILEEP MONITORING SYSTEM UPDATES & IMPROVEMENTS (MM-DD-YY)",
   then a centred italic line "Date of implementation: Month D, YYYY", then section "1. System Fixes & Improvements"
   and/or "2. New Features", each a table.
4. **Writing rules**: audience is non-technical DOLE staff. One short row per change, plain words, no file, function or
   table names, no code. Say what users now see or can do, and the benefit. Group related fixes into one row. Status
   column is "✔ Fixed" for fixes.
5. Check the .docx opens (`qlmanage -t -s 1400 -o <scratchpad> <file>.docx` on macOS renders a preview) and mention the
   file path in the final reply. Commit the .docx and spec with the code change.
6. To change the look for all future summaries, edit the reference Google Doc, re-export it as .docx over
   `_template.docx`, and keep the two table header rows and the bullet heading paragraph, since the builder reuses them.
