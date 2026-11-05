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

## Rules for the API code (server side)

- New routes go in `routes/api.php` under `v1`, named `api.v1.*`, with a test in `tests/Feature/Api`.
- Reuse the website's FormRequests and `App\Support` services; put the check in `authorize()`
  (another person's item is a 404, as on the website).
- Never change the shape of an existing answer in a way an installed app would break on: add
  fields, don't rename or remove them. A breaking change is `/api/v2`, and raises `APP_MIN_VERSION`.
