# Tulabi API (v1) — for the mobile app

The Flutter app talks to this Laravel project through `/api/v1`. This file is the contract;
keep it in step with `routes/api.php` and `tests/Feature/Api`.

## Basics

- **Base URL:** `https://tulabi.ly/api/v1` (locally `http://127.0.0.1:8010/api/v1`).
- **Every answer is JSON**, errors included. Text in answers is Arabic, ready to show.
- **Headers the app sends on every request:**
  - `Accept: application/json` (optional; the API answers JSON anyway)
  - `X-App-Version: 1.0.0` — the app's version. Older than the server's minimum → `426` (see below).
  - `Authorization: Bearer <token>` — on routes that need a signed-in account.
- **Times** are ISO 8601 in UTC (`2026-11-05T17:00:00+00:00`). The calendar is Libya's
  (`Africa/Tripoli`, also in `/meta`): show times in that zone.
- **Phones** may be typed any usual way (`0912345678`, `091-234-5678`, `+218912345678`);
  the server normalises them. Only Libyan mobiles are accepted.
- **Limits:** 120 requests a minute per account (or per IP before signing in); sign-up, codes
  and password reset have tighter limits, as on the website. Over a limit → `429`.

## Errors

| Status | Meaning | Body |
|---|---|---|
| 401 | No token, a wrong or expired one, or signed out elsewhere | `{ "message": "..." }` — go to sign-in |
| 401 + `code: account_suspended` | The account was suspended (the token is deleted) | `{ "message", "code" }` — show the message, go to sign-in |
| 403 | Signed in but not allowed | `{ "message" }` |
| 404 | Not found (also: someone else's item) | `{ "message" }` |
| 422 | Validation | `{ "message", "errors": { "field": ["..."] } }` — show each under its field |
| 426 + `code: update_required` | App too old | `{ "message", "code", "min_version", "store_urls": { "android", "ios" } }` — block, link to the store |
| 429 | Too many requests | `{ "message" }` (and `Retry-After` header) |

## Start-up

`GET /meta` (no sign-in, never 426)

```json
{ "api_version": 1, "min_version": "1.0.0", "latest_version": "1.0.0",
  "store_urls": { "android": null, "ios": null }, "timezone": "Africa/Tripoli" }
```

If the app is older than `min_version`, ask to update (blocking); older than `latest_version`,
offer it. Then, with a saved token, call `GET /me`; a 401 means sign in again.

## Signing in

`POST /auth/login` — `{ "phone", "password", "device_name" }` (e.g. "Pixel 8", shown later in a
list of devices). Five wrong passwords for a phone lock it for ten minutes (shared with the
website). Answer:

```json
{ "token": "12|abc...", "token_type": "Bearer", "expires_at": "2027-05-04T...", "user": { ... } }
```

Keep the token in secure storage (Keychain / Keystore). It lasts 180 days; after that, or after
a password change or reset, the API answers 401.

`POST /auth/logout` — this device. `POST /auth/logout-all` — every device signed in to the app.

`POST /auth/forgot-password` — `{ "phone" }`. Always the same answer; an account with an email
gets a link (it opens the website's reset page). Accounts without email ask their instructor or
the admin for a link, as on the website.

## Signing up (three steps)

1. `POST /auth/register/code` — `{ "phone" }`. Sends a six-digit code by SMS.
   → `{ "message", "resend_in": 60, "expires_in": 600 }`. A number that already has an account,
   or asking again within 60 seconds, is a 422 on `phone`.
2. `POST /auth/register/verify` — `{ "phone", "code" }`.
   → `{ "registration_token", "expires_in": 1800 }`. Wrong code: 422 on `code`; five wrong tries
   or ten minutes void the code (send a new one).
3. `POST /auth/register` — `{ "registration_token", "password", "password_confirmation", "device_name" }`
   (password at least 8). → `201` with the same body as sign-in. An old or foreign token, or a
   number taken meanwhile: 422 on `registration_token` (start again from step 1).

## The account

`GET /me`

```json
{ "user": {
    "id": 7, "name": "سارة", "phone": "091-2345678", "email": null,
    "is_instructor": true, "instructor_application_pending": false,
    "instructor": { "slug": "sara", "display_name": "أ. سارة", "public_url": "https://tulabi.ly/sara", "pending_requests": 3 }
} }
```

`name` is null until the person fills it in. `instructor` is null for students. One account can
be both: show the instructor side when `is_instructor` is true.

## An instructor's page (no sign-in needed)

The same things visitors see on `tulabi.ly/{slug}`: drafts, hidden programs and suspended
instructors are 404, even for the instructor (they manage their own in the instructor part).
All `*_url` fields are full addresses. `url` fields point to the website (for sharing).

- `GET /instructors/{slug}` → `{ instructor, classes, programs }`
  - `instructor`: `name, slug, url, specialty, city, photo_url, links, whatsapp_number, whatsapp_url, has_programs,
    about_sections: [{ title, body (safe HTML), text (plain) }]`
  - `classes`: open classes, soonest first: `id, title, description, learn, requirements, audience,
    video_embed_url, type (online|in_person), type_label, location, map_url, image_url, program {name, url} | null,
    status, status_label, is_open (takes requests), ended, starts_on (Y-m-d), starting_date, ending_date,
    schedule, time, price (text), is_free, seats, whatsapp_url, enrollment` (see below)
  - `programs`: featured first: `id, name, description, learn, requirements, audience, video_embed_url, summary,
    url, poster_url, is_featured, upcoming_count`
- `GET /instructors/{slug}/classes/{id}` → `{ instructor, class }`. With a token, `class.enrollment` is the
  person's own request: `{ id, status, status_label }` (null if none). Use it to show «طلبك قيد المراجعة» etc.
- `GET /instructors/{slug}/programs/{id}` → `{ instructor, program, classes }` (its open classes).

`POST /classes/{id}/join` (token) — `{ "note": "..." }` (optional, up to 500). → `201 { message, enrollment }`.
422 on `enrollment` when the class takes no requests, it is the person's own class, or they already
asked / are in it. Limited to 20 an hour.

## The student side (token)

Enrollment `status`: `requested` (waiting), `accepted`, `rejected`, `withdrawn`, `canceled` (removed by the
instructor, or the class was canceled).

- `GET /student/classes` → `{ enrollments: [...] }`, newest request first. Each: `id, status, fee, note,
  decision_reason, requested_at, decided_at, class { ...the class's details, meeting_url, schedule_changed_at,
  meeting_url_new, meeting_url_changed_at, cancel_reason, url }, instructor { display_name, photo_url, url, whatsapp }`.
  `meeting_url` is only there for an accepted student of a class that still runs; `schedule_changed_at`
  and `meeting_url_new` say the instructor changed them in the last few days (show a notice).
- `GET /student/classes/{id}` → `{ enrollment, totals: { fee, paid, remaining }, payments: [...] }`. Someone else's: 403.
- `POST /student/classes/{id}/withdraw` → `{ message, enrollment }`. Takes back a waiting request or leaves
  a class (as on the website; ask "are you sure?" first). Already decided: 422 on `enrollment`.
- `GET /student/payments` → `{ payments: [...] }` in every class: `id, amount, currency, paid_on (Y-m-d), method,
  receipt_number, notes, voided, void_reason, receipt_url, class_title, instructor, enrollment_id`.
- `GET /receipts/{id}` → the receipt as a PDF (A5, Arabic). Open `receipt_url` with the token header and
  show or share the file. Only the student and the instructor who recorded it; others 403.

Amounts are text with up to three decimals (`"120"`, `"62.5"`), in Libyan dinars (`currency: "LYD"`, shown «د.ل»).

## The instructor side (token of an active instructor; others 403)

Everything is the instructor's own: another instructor's class is 404, their enrollment or payment 403.
The data is the same as the website's pages (shared code in `App\Support\Teach`).

**Home and lists**

- `GET /teach/home` → `{ profile { display_name, slug, public_url, has_photo, has_bio }, counts { draft, published, ... }
  (classes per status, always an object), upcoming [the next 5 running classes, full details + students_count],
  students (accepted, in classes not canceled), money { collected (this month), remaining (still owed), currency }, pending_requests }`
- `GET /teach/students?filter=active|past|all&q=&course=&page=` → `{ students: { data: [...], current_page, last_page, total, ... },
  filter, q, course, courses [{id, title}], counts { active, past, all }, totals { remaining } }`. 15 per page.
- `GET /teach/payments?from=&to=&course=&method=&status=valid|voided|all&q=&page=` → `{ payments: { data: [payment + enrollment_id,
  student { name, phone }, class { id, title }], ...paging }, filters, courses, methods, totals { collected, count, voided, by_method },
  payable [students who still owe: { id (enrollment), name, class, remaining }], today, monthStart }`. 25 per page.
  Bad filters: 422.

**Join requests and a class's students**

- `GET /teach/requests` → `{ requests: [...] }`, oldest first. Each: `id, status, fee, note, decision_reason, requested_at,
  accepted_at, student { name, phone, whatsapp }, class { id, title, seats }, accepted_count`.
- `GET /teach/classes/{id}/students` → `{ class { id, title, status, seats, price, meeting_share_url }, enrollments: [row + totals] }`
  (waiting first, then accepted, then the rest).
- `POST /teach/enrollments/{id}/accept`, `/reject` (`{ reason? }`), `/remove` (`{ reason? }`) → `{ message, enrollment }` (the new row,
  with totals). Already decided: 422 on `enrollment`. A canceled or ended class accepts nobody (422). Seats are not
  enforced: show `accepted_count` against `class.seats` as a warning, as the website does.
- `POST /teach/enrollments/{id}/reset-link` → `{ reset_link { name, url, whatsapp } }`: a one-time link (1 hour) to give an
  accepted student who forgot their password; `whatsapp` is a ready wa.me link. 20 an hour.

**Payments** (voided, never deleted; amount and date fixed once recorded)

- `GET /teach/enrollments/{id}/payments` → `{ enrollment, totals { fee, paid, remaining }, canPay, payments, methods, today }`.
- `POST /teach/enrollments/{id}/payments` — `{ amount (> 0, up to 3 decimals, not more than what is left), paid_at (Y-m-d, not in the
  future), method (cash|transfer|other), notes? }` → `201 { message, payment, totals }`.
- `PUT /teach/payments/{id}` — `{ method, notes? }` → `{ message, payment, totals }`. Other fields are ignored.
- `POST /teach/payments/{id}/void` — `{ reason }` (3+ letters) → `{ message, payment (voided: true), totals }`. Twice: 422 on `payment`.

## Rules for the API code (server side)

- New routes go in `routes/api.php` under `v1`, named `api.v1.*`, with a test in `tests/Feature/Api`.
- Reuse the website's FormRequests and `App\Support` services; put the check in `authorize()`
  (another person's item is a 404, as on the website).
- Never change the shape of an existing answer in a way an installed app would break on: add
  fields, don't rename or remove them. A breaking change is `/api/v2`, and raises `APP_MIN_VERSION`.
