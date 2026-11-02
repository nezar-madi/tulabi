# CLAUDE.md — tulabi (طلابي)

Handoff notes for AI coding sessions. Read this first, then `docs/spec.md` (the approved spec, in Arabic; it is the source of truth).

## What this is

A platform for independent instructors in Libya (tulabi.ly). Each instructor has a public page `tulabi.ly/{slug}` with their classes; students sign up by phone and ask to join a class; the instructor accepts them and records payments by hand (DPay / MyPay later). An admin approves instructors.

- **Sister product of rqmana** (`C:\laragon\www\center-saas`, multi-center SaaS for training centers). Same stack and design system, but **one database and no tenancy**: no `TenantManager`, `IdentifyTenant`, per-center databases or panel host. Copy pieces from rqmana when needed, never its tenancy.
- **Stack:** Laravel 13, Inertia v2, Vue 3 `<script setup>`, Vuetify 4, vue-i18n, Ziggy, `giggsey/libphonenumber-for-php`. PHPUnit (not Pest), sqlite in memory for tests.
- **Phase:** 0 (foundation), 1 (accounts), 2 (instructor page and classes), 3 (join requests, student side) and 4 (payments and receipts) are done. Phase 5 (before launch: privacy and terms, account deletion, security review, browser tests) waits for the owner. The phases are listed in `docs/spec.md` §8.
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

## Accounts (phase 1)

- **Guards:** `web` = `User` (phone + password), `admin` = `Admin` (email + password + TOTP, routes in `routes/admin.php` under `/admin`, names `admin.*`). User routes use `auth:web` / `guest:web` explicitly and code reads `$request->user('web')`; an admin session never counts on the user side. Logging out of the admin invalidates the whole session (the user side too).
- **Sign-up** (`/register`): name, phone, password, optional email; signs in at once. **«انضم كمدرّس»** (`/join`): a visitor makes the account and the application in one form; someone signed in only adds specialty, city, bio. One pending application at a time; after a rejection they may apply again. reCAPTCHA on both visitor forms (`App\Support\Recaptcha`, skipped when no keys are set outside production).
- **Instructor = a row in `instructors`** (`User::isInstructor()`), made by `App\Support\InstructorApplications::accept()`; `slug` stays null until phase 2. Accepting shows the admin a WhatsApp button with a welcome message (`App\Support\WhatsApp`, wa.me links only; tulabi sends nothing itself).
- **Suspending** is on the account (`users.status`); `EnsureAccountIsActive` signs a suspended account out on its next request, and login says it is suspended only after the right password. `instructors.status` exists for later (switching teaching off) and is not set from the admin yet.
- **Password reset links** (`App\Support\PasswordResetLinks`, table `password_reset_links`): only the token's sha256 is stored, one hour, single use, a new link cancels older unused ones, and a suspended account's link does not work. Made by the admin (students and instructors lists), by email from «نسيت كلمة المرور» (only when the account has an email; the answer is the same for every phone), and later by instructors for their students (`ISSUER_INSTRUCTOR`). Laravel's own password broker and `password_reset_tokens` table are not used.
- **Rate limits** (`AppServiceProvider`): login 5 per phone+IP and 30 per IP per 10 minutes (in the SessionControllers), sign-up and join 10 per IP per hour, forgot-password 3 per phone and 10 per IP per hour.
- **Admins:** `php artisan admin:create {email}` (asks the password, 12+ characters); the authenticator is set up at first login (`EnsureAdminTwoFactor`). `php artisan admin:two-factor-reset {email}` for a lost phone. The admin 2FA code and its Vue pages (`Pages/Admin/TwoFactor`) are rqmana's panel code with names changed; their Arabic text is still written in the pages, as in rqmana. `Setting::APPLICATIONS_NOTIFY_EMAIL` (admin «الإعدادات») gets an email per new application.
- Local data: `php artisan migrate:fresh --seed` makes sample accounts (password `password`) and, locally only, `admin@tulabi.test` (sets up its authenticator at first login).
- The skeleton's users migration was edited in place (phone, status; no `password_reset_tokens`) because nothing has been deployed yet. From the first deploy on, never edit an old migration.

## Instructor page and classes (phase 2)

