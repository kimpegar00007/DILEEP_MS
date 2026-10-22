# Architecture: pages, APIs, includes, JS

All paths are relative to the repo root. "Guard" is the access check at the top of the file.

## Core plumbing

| File | Role |
|---|---|
| `config/database.php` | Parses `.env` by hand (KEY=VALUE, quotes, `export`), defines `DB_HOST/PORT/NAME/USER/PASS/CHARSET/SOCKET`. Accepts `DB_NAME` or `DB_DATABASE`, `DB_USER` or `DB_USERNAME`, `DB_PASS` or `DB_PASSWORD`. `Database::getInstance()->getConnection()` returns a PDO singleton that tries socket, then the host, then localhost/127.0.0.1/hostname. Dies with diagnostics when `APP_DEBUG`/`APP_ENV=local`. |
| `includes/Auth.php` | Login/logout (bcrypt `password_verify`), session keys `user_id, username, role, province, full_name`; `hasRole`, `requireLogin` (+ maintenance-mode redirect to `maintenance.php` for non-admins), `requireRole` (redirects to `unauthorized.php`, which does not exist), `isSuperAdmin`, `isRegionalDirector`, `isCrossProvince`, `hasProvinceAccess`, `getProvince` (lazily refreshes legacy sessions), `changePassword`, `isMaintenanceModeEnabled`, office-label helpers, `logActivity`. |
| `includes/navbar.php` | Top bar: brand, notification bell (`notification-bell.php`), user menu (profile, change password, logout). |
| `includes/sidebar.php` | Nav. Everyone: Dashboard, Beneficiaries & Proponents (unified), Reports, Fieldwork Schedule, About. `hasRole('admin')`: Activity Logs, Settings (section titled "Regional Director" for that role). `isSuperAdmin()`: System Admin, which links on to Users, Org Chart and Reset Records. Active item = `$currentPage`. |
| `includes/shared-styles.php` | One big `<style>` block: DOLE palette tokens `--dole-primary #1B7A3D`, `--dole-secondary #145A2C`, `--dole-accent #D4A017`, info `#1E6BB8`, radius 10px, Inter font; cards, tables, sidebar, forms, print rules. |
| `includes/ux-utilities.php` | `<script>` defining `window.DILP`: `toast.show(type,title,msg)`, `loading.show/hide/inline`, `validation.init(form)`, password strength, `session` idle timeout (warns, then redirects to `logout.php?timeout=1`), guided `tour`, `tooltip.initAll`, `export.toCSV/toExcel/toPDF(tableSelector, name)`, `errors.handle/handleFetch`, keyboard shortcuts. |
| `includes/notification-{bell,styles,script}.php` | Bell UI; the script polls `api/get-notifications.php` on load and every 5 min. |
| `includes/footer.php` | Footer. |
| `includes/cqpr-report-template.php` | Builds the CQPR (Consolidated Quarterly Progress Report, DOLE template Rev. 7, image in `docs/`). Included by `reports.php`; uses `$dateFrom/$dateTo/$provinceFilter` from the including scope plus `$_GET['status']` and `regional_office`. Proponents become type `G` rows, beneficiaries type `I` rows. |

## Page controllers (root)

