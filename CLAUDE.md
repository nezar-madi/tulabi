# CLAUDE.md — tulabi (طلابي)

Handoff notes for AI coding sessions. Read this first, then `docs/spec.md` (the approved spec, in Arabic; it is the source of truth).

## What this is

A platform for independent instructors in Libya (tulabi.ly). Each instructor has a public page `tulabi.ly/{slug}` with their classes; students sign up by phone and ask to join a class; the instructor accepts them and records payments by hand (DPay / MyPay later). An admin approves instructors.

- **Sister product of rqmana** (`C:\laragon\www\center-saas`, multi-center SaaS for training centers). Same stack and design system, but **one database and no tenancy**: no `TenantManager`, `IdentifyTenant`, per-center databases or panel host. Copy pieces from rqmana when needed, never its tenancy.
- **Stack:** Laravel 13, Inertia v2, Vue 3 `<script setup>`, Vuetify 4, vue-i18n, Ziggy, `giggsey/libphonenumber-for-php`. PHPUnit (not Pest), sqlite in memory for tests.
- **Phase:** 0 (foundation) is done: theme, RTL, fonts, home page, clock and phone helpers, tests, CI. Phase 1 (accounts) waits for the owner. The phases are listed in `docs/spec.md` §8.
- **Work is local only** for now: no server, no staging, no GitHub remote. Commit locally; never push without asking.

## Decisions from the spec (don't undo without asking)

- **Libya only:** Arabic only (RTL), currency **LYD** (`App\Support\Currency`), time zone **Africa/Tripoli**, Libyan mobile numbers only.
- **Sign-in is phone + password for everyone**; email is optional. **One account per phone number.** The instructor role is added to an existing account after admin approval; an instructor can also be a student of another instructor.
- Instructor page is a **path** (`/{slug}`), not a subdomain. Slugs: lowercase English letters, digits and dashes, with reserved words (admin, login, ...).
- Instructors **apply**, the admin accepts or rejects (throughout the beta).
- **Forgotten password:** the instructor issues a reset link for their students, the admin for instructors; the link is sent with a WhatsApp button (wa.me). Anyone with an email can reset by email. Links are single use and expire after one hour. **No SMS** in this version.
- Classes are in person (address) or online (meeting link **shown only to accepted students**).
- **Payments are voided, never deleted** (with a reason); amount and date are locked after recording; a voided receipt shows «ملغى». Receipt numbers `TL-YYYYMMDD-NNNN` from a per-instructor counter that never goes backwards. Canceled classes owe nothing and take no payments.
- Class capacity is a warning only.
- Admin accounts are separate and use a TOTP second factor (as rqmana's panel).
- Abuse protection without SMS: rate limits per IP and per phone, reCAPTCHA on sign-up and instructor application, and the instructor accepts each student.
- Account deletion: the account goes; its payments stay without name or phone (the instructor's books stay correct).
- Activity log kept 14 days (as rqmana). Certificates, e-payment, a marketplace, attendance, SMS and a mobile app are out of scope (spec §7).
- Open: the logo (placeholder text logo «طلابي» in `resources/js/Components/BrandMark.vue`), the list of Libyan cities.

## Conventions carried over from rqmana

- **Dates:** timestamps are stored in UTC; `config('app.timezone')` stays `UTC`. For "today", "this month" or a date a person typed, use `App\Support\TulabiClock` (Africa/Tripoli, from `config/tulabi.php`), never `now()->toDateString()`. A typed `Y-m-d` means local midnight (`TulabiClock::storeDate()`). Filter by date with `startOfDayUtc()`/`endOfDayUtc()`, not `whereDate`.
- **Phones:** normalise every phone with `App\Support\LibyanPhone::normalize()` before saving or looking up; store E.164 (`+2189XXXXXXXX`). `forWhatsApp()` gives the wa.me digits, `display()` the local form (091-2345678). Only mobiles are accepted (landlines are rejected).
- **Permissions:** every route checks access on the server. Put the check in the FormRequest `authorize()` (so 403 comes before validation) and keep the controller check as a second layer. An instructor only ever sees their own classes and their students; a student only their own enrollments. Write isolation tests for each.
- **User model:** `phone`, `password` and `status` (and any role/type column) are **not mass-assignable**. Set them with `forceFill()`/`forceCreate()` and explicit values only.
- **HTML from instructors** (class descriptions) must go through a `SafeHtml` sanitiser (copy `App\Support\SafeHtml` and `tests/Unit/SafeHtmlTest.php` from rqmana when the first rich text field arrives); it is rendered with `v-html`.
- **Text:** all UI text is Arabic, in `resources/js/locales/ar.json` (frontend, checked by `messages.spec.js`: write `@ { } |` as `{'@'}` etc.) and `lang/ar/*.php` (backend). Keep it short and plain.
- **Icons:** Tabler icons (`icon="tabler:name"`) must be registered in `resources/js/icons/tablerChrome.js`, or they render blank; `tablerChrome.spec.js` fails on an unregistered name.
- **Design system:** rqmana's, recoloured green. Primary `#0A7D4F`, hover/dark `#075C3A`, yellow accent `#FFC24A` (marks and underlines only, never text on white). Tokens in `resources/css/tokens.css` keep the `--rq-` prefix and classes keep `rq-` (`rq-btn`, `rq-card`, `rq-eyebrow`, `rq-badge`, `rq-icon-tile`, ...) so rqmana components can be copied as they are; brand shades are `--rq-brand-*` (`--rq-green-*` is the success colour). Vuetify themes in `resources/js/config/vuetifyTheme.js` (light and dark). Fonts: Alexandria (display) and Tajawal (body) from fonts.bunny.net. Square-ish 4px buttons, flat, hairlines instead of shadows.
- Pages use `PublicLayout.vue` unless they set their own `layout`.
- Formatting: `vendor/bin/pint` on changed PHP files.

## Running locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build                      # or: npm run dev (Vite on 127.0.0.1:5174)
php artisan serve --port=8010      # http://127.0.0.1:8010
```

`.env.example` points at `http://tulabi.test` for Laragon; the virtual host appears after Laragon reloads its sites. The local database is sqlite (`database/database.sqlite`).

## Tests

- `php artisan test` (sqlite in memory, `phpunit.xml` forces it). `tests/TestCase.php` calls `withoutVite()` so pages render without a build.
- `npm run test:js` (vitest): translation strings compile, every Tabler icon used is registered.
- CI (`.github/workflows/ci.yml`, mirrors rqmana): PHP tests on 8.3 and 8.5, then `npm ci`, `test:js` and the build on 8.5. It runs once a GitHub remote exists.