- **Instructor area** `/teach` (`EnsureInstructor`, `TeachLayout.vue`): الرئيسية (setup checklist, counts, coming classes), فصولي, صفحتي. Every `{class}` is checked twice: the FormRequest (`Requests\Teach\InstructorRequest::authorize()`, 403) and the controller (`FindsOwnClass::ownClass()`, 404). Queries start from `$instructor->classes()`. `tests/Feature/Teach/ClassTest.php` holds the isolation tests; add one for every new class route.
- **Public pages** `routes/pages.php`, loaded last: `/{slug}` (`instructors.show`) and `/{slug}/classes/{id}` (`classes.show`). A page is public when the slug is set, `instructors.status` is active and the account is active (`Instructor::scopePublic`). Visitors see published and closed classes that have not ended; the owner also sees their drafts (with a banner); canceled classes are seen by nobody. **`meeting_url` never goes to a public page** (`ClassPresenter::forPublic`); it is for accepted students (phase 3).
- **Slugs** (`App\Support\InstructorSlug`): lowercase English letters, digits and single dashes, starting with a letter, 3-40 characters, chosen by the instructor in «صفحتي» (null until then; publishing a class needs it, and it cannot be cleared while classes are public). The route pattern excludes `RESERVED`; **add every new top-level path to `RESERVED`** (`PublicPagesTest` fails otherwise).
- **Classes** = model `TeachingClass`, table `classes` (`Class` is reserved in PHP; Vue props carrying a class are named `item`, since `class` is not a usable prop name). Status changes only through `App\Support\ClassLifecycle`: draft → published ⇄ closed; any → canceled (final, read-only, optional reason); only drafts are deleted (soft delete). Class dates are DATE columns (calendar days in Libya, no time zone) and days are Carbon weekday numbers (0 = Sunday), shown Saturday first. Price is `decimal(12,3)` in LYD (`currency_code` stored per class). Seats are a warning only.
- **Descriptions** go through `App\Support\SafeHtml::description()` (copied from rqmana with `tests/Unit/SafeHtmlTest.php`) and are edited with rqmana's `FormPlainRichText` (tiptap).
- **Photo** (`App\Support\InstructorPhoto`): decoded with GD and saved again as a 512px square JPEG on the `public` disk (`instructors/{id}/`), the old file deleted; served as `/storage/...` (run `php artisan storage:link`). Links (`App\Support\ProfileLinks`): up to 5 http(s) URLs, icon chosen from the host.
- The «اطلب الانضمام» button on a class page is shown disabled until phase 3.

## Join requests and the student side (phase 3)