| Page | Guard | What it does |
|---|---|---|
| `login.php` | none | Login form → `Auth::login`, redirects to `index.php`. |
| `logout.php` | none | `Auth::logout`, back to login. |
| `index.php` | login | Dashboard: stat cards, status overview, Chart.js charts (municipality, district, worker type, funding source, category, 12-month trends), Leaflet map (blue = beneficiaries, green = proponents), overdue liquidations, upcoming fieldwork. Filters (province for cross-province roles, year, approved-only) re-query `api/dashboard-stats.php` without reload. Calls `FieldworkSchedule::autoUpdateStatuses()`. |
| `unified-beneficiaries-proponents.php` | login | Main list page: tabs for beneficiaries and proponents with shared search plus filters (province, municipality, barangay, status, type, district, category); DataTables. The sidebar "Beneficiaries & Proponents" link. |
| `beneficiaries.php`, `proponents.php` | login | Older single-type list pages (still linked from forms and redirects such as `?success=created`). |
| `beneficiary-form.php` | admin, encoder | Add/Edit (`?id=`). Province select (locked for provincial users), PSGC cascading municipality/barangay, geocode button, searchable proponent combobox (`api/get-proponents.php`), "create new proponent" detour and restore, type checkboxes, autosave (`DILPAutoSave`, key `dilp_beneficiary_form`). |
| `proponent-form.php` | admin, encoder | Add/Edit. Dynamic association rows (`association_names[]/addresses[]` → `saveAssociations`, which deletes and reinserts), beneficiary-name list sized to `total_beneficiaries`, cheque/OR fields, all pipeline dates, Returns panel (`api/proponent-returns.php`). Supports `?return=beneficiary-form`. |
| `beneficiary-view.php`, `proponent-view.php` | login | Read-only detail pages (proponent view lists associations, linked beneficiaries and returns). |
| `beneficiary-delete.php`, `proponent-delete.php` | admin | GET shows confirmation, POST deletes (province-scoped). |
| `reports.php` | login | Report builder: type (beneficiaries / proponents / CQPR), date range, "status" select that is really a date-field selector (or `source_of_funds`), municipality/district, province. Output table → DataTables plus DILP.export CSV/Excel/PDF/print. |
| `fieldwork-schedule.php` | login | FullCalendar (PH holidays computed per visible year, Holy Week from Easter) plus a table, 5 stat cards that filter the table (incl. Overdue), and the Smart Activity Reminder modal for editors. All CRUD goes through `api/fieldwork-schedule-crud.php`; saves refresh in place (no page reload). Row actions use `data-action` + one delegated click handler (no titles in inline JS). `$canEdit` = `hasRole(['admin','encoder'])`. |
| `about.php` | login | Program info plus province-tabbed org chart from `api/org-chart.php`. |
| `org-chart-admin.php` | super_admin | Edit org chart tiers (0 Regional Director, 1 Field Office Head, 2 DILEEP Focal, 3 Staff; up to 5 per tier) per province. |
| `users.php` | super_admin (`requireRole('super_admin')`) | Create, update, deactivate users, set role and province. Contains admin-scoped code paths that are unreachable today. |
| `activity-logs.php` | admin | Activity log viewer (latest 10,000 rows, DataTables 50 per page). |
| `settings.php` | admin | Data export (CSV per table and date range), import (CSV/XLSX → `api/settings-import.php`, with a format guide modal), full SQL backup download, Google Drive backup (OAuth connect/disconnect/backup). |
| `system-admin.php` | super_admin | System stats, DB size (hard-codes schema name `dilp_monitoring`), maintenance-mode toggle (`api/toggle-maintenance.php`), links to Reset Records and Org Chart. |
| `reset-records.php` | super_admin | Wipes selected data tables (destructive). |
| `profile.php`, `change-password.php` | login | Self-service name/email and password (min length enforced). |
| `maintenance.php` | none | Shown to non-admins while `system_settings.maintenance_mode = '1'`. |
| `migrate-database-online.php` | **none** | Large browser-run migrator with backup and validation. Ops tool; must not stay publicly reachable. |
| `test_login.php`, `debug/*`, `fixes/*.php` | **none** | Diagnostics that dump schema and users. Dev only. |

## API endpoints (`api/`)

