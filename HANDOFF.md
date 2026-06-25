# OrthoPlus — Architecture & Handoff

Orthopedic & physiotherapy clinic management system. Single-page Vue app on a Laravel JSON API. Now also a full Orthopedic **Hospital Management System (HMS)** layered on top.

---

## Latest Update — Hospital HMS, Branding & OPD (2026-06-24)

Large additive build on top of the clinic. **No existing clinic module was rewritten** — everything is new files/routes/columns, backward compatible.

### Hospital modules (in-patient HMS)
- **24 new tables** (migrations `2026_06_23_210001..210008`, guarded `if(!hasTable)`, MySQL+pgsql safe): wards, beds, admissions (`ip_number` IP-YYYY-NNN), bed_transfers, discharge_summaries, staff (`STF-001`), staff_shifts, leave_requests, opd_queue, opd_visits, surgeries, implants, implant_usage, pre_op_plans, preference_cards, op_orders, op_fittings, imaging_orders, imaging_studies, charge_master, ip_bills, global_periods, clinical_templates, cast_injection_log.
- **24 models** (`app/Models`), **17 `Api\` controllers** + `Hospital\HospitalReportController`. Admission/transfer/discharge flip bed status in transactions. IP-bill auto-computes room (bed daily_charge × whole nights — Carbon 3 `diffInDays()` returns FLOAT, use `startOfDay`) + implant charges.
- Routes: flat `/api/*` **and** a `/api/hospital/*` alias group (→ same `Api\` controllers + hospital report). Custom GET routes registered before `apiResource` wildcards.
- **HospitalSeeder**: 5 wards → 52 beds + 13 charge_master. **ClinicalTemplateSeeder**: 11 ortho templates. Both idempotent, in DatabaseSeeder.
- Frontend pages under `pages/Hospital/*` + the shared `HmsIcon.vue` (outline medical icon set replacing letter badges) + `HospitalLayout.vue` (sidebar/header use `var(--brand-sidebar/header)`, single logo → `/hospital/dashboard`).
- **7 print Blade views** (`resources/views/print/`): admission-card, ip-bill, discharge-summary, surgery-note, opd-token (80mm), pre-op-plan, implant-sticker. `?lang=en|ta|hi`.

### OPD flow
- **Registration** (`/hospital/opd-registration`): patient list with `display_op_number` (N-9 → N-9-1 revisit suffix), visit_count, **Last Visit** date, queue-status badge, actions Edit/Files/Print/Add-to-Queue (Add hidden once queued/completed).
- **Consulting** (`/hospital/opd-consulting`): 2-pane doctor screen. Reuses clinic VisitHistory, TreatmentTracker, ExerciseLibrary (`/exercises/prescribe`), PrescriptionForm, SOAPNotes, ROMTracker, OrthoTests, VASSlider; 3D `BodyMap3D` (three.js, gender model) + **pain description** textarea (10–15 words). `saveConsultation` updateOrCreate's the **linked** `clinical_record` (`opd_queue.clinical_record_id`) → re-save = edit, **no duplicate**. `GET …/consult` loads a completed consult for edit/print. Completed token → Edit/Print only (no Done). Queue add blocks same patient when active OR completed today.

### Branding / theme / print engine (DB-driven, cached, global)
- `settings` table (key/value-JSON/group) + `Setting` model (`Cache::rememberForever('app_settings_all')`, busts on write). Groups: `theme`/`brand`/`print`. Defaults in `config/branding.php`; helper `branding()` merges config⊕DB (try/catch-safe).
- `SettingController` GET/PUT `/api/settings/branding` (root). **Applied globally with zero runtime API**: `app.blade.php` server-renders `<style id="brand-theme">:root{…}</style>` (remaps Tailwind `--color-blue-*`→brand green, `--brand-sidebar/header`, font/radius, status vars) + `window.__BRAND__`.
- Settings tabs: **Appearance** (`AppearanceSettings.vue`, live preview = writes the same CSS vars to `:root`) + **Print Designer** (`PrintSettings.vue`, header builder + 4 styles + live preview). Print `layout.blade.php` reads `branding()` (logo, name/address/reg/gst/NABH, watermark, footer, margins, colors).
- Whole app already re-skinned **OrthoPlus green** (`#2E7D32/#4CAF50/#81C784`, bg `#F8FAF8`) via the `@theme` blue→green remap in `resources/css/app.css` — every legacy `bg-blue-*` util is green; `Logo.vue` SVG mark + `public/favicon.svg`.

### Files: real uploads + camera
- `patient_files` table + `PatientFile` model (`url` = relative `/storage/...`) + `PatientFileController` (multi-upload, **one request per file** to dodge `post_max_size`, extension whitelist not `mimes:`, 25 MB). `FileGallery.vue` (thumbnails, ImageLightbox zoom, camera capture + client compress, download, delete, permission props). PHP limits raised via `docker/laravel/uploads.ini` (mounted in compose + baked in Dockerfile). Ran `php artisan storage:link`.

### Patient register
- Phone = `country_code` (default `+91`, single combined field) + exactly-10-digit national (regex + UI). Duplicate = same **name+phone** (phone-unique dropped — families share phones). `PatientController@update` + `PUT /api/patients/{id}` (edit mode in `PatientForm`).

### Dashboard
- `Hospital/Dashboard.vue`: 8 themed stat cards, **registration-trend SVG chart** (daily/weekly/monthly toggle, y-axis, tooltip, animation — dep-free), Quick Actions, Today's Queue, Recent Patients. One cached call to `HospitalReportController@dashboard`.

### Verification
- `npm run build` clean throughout. Backend smoke-tested via curl (admit/transfer/discharge, OPD consult no-dup, file upload docx/xlsx/4MB-pdf, branding live apply, dashboard payload).

---

## Latest Update - Portal Split (2026-06-23)

Separate login portals were added for Clinic Management and Orthopedic HMS.

### Database/Auth
- New migration: `database/migrations/2026_06_23_220001_add_module_to_users_table.php`.
- `users.module` enum values: `clinic`, `hospital`, `both`; default is `clinic`.
- `POST /api/auth/login` now accepts `module` (`clinic` or `hospital`) and returns 403 when the user is not authorized for that portal.
- Auth payload now includes `user.module`; frontend stores it in `ortho_module` and `module`.
- Existing clinic demo users stay `clinic`; existing `root/root123` is seeded as `both`.
- Hospital demo users seeded by `OrthoSeeder`:
  - `hadmin/hadmin123` (`root`, `hospital`)
  - `surgeon/surg123` (`doctor`, `hospital`)
  - `nurse/nurse123` (`therapist`, `hospital`)
  - `reception/recep123` (`front_office`, `hospital`)
  - `hbilling/hbill123` (`billing`, `hospital`)
  - `hpharma/hph123` (`pharmacy`, `hospital`)

### Frontend Routes
- `/` is now `PortalSelect.vue` with Clinic and Hospital cards.
- `/login` redirects to `/clinic/login`.
- `/clinic/login` uses `LoginPage.vue` and sends `module=clinic`.
- `/hospital/login` uses `HospitalLoginPage.vue` and sends `module=hospital`.
- Clinic dashboard route is `/dashboard` (not `/`).
- Hospital routes are nested under `/hospital` and render through `resources/js/layouts/HospitalLayout.vue`.
- Main hospital paths: `/hospital/dashboard`, `/hospital/admissions`, `/hospital/beds`, `/hospital/opd`, `/hospital/surgery`, `/hospital/staff`, `/hospital/pharmacy`, `/hospital/billing`, `/hospital/reports`, `/hospital/settings`.
- Old top-level HMS paths (`/admissions`, `/beds`, `/opd`, `/surgeries`, `/staff`, `/ip-billing`, `/hospital-reports`, etc.) redirect to the new `/hospital/...` paths.
- OPD queue now supports inline patient registration: `resources/js/pages/OpdQueue.vue` has `+ New Patient`, opens `PatientForm`, and returns the newly created patient into the token flow.

### Verification Done
- `npm.cmd run build` passed. Use `npm.cmd` on Windows PowerShell because `npm.ps1` may be blocked by execution policy.
- Ran `docker exec laravel_app php artisan migrate --force`.
- Ran `docker exec laravel_app php artisan db:seed --class=OrthoSeeder --force`.
- Confirmed database modules for sample users: `root=both`, `doctor=clinic`, `hadmin/surgeon/nurse=hospital`.
- Added `require_once app/helpers.php` in `bootstrap/app.php` because Composer's generated autoload files in this environment did not yet include the custom helper file; this fixes runtime calls to `like_operator()` immediately.

---

## 1. Stack & Runtime

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8) |
| Frontend | Vue 3 (`<script setup>`) · Pinia · vue-router · vue-toastification · Tailwind CSS v4 |
| Build | Vite 7 (`@vitejs/plugin-vue` v6, `@tailwindcss/vite`, `laravel-vite-plugin`) |
| DB | MySQL 8 (single database `orthoplus` — Clinic + Hospital modules share it) |
| Calendar | FullCalendar 6 (daygrid/timegrid/list/interaction) |
| Infra | Docker — see below |

### Docker
| Container | Purpose | Port |
|---|---|---|
| `laravel_app` | PHP + Laravel (`php artisan serve`) | 8001 → 8000 |
| `mysql_server` | MySQL 8 (`root`/`showme`, db `orthoplus`) | 3306 |
| `phpmyadmin` | DB UI | 8080 |

- Project is **volume-mounted** into `laravel_app` (`.:/var/www/html`) — host edits are live, no `docker cp`.
- **No PHP on Windows host** — all artisan via `docker exec laravel_app php artisan <cmd>`.
- MySQL CLI: `docker exec mysql_server mysql -uroot -pshowme orthoplus -e "<sql>"`.
- Frontend build: `npm run build` (host). App served at `http://localhost:8001`.

⚠️ **Perf caveat:** `php artisan serve` is single-threaded, no opcache → API latency ~3-4s in dev. Production needs **php-fpm + opcache** for <200ms. Caching + indexes already in place; the bottleneck is the dev server, not queries.

---

## 2. Auth, Roles & Page Access

Lightweight **bearer-token** auth (no Sanctum/Passport).

- `users.api_token` (random 64). `POST /api/auth/login` (username **or** email + password) → `{token, user}`. `/auth/me`, `/auth/logout`.
- `ResolveUser` middleware (api group, **non-blocking**): resolves bearer token → `Auth::setUser()`. Lets `Auth::id()` work for `created_by` without forcing auth.
- `CheckRole` middleware alias `role:` → `->middleware('role:root')`. 401 if no user, 403 if lacking role. Only **`/api/settings/*`** is role-gated now (root). Pharmacy routes were **ungated** (matched the rest of the app, which is open) after a 401 regression.

### Roles (table `roles` + pivot `user_roles`, many-to-many)
6 roles seeded: `root, doctor, front_office, billing, pharmacy, therapist`. `User::hasRole()` (root passes all).

### Page access (the access-control model the frontend uses)
- `roles.page_access` JSON — per-role page list. `root = ['*']`.
- `users.page_access` JSON — optional **per-user override**.
- `User::pageAccess()` resolves: root role → `['*']`; else per-user override if set; else **union of roles' page_access**.
- Returned in `/auth/login` + `/auth/me` payload as `page_access`.

**Page keys:** `dashboard, patients, appointments, direct-doctor, inventory, pharmacy, invoices, treatments, settings`.

**Role → pages (default):**
| Role | Pages |
|---|---|
| root | `*` |
| doctor | dashboard, patients, appointments, direct-doctor, treatments |
| front_office | dashboard, patients, appointments |
| billing | dashboard, patients, invoices |
| pharmacy | pharmacy, inventory |
| therapist | dashboard, appointments, treatments |

**Frontend enforcement** (no backend page middleware — API is open):
- `stores/auth.js` `canAccess(page)` (`*` or includes; missing→permissive), `firstAccessiblePath()`.
- `router.js` — each route `meta.page`; guard: public ok · not authed → `/login` · `!canAccess` → `/unauthorized`.
- `App.vue` nav filters links by `canAccess`. `LoginPage` lands on first accessible page. `App.vue onMounted` calls `fetchMe()` so old sessions get `page_access`.
- `Settings > Users` form: pick Roles *, then Pages checklist (auto-seeded from roles, manually overridable per user).

**Demo logins:** `root/root123 · doctor/doc123 · frontoffice/fo123 · billing/bill123 · pharmacy/ph123 · therapist/th123`.

---

## 3. Database (single db `orthoplus`)

Core clinical (pre-existing): `patients` (op_number `N-78`), `patients_visits`, `clinical_records` (soap_notes/body_map/vas/rom/ortho_tests/outcome_measures JSON), `appointments`, `services`, `resources`, `treatment_catalog`, `treatments`, `exercises`, `exercise_prescriptions`, `prescriptions`, `invoices`, `packages`, `medicines` (+`medicines_stock`, `consumables`, `inventory_transactions`, `suppliers`), `inventory_logs`, `therapists`, `rooms`.

Added by the role/pharmacy/master-fix work: `roles`, `user_roles`, `soap_templates`; columns — `users.username/api_token/page_access`, `roles.page_access`, `therapists.phone/email`, `medicines.unit/strength/quantity/reorder_level/expiry_date/hsn_code`, `invoices.type(clinical|pharmacy)`, `prescriptions.services/estimated_total`, `invoices.patient_id` made NULLABLE (walk-in pharmacy).

### Indexes
`appointments(patient_id, scheduled_date)`, `patients(op_number unique, name(50), phone)`, `clinical_records(patient_id)`, `invoices(patient_id,status)+(status)`, `treatments(patient_id,status)`, `medicines FULLTEXT idx_med_search(name, generic_name)`.

### Seeders
- `OrthoSeeder` — roles(6, with page_access via migration), users(6), therapists(3), medicines(5), soap_templates(3). Idempotent.
- `IndianMedicinesSeeder` — ~520 ortho meds (10 categories, brand×strength + generic-maker variants), idempotent, chunked `insertOrIgnore(50)`. `node`-free; run via artisan.
- Run: `docker exec laravel_app php artisan db:seed --class=OrthoSeeder --force` (likewise `IndianMedicinesSeeder`).

---

## 4. API (routes/api.php, prefix `/api`)

Open unless noted. Auth: `/auth/{login,me,logout}`.
- Patients: `GET/POST /patients`, `/patients/search`, `/patients/{id}`, `/patients/{id}/visits`.
- Appointments: `GET /appointments/calendar`, `POST /appointments`, `PUT /appointments/{id}`.
- Clinical: `POST/GET/PUT /clinical-records[/{id}]`.
- Treatments: `GET /treatments`, `GET /treatments/catalog` (cached 60m), `POST /treatments/assign`, `PUT /treatments/{id}/complete`.
- Exercises: `GET /exercises`, `/exercises/categories`, `POST /exercises/prescribe`.
- Prescriptions: `GET/POST /prescriptions`, `GET /prescriptions/{id}/print`.
- Medicines: `GET /medicines/search` (**FULLTEXT** boolean prefix + LIKE fallback; returns name/generic/unit/strength/qty/sell_price/hsn_code/expiry_date).
- Invoices: `POST/PUT /invoices`, `GET /invoices/{id}/print`.
- Inventory, Reports (`/reports/dashboard` cached 120s, `/reports/therapist`).
- Lists: `GET /therapists` (active, cached), `/rooms`, `/packages`, `/soap-templates`.
- Pharmacy (open): `/pharmacy/patients/search`, `/pharmacy/patients/{id}/prescriptions`, `POST /pharmacy/invoices` (deducts stock + inventory_logs).
- Settings (`role:root`): apiResource `treatment-catalog, exercises, medicines, therapists, soap-templates, users`; `GET users/roles`.

---

## 5. Frontend (`resources/js`)

- Entry `app.js` → Vue + Pinia + router + Toast. Shell `views/app.blade.php` (`<div id="app">`, viewport meta).
- `bootstrap.js` — axios bearer interceptor (`ortho_token`), 401 → drop token + redirect `/login`.
- `stores/auth.js` — token/user in localStorage, `hasRole`, `canAccess`, `firstAccessiblePath`, login/me/logout.
- `router.js` — **all routes lazy** (`() => import`), `meta.page` guard.
- Pages: `Dashboard, Patients, PatientDetail, Appointments, Inventory, PharmacyBilling, Settings, DirectDoctorMode, LoginPage, Unauthorized`.
- Components: `PatientSearch, PatientForm, VisitHistory, AppointmentCalendar, BodyMap, VASSlider, ROMTracker, OrthoTests, OutcomeMeasures, SOAPNotes, TreatmentTracker, ExerciseLibrary, PrescriptionForm, InvoiceForm, InventoryTable, Dashboard`; settings forms in `components/settings/` (TreatmentCatalogForm+SOAP, ExerciseForm, MedicineForm, TherapistForm, UserForm).
- Print (Blade) `/print/{type}/{id}?lang=en|ta|hi`: patient_record, soap_note, exercise_sheet, prescription, invoice (pharmacy bills show Generic/HSN/Expiry + Walk-in), consent_form, layout. Lang `resources/lang/{en,ta,hi}/print.php`.

### Responsive
Top navbar (not sidebar). White sticky header `min-h-[3.5rem]`. Nav `hidden sm:flex flex-nowrap justify-center overflow-x-auto scrollbar-hide` (single row, scrolls, never wraps), filtered by `canAccess`. `<sm`: hamburger → left drawer (teleport) + fixed bottom nav. Modals fullscreen `<md`. Calendar 3 tiers (<640 listWeek, <1024 timeGridThreeDay, else timeGridWeek + resize swap). `app.css` base: `touch-action:manipulation`, inputs 16px <768 (no iOS zoom), 44px tap targets, `.scrollbar-hide`, `.table-responsive`, print hides chrome.

**Breakpoints (Tailwind v4 — no JS config, defined in `app.css @theme`):** `sm/md/lg/xl` keep TW defaults (640/768/1024/1280); added `--breakpoint-xs:320px`, `--breakpoint-2xl:1440px`, `--breakpoint-3xl:1920px`. NOT global `overflow-x:hidden` on html/body — breaks the sticky header (v4 spec computes cross-axis overflow to `auto`); fix overflow at source instead.

**Page patterns (audited 320/768/1024/1440):**
- Tables → `overflow-x-auto` + hide low-priority cols `<sm` (InventoryTable), or desktop table / mobile stacked cards (PharmacyBilling).
- 12-col line-item rows (InvoiceForm, PrescriptionForm services) → stack on mobile via `col-span-12 sm:col-span-N`, header `hidden sm:grid`.
- Multi-col grids → `grid-cols-1 sm:grid-cols-3` (TreatmentTracker kanban), `grid-cols-3 sm:grid-cols-6` (TherapistForm schedule).
- Tab strips → `flex overflow-x-auto scrollbar-hide` + `flex-shrink-0 whitespace-nowrap` (PatientDetail).

**BodyMap** (`components/BodyMap.vue`): full-body SVG `viewBox 0 0 200 480`, `max-w-[300px] sm:max-w-[340px]`. Detailed anatomy (fingers/toes/spine segments), Front/Back toggle (spine back-only), tap-to-mark per `data-part` (`getBBox` center), tap marker to remove, breathing torso (`transform-box:fill-box`) + pulse markers. Keeps `v-model` contract (`modelValue`/`update:modelValue`, parents `v-model="record.body_map"`); marker shape superset of old `{x,y,severity,label}` (+`part`) → old records + print still render.

### Performance
Vite `manualChunks`: `vendor`(vue/router/pinia/axios) + `calendar`(fullcalendar) → **app.js 184KB→29KB** (calendar lazy). Route lazy-loading. Dashboard skeleton. DB indexes + `Cache::remember` (dashboard 120s, treatment_catalog/therapists 60m).

---

## 6. Pharmacy & Tamil/Hindi note

This repo (`OrthoPlus`) is **Laravel/Vue**. A separate sibling project **`AyuPlus/AyPlus-SAS`** (Node/Express/Sequelize/EJS, db `ayplus`) has its own `pharma_` Pharmacy Pro module — do not confuse the two. OrthoPlus pharmacy = `PharmacyBilling.vue` + `/api/pharmacy/*` + `medicines` table.

---

## 7. Known gotchas / pending

- **Tailwind v4:** scoped `<style>` using `@apply` needs `@reference "tailwindcss";` first line. Breakpoints live in `app.css @theme` (`--breakpoint-*`), not a `tailwind.config.js`.
- **Appointments:** schema uses `scheduled_date`+`scheduled_time`+`duration_minutes` (NOT `start_at`/`end_at`). Calendar event `start` MUST format date `Y-m-d` (Carbon date cast else invalid ISO → events silently dropped). Conflict check = true time-range overlap via `ADDTIME`.
- **Auth is frontend-enforced** (router guard + nav). API routes mostly open. For real backend enforcement → add Sanctum + per-route gates (bigger job).
- Editing a user's roles/pages → that user must **re-login** (or next-load `fetchMe`) to pick up access.
- Pre-existing unrelated log errors: `visits` table missing (Patient `visits()` relation), patient `photo` column too short for base64.
- `update()` on appointments has no conflict check (only `store`).

### HMS-era gotchas (2026-06-24)
- **Single DB `orthoplus` (resolved):** both `.env DB_DATABASE` and `docker-compose.yaml` now use **`orthoplus`** — CLI (`php artisan migrate`/`tinker`) and the served app hit the **same** DB. The old `clinic` DB still exists in MySQL as a legacy leftover but is **unused** (safe to drop once confirmed). Clinic + Hospital modules share `orthoplus`; the `module` value on users (`clinic`/`hospital`) is an app feature, NOT a database name — do not rename it. (MySQL has no `ADD COLUMN IF NOT EXISTS` — that's MariaDB; migrations guard with `Schema::hasColumn`.)
- **Theme is DB-driven:** colors come from `settings` (`branding()`), injected as CSS vars in `app.blade.php`. Don't hardcode hex in components — use `bg-blue-*` (remapped to brand green) or `var(--brand-*)`. Settings → Appearance changes apply globally on save (live preview writes the same vars to `:root`).
- **Form components want ARRAYS:** `OrthoTests` / `ROMTracker` / `ExerciseLibrary` / `BodyMap3D` `v-model` must be an array (they spread `[...modelValue]`). Passing an object (`{}`) throws "c is not iterable" (minifier may mislabel the stack as another component). `clinical_records.ortho_tests/rom/body_map` are array casts — guard with `Array.isArray` when loading saved JSON.
- **File uploads:** upload **one file per request** (PHP `post_max_size`). Allowed types validated by **extension** (not `mimes:`, which sniffs docx/xlsx as zip and rejects). Limits set in `docker/laravel/uploads.ini` (25M) — survives rebuild via compose mount + Dockerfile COPY.
- **Stale-bundle crashes:** a `TypeError` whose stack points at `build/assets/<Name>-<hash>.js` with an OLD hash = cached bundle. Rebuild + hard-refresh before debugging.

---

## 8. Run cheatsheet
```bash
# migrate + seed  (single DB 'orthoplus' — CLI and the served app hit the same one)
docker exec laravel_app php artisan migrate --force
docker exec laravel_app php artisan db:seed --force            # OrthoSeeder + TreatmentCatalog + Exercise + IndianMedicines + ClinicalTemplate + Hospital
docker exec laravel_app php artisan storage:link               # patient file uploads
docker exec laravel_app php artisan cache:clear                # bust settings/dashboard caches after changes
# direct DB:
docker exec mysql_server mysql -uroot -pshowme orthoplus -e "SHOW TABLES;"
# build frontend
npm run build
# app: http://localhost:8001   (clinic root/root123 · hospital hadmin/hadmin123)
```
