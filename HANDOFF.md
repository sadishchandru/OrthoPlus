# OrthoPlus — Architecture & Handoff

Orthopedic & physiotherapy clinic management system. Single-page Vue app on a Laravel JSON API.

---

## 1. Stack & Runtime

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8) |
| Frontend | Vue 3 (`<script setup>`) · Pinia · vue-router · vue-toastification · Tailwind CSS v4 |
| Build | Vite 7 (`@vitejs/plugin-vue` v6, `@tailwindcss/vite`, `laravel-vite-plugin`) |
| DB | MySQL 8 (database `clinic`) |
| Calendar | FullCalendar 6 (daygrid/timegrid/list/interaction) |
| Infra | Docker — see below |

### Docker
| Container | Purpose | Port |
|---|---|---|
| `laravel_app` | PHP + Laravel (`php artisan serve`) | 8001 → 8000 |
| `mysql_server` | MySQL 8 (`root`/`showme`, db `clinic`) | 3306 |
| `phpmyadmin` | DB UI | 8080 |

- Project is **volume-mounted** into `laravel_app` (`.:/var/www/html`) — host edits are live, no `docker cp`.
- **No PHP on Windows host** — all artisan via `docker exec laravel_app php artisan <cmd>`.
- MySQL CLI: `docker exec mysql_server mysql -uroot -pshowme clinic -e "<sql>"`.
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

## 3. Database (db `clinic`)

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
- Pre-existing unrelated log errors: `clinic.visits` table missing (Patient `visits()` relation), patient `photo` column too short for base64.
- `update()` on appointments has no conflict check (only `store`).

---

## 8. Run cheatsheet
```bash
# migrate + seed
docker exec laravel_app php artisan migrate --force
docker exec laravel_app php artisan db:seed --class=OrthoSeeder --force
docker exec laravel_app php artisan db:seed --class=IndianMedicinesSeeder --force
# build frontend
npm run build
# app: http://localhost:8001   (login root/root123)
```