| Endpoint | Auth | Contract |
|---|---|---|
| `dashboard-stats.php` | logged in (401 JSON) | GET `province`, `year`, `approved_only` → JSON with stats, distributions and trends for both entities. Has its own province logic (`provinceCondition()`). |
| `get-entity-details.php` | login | GET type and id → detail JSON for dashboard/map modals (uses models, so province-scoped). |
| `get-proponents.php` | logged in | GET `search` → `[{id,label}]` for the beneficiary combobox; province-scoped. |
| `get-locations.php` | **none** | GET `action=provinces` (3 hard-coded), `cities&province_code=`, `barangays&city_code=`; reads PSGC JSON; 24h cache header. |
| `geocode.php` | **none** | GET barangay, municipality, province → Nominatim lookup bounded to the province box; `Access-Control-Allow-Origin: *`. |
| `fieldwork-schedule-crud.php` | login | `action=list,get,calendar,create,update,update_status,delete,statistics,overdue,users`; writes (POST) and `overdue` need raw role in admin/encoder/super_admin/regional_director. `list` takes `status=overdue`. Errors use the model's `getLastErrorStatus()` (404/422/500) and never echo DB text. |
| `includes/Mailer.php`, `includes/FieldworkNotifier.php`, `cron/fieldwork-notifications.php` | n/a | Fieldwork email alerts. `FieldworkSchedule` create/update/updateStatus queue rows into `fieldwork_notifications` (outbox + unique-key dedupe) and the notifier sends them after the response; the cron script (every 10 min on the host, CLI only) queues day-before/day-of reminders and overdue notices (day 1/3/7, creator copied from day 3, ignores >14 days) and sends the outbox. Mail settings are `MAIL_*` in `.env`; `FIELDWORK_EMAILS=false` switches it off. Full guide: `docs/FIELDWORK_EMAIL_NOTIFICATIONS.md`. |
| `get-notifications.php` | login | Overdue and upcoming (14-day) liquidations, monitoring due, fieldwork today/upcoming/overdue → `{success, notifications[], count}`. |
| `proponent-returns.php` | logged in | POST adds a return (proponent_id, return_date, reason); DELETE `?id=` removes one. |
| `org-chart.php` | login | GET (optional `province`) → nodes; POST `action=add_person/update_person/delete_person` (super_admin). |
| `cqpr-report.php` | logged in | Older JSON CQPR. **Not referenced by any page**; superseded by `includes/cqpr-report-template.php`. |
| `settings-backup.php` | admin | `?action=download` streams a full SQL dump of every table. |
| `settings-export.php` | admin | `?table=beneficiaries|proponents&date_from&date_to` → CSV. |
| `settings-import.php` | admin | POST file + table → inserts rows. |
| `settings-gdrive-auth.php` | admin | `action=connect|callback|disconnect`; tokens kept in `system_settings` (`gdrive_access_token`, `gdrive_refresh_token`, `gdrive_token_expiry`, `gdrive_user_email`). Default redirect URI assumes `/dilp-system/` path; set `GOOGLE_REDIRECT_URI` in prod. |
| `settings-gdrive-backup.php` | admin | Refreshes the token and uploads an SQL dump to Drive. |
| `toggle-maintenance.php` | raw role admin/super_admin | POST `value=0|1` → upserts `maintenance_mode`. |

## Front-end JS modules

- `assets/js/location-autofill.js` → `window.DILPLocation`: helpers for the cascading province →
  municipality → barangay selects, status messages, restoring saved values.
- `assets/js/form-autosave.js` → `DILPAutoSave.init({formSelector, storageKey, debounceMs})`: saves
  non-hidden fields to localStorage per new record or per edit id, restores them only into an empty form,
  clears them on submit.
- Inline `<script>` blocks in forms handle the PSGC loading, geocoding, the proponent combobox,
  the detour snapshot (sessionStorage) and dynamic rows. In the dashboard they handle the charts, map and filter refresh.

## Key flows

1. **Create beneficiary**: form POST → normalize checkbox types → validate (required fields,
   province in `Auth::PROVINCES`, amount > 0, proponent required, coordinates inside the province box,
   lat and lng together) → duplicate check → `Beneficiary::create` (province from session or validated
   input) → activity log → redirect `beneficiaries.php?success=created`.
2. **Create proponent**: same shape, plus the names JSON, male + female ≤ total, names count = total,
   PHP-computed `liquidation_deadline`, then `saveAssociations`; redirect, or return to the beneficiary form.
3. **Liquidation monitoring**: `liquidation_deadline < today AND date_liquidated IS NULL` → dashboard
   "overdue" list and the bell notifications.
4. **Fieldwork**: `computeStatus()` gives `ongoing` while today is within the dates, else `pending`.
   `autoUpdateStatuses()` (once per request, fieldwork page/API) only moves pending→ongoing; nothing is
   auto-marked `missed`. A past-due pending/ongoing row is **overdue** (`is_overdue`, `getOverdueActivities()`)
   and the Smart Activity Reminder asks an editor to mark it Completed/Missed or reschedule it; the bell's
   "Overdue Fieldwork" uses the same rule. A manual status change sets `manual_override=1` (keeps a manual
   `pending` from turning `ongoing`); an edit that moves the dates with the old status re-derives it.
   "Today" is PHP's date in Asia/Manila, passed into SQL (not `CURDATE()`).
5. **Maintenance**: super_admin/admin toggles it; `requireLogin()` sends everyone below admin to `maintenance.php`.