- **Enrollments** (`Enrollment`, table `enrollments`): **one row per student and class** (unique), changed only by `App\Support\Enrollments` with conditional updates: requested → accepted | rejected; accepted → canceled (the instructor removes the student, optional reason); requested | accepted → withdrawn (the student); rejected / withdrawn / canceled → requested again on the same row. Nothing on the model is mass-assignable.
- **Fee:** copied from the class price at the first acceptance and never changed by a later price edit; a student accepted again keeps the first fee (rqmana's "re-enrolling continues the original fee"). Payments arrive in phase 4.
- **A canceled class** shows its open places as «ملغى» without changing the rows (`Enrollment::displayStatus()`, like rqmana's `billingStopped()`), so it owes nothing; its requests cannot be accepted and leave the instructor's list.
- **Requests** only for a class that is published and not ended, of a public instructor, and not the student's own class (`TeachingClass::acceptsRequests()`). A visitor pressing «اطلب الانضمام» goes through `GET /classes/{id}/request` (auth), so after signing in they land back on the class page with `?request=1` and the box open.
- **Meeting link** reaches the student only in `EnrollmentPresenter::forStudent()` and only when `isActiveMember()` (accepted, class not canceled). Public pages never have it.
- **Student pages** `/account/classes` and `/account/classes/{enrollment}` (`StudentEnrollmentRequest`: own enrollments only). **Instructor pages** `/teach/requests` (badge = shared `auth.user.pendingRequests`) and `/teach/classes/{id}/students`; every `{enrollment}` action goes through `Teach\EnrollmentActionRequest` (enrollment in one of the instructor's classes). Isolation tests in `tests/Feature/Teach/EnrollmentTest.php` and `tests/Feature/Student/JoinClassTest.php`.
- **Instructor reset links:** only for students accepted in that class, active, and not instructors themselves (those go to the admin). The instructor sees the URL; this follows the spec (students ask their instructor). Limited to 20 per hour.
- **Email** (only to people who gave one): the instructor on a new request, the student on accept or reject. Failures are reported, never block the change.

## Payments and receipts (phase 4)

- **Payments** (`Payment`, table `payments`) are made only by `App\Support\Payments`: **voided, never deleted** (reason required; the row and receipt number stay; `deleting` is refused), **amount and date locked** after recording, only method and notes editable. Methods by hand: cash, transfer, other (`provider` and `provider_reference` stay empty until DPay / MyPay).
- **Who can be paid for:** only an accepted student of a class that is not canceled (`Enrollment::isActiveMember()`), and **never more than the remaining** (the enrollment row is locked while checking). A removed or withdrawn student, or a canceled class, owes nothing more; their past payments stay.
- **Money math** in whole dirhams (`Currency::toMillis()` / `fromMillis()`), never floats; `Currency::format()` drops trailing zeros for display. Fee − paid (not voided) = remaining (`Enrollment::remainingMillis()`).
- **Dates:** `paid_at` uses the `App\Casts\TulabiDateTime` cast (a typed `Y-m-d` = local midnight in Tripoli, stored UTC; copied from rqmana's `CenterDateTime`); not in the future, not before 2000. The dashboard's "this month" uses `TulabiClock::monthRangeUtc()`.
- **Receipt numbers** `TL-YYYYMMDD-NNNN` from `receipt_counters` (one row per instructor, locked, restarts at 1 each Libyan day, never goes backwards); unique per instructor, so two instructors can both have `TL-…-0001`.
- **Receipts** `GET /receipts/{payment}` (PDF, mPDF with `resources/fonts/Tajawal-*.ttf`, view `resources/views/receipts/payment.blade.php`): only the instructor who recorded it and the student it is for. Paid and remaining on a receipt count payments up to that one, so a printed receipt keeps saying the same thing; a voided receipt opens with «ملغى».
- **Pages:** instructor `/teach/enrollments/{id}/payments` (record, edit, void; ownership in `RecordPaymentRequest` / `PaymentRequest`), paid and remaining on the class students page, «المحصّل هذا الشهر» and «المتبقي على الطلاب» on the dashboard; student payments in the class detail and «دفعاتي» `/account/payments`. Isolation tests in `tests/Feature/Teach/PaymentTest.php`.

## Conventions carried over from rqmana

- **Dates:** timestamps are stored in UTC; `config('app.timezone')` stays `UTC`. For "today", "this month" or a date a person typed, use `App\Support\TulabiClock` (Africa/Tripoli, from `config/tulabi.php`), never `now()->toDateString()`. A typed `Y-m-d` means local midnight (`TulabiClock::storeDate()`). Filter by date with `startOfDayUtc()`/`endOfDayUtc()`, not `whereDate`.
- **Phones:** normalise every phone with `App\Support\LibyanPhone::normalize()` before saving or looking up; store E.164 (`+2189XXXXXXXX`). `forWhatsApp()` gives the wa.me digits, `display()` the local form (091-2345678). Only mobiles are accepted (landlines are rejected).
- **Permissions:** every route checks access on the server. Put the check in the FormRequest `authorize()` (so 403 comes before validation) and keep the controller check as a second layer. An instructor only ever sees their own classes and their students; a student only their own enrollments. Write isolation tests for each.
- **User model:** `phone`, `password` and `status` (and any role/type column) are **not mass-assignable**. Set them with `forceFill()`/`forceCreate()` and explicit values only.
- **HTML from instructors** (class descriptions) must go through a `SafeHtml` sanitiser (copy `App\Support\SafeHtml` and `tests/Unit/SafeHtmlTest.php` from rqmana when the first rich text field arrives); it is rendered with `v-html`.
- **Text:** all UI text is Arabic, in `resources/js/locales/ar.json` (frontend, checked by `messages.spec.js`: write `@ { } |` as `{'@'}` etc.) and `lang/ar/*.php` (backend). Keep it short and plain.
- **Icons:** Tabler icons (`icon="tabler:name"`) must be registered in `resources/js/icons/tablerChrome.js`, or they render blank; `tablerChrome.spec.js` fails on an unregistered name.
- **Design system:** rqmana's, recoloured green. Primary `#0A7D4F`, hover/dark `#075C3A`, yellow accent `#FFC24A` (marks and underlines only, never text on white). Tokens in `resources/css/tokens.css` keep the `--rq-` prefix and classes keep `rq-` (`rq-btn`, `rq-card`, `rq-eyebrow`, `rq-badge`, `rq-icon-tile`, ...) so rqmana components can be copied as they are; brand shades are `--rq-brand-*` (`--rq-green-*` is the success colour). Vuetify themes in `resources/js/config/vuetifyTheme.js` (light and dark). Fonts: Alexandria (display) and Tajawal (body) from fonts.bunny.net. Square-ish 4px buttons, flat, hairlines instead of shadows.
- Pages use `PublicLayout.vue` (pages under `Admin/` use `AdminLayout.vue`) unless they set their own `layout` (`layout: null` for full-page screens). Forms use rqmana's `Components/formFields` (FormPlainTextField etc.) with Inertia `useForm` errors; the server's one-time message is the shared `flash.status` (`FlashStatus.vue`).
- Formatting: `vendor/bin/pint` on changed PHP files.

## Running locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # sample accounts and classes
php artisan storage:link
npm run build                      # or: npm run dev (Vite on 127.0.0.1:5174)
php artisan serve --port=8010      # http://127.0.0.1:8010
```

`.env.example` points at `http://tulabi.test` for Laragon; the virtual host appears after Laragon reloads its sites. The local database is sqlite (`database/database.sqlite`).

## Tests

- `php artisan test` (sqlite in memory, `phpunit.xml` forces it): 151 tests. `tests/TestCase.php` uses `RefreshDatabase` and calls `withoutVite()` so pages render without a build. Admin test accounts need a confirmed second factor: `Admin::factory()->withTwoFactor()`; `actingAs($admin, 'admin')`.
- `npm run test:js` (vitest): translation strings compile, every Tabler icon used is registered.
- CI (`.github/workflows/ci.yml`, mirrors rqmana): PHP tests on 8.3 and 8.5, then `npm ci`, `test:js` and the build on 8.5. It runs once a GitHub remote exists.
