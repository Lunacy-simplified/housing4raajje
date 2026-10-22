# Frontend handoff: authentication and staff management

Backend by Looth: login, registration, admin staff management (Sprint 2).
Last updated: 22 October 2026 (added Last login).

## What this is

The backend for these pages is finished and covered by automated tests. Every page below
already exists as a **bare, unstyled Blade view**, so the backend could be tested. Your job is
to replace those bare views with the real design.

This document lists what each page **receives** from the controller and what it must **send
back**. Stick to that and you can restyle freely without breaking anything.

## Ground rules

1. **Change how a page looks, not what it sends.** Keep every route name, form `method` and
   `action`, field `name`, `@csrf`, `@method(...)` and error key exactly as listed here.
2. **Don't edit controllers, routes or tests.** If a page needs data it doesn't receive, ask Looth.
3. **Run the tests after every page:** `docker compose exec app php artisan test`.
   The tests check behaviour, not looks, so they must all still pass. If one fails after you
   restyled a page, you most likely renamed a field or changed a route.
4. **Match the approved prototype** (at least 80% consistent): `H4R_Prototype.html` (P-03 sign-in)
   and the templates in `ibrahim_waseems_templates/`.
5. Never commit `.env`. Check `git status` before every commit.

## Getting set up

```bash
git checkout main
git pull
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed   # WARNING: wipes your local database
```

Run every `artisan` command inside Docker (`docker compose exec app ...`), not with Laragon's PHP.

Demo accounts (local only, created by `DemoUserSeeder`):

| Role | Email | Password | Lands on |
|---|---|---|---|
| System administrator | admin@h4r.local | SecretAdmin123! | `/admin` |
| Branch manager | manager@h4r.local | SecretManager123! | `/manager` |
| Property specialist | specialist@h4r.local | SecretSpecialist123! | `/staff` |
| Receptionist | reception@h4r.local | SecretReception123! | `/staff` |
| Property owner | owner@h4r.local | SecretOwner123! | `/owner` |
| Client | client@h4r.local | SecretClient123! | `/client` |

## Suggested structure for the shared design

None of this exists yet. It's a suggestion so that everyone's pages share one design.

- **CSS:** plain CSS files in `public/css/`: `tokens.css` (colours, fonts, spacing),
  `layout.css` and `components.css`. The prototype's `SPLIT POINT` comments show where to split its CSS.
- **Layouts:**
  - `resources/views/layouts/auth.blade.php` for sign-in and registration.
  - A portal layout (top bar and side navigation) for dashboards and admin pages.
  - Pages use `@extends('layouts.auth')` and put their content in `@section('content')`.
- Build the CSS files and layouts **first**. Every page depends on them.

## Things every page shares

- **Success messages** arrive as `session('status')`. Show them at the top of the page when set.
- **Validation errors:** after a failed submit, the user is sent back to the form with `$errors`.
  - Under each field, use `@error('field') {{ $message }} @enderror`.
  - Refill values with `old('field')`. Never refill passwords.
- **Forms:**
  - Every POST form needs `@csrf`.
  - HTML can't send PUT or PATCH, so those forms are `method="POST"` plus `@method('PUT')` or `@method('PATCH')`.
  - GET forms (filters) don't need `@csrf`.
- **Logout** is a POST form, never a plain link:
  ```blade
  <form method="POST" action="{{ route('logout') }}">@csrf <button>Log out</button></form>
  ```
- **Error pages Laravel shows by itself:**
  - 403: wrong role
  - 404: not found
  - 429: too many attempts

  Custom designs can go in `resources/views/errors/403.blade.php` and so on (optional).
- **Rules worth showing as hints on forms:**
  - **Password:** at least 8 characters, with letters and numbers.
  - **Phone:** Maldivian mobile, 7 digits starting with 7 or 9 (for example `7771234`).

---

## 1. Sign in

| | |
|---|---|
| View | `resources/views/auth/login.blade.php` |
| URL | `GET /login`, route `login` (logged-out users only) |
| Form | `POST` to `route('login')` |
| Data received | none |

| Field `name` | Type | Notes |
|---|---|---|
| `email` | email | refill with `old('email')` |
| `password` | password | |
| `remember` | checkbox | "Remember me" |

- **Errors:** login failures all appear on **`email`**. That covers "These login details are incorrect."
  and "This account has been deactivated. Contact your administrator." An empty password gives
  an error on `password`.
- **Too many attempts:** after 5 failed attempts in one minute, Laravel shows a 429 page.
- **On success:** the user is sent to their own dashboard (see section 3).
- **Links to add:**
  - "Register as a client": `route('register', 'client')`
  - "Register as a property owner": `route('register', 'owner')`
  - Leave out "Forgot password?" for now; it isn't built yet.

## 2. Register (client and owner)

| | |
|---|---|
| View | `resources/views/auth/register.blade.php` (one view for both types) |
| URL | `GET /register/client` and `GET /register/owner` (logged-out users only) |
| Link to it | `route('register', 'client')` or `route('register', 'owner')` |
| Form | `POST` to `route('register', $type)` |
| Data received | `$type`: `'client'` or `'owner'` |

Staff can't register. Their accounts are created by the admin (section 5).

| Field `name` | Type | Required | Notes |
|---|---|---|---|
| `name` | text | yes | max 255 |
| `email` | email | yes | must not already be registered |
| `phone` | tel | yes | mobile rule above; must not already be registered |
| `national_id_or_passport` | text | **owners only** | wrap in `@if ($type === 'owner') ... @endif`; max 30 |
| `password` | password | yes | password rule above |
| `password_confirmation` | password | yes | must be named exactly this |
| `terms` | checkbox, `value="1"` | yes | "I accept the terms and conditions" |

- **Errors** arrive on each field's own name.
- **On success:** the user is logged in and sent to the owner or client dashboard.
- **Limit:** 10 attempts per minute.
- **Link to add:** "Already have an account? Sign in", pointing to `route('login')`.

## 3. Dashboards (placeholders)

| Route name | URL | Who |
|---|---|---|
| `admin.dashboard` | `/admin` | System administrator |
| `manager.dashboard` | `/manager` | Branch manager |
| `staff.dashboard` | `/staff` | Property specialist, receptionist |
| `owner.dashboard` | `/owner` | Property owner |
| `client.dashboard` | `/client` | Client |

- **Current state:** all five show `resources/views/dashboard-placeholder.blade.php`. It receives
  `$portal`, the portal's display name, and contains the logout form.
- **Dashboards with data:** a dashboard that shows data (counts, lists, recent activity) needs a
  controller behind it. Agree on who builds it before starting.
- **Every dashboard** needs the logout form.

## 4. Staff list (admin)

| | |
|---|---|
| View | `resources/views/admin/staff/index.blade.php` |
| URL | `GET /admin/staff`, route `admin.staff.index` (system administrator only) |

**Data received:**

- `$staff`: staff members, 20 per page. For each `$member`:

  | Show | Use |
  |---|---|
  | Staff code | `$member->staffProfile->staff_code` |
  | Name | `$member->name` |
  | Email | `$member->email` |
  | Phone | `$member->phone` |
  | Role | `$member->role->name` |
  | Branch | `$member->staffProfile->branch?->name ?? '—'` (administrators have no branch) |
  | Job title | `$member->staffProfile->job_title` |
  | Status | `$member->is_active` (true means Active) |
  | Last login | `$member->last_login_at?->diffForHumans() ?? 'Never'` (for example "3 hours ago"); updated on every sign-in through the login form |

- `$roles`: the four staff roles, for the filter (`->code`, `->name`).
- `$branches`: **all** branches, for the filter (`->id`, `->name`).

**Filter form:** `method="GET"`, `action="{{ route('admin.staff.index') }}"`, no `@csrf`.

| Field `name` | Values |
|---|---|
| `role` | a role code, or empty for all |
| `branch_id` | a branch id, or empty for all |
| `status` | `active`, `inactive`, or empty for all |

Keep the chosen option selected with `request('role')`, `request('branch_id')` and `request('status')`.

- **Pagination:** `{{ $staff->links('pagination::bootstrap-4') }}` prints a plain `<ul class="pagination">`.
  Style that class, or ask about a custom pagination view. Filters carry over to other pages automatically.
- **No results:** use `@forelse ... @empty ... @endforelse` to show "No staff match these filters."
- **Links to add:**
  - "Create staff": `route('admin.staff.create')`
  - On each row, "Edit": `route('admin.staff.edit', $member)`. This isn't in the bare view yet.

## 5. Create staff (admin)

| | |
|---|---|
| View | `resources/views/admin/staff/create.blade.php` |
| URL | `GET /admin/staff/create`, route `admin.staff.create` |
| Form | `POST` to `route('admin.staff.store')` |
| Data received | `$roles` (`->code`, `->name`); `$branches`: **active** branches only (`->id`, `->name`, `->branch_no`) |

| Field `name` | Type | Required | Notes |
|---|---|---|---|
| `name` | text | yes | |
| `email` | email | yes | must not already be registered |
| `phone` | tel | yes | mobile rule; must not already be registered |
| `role` | select | yes | option `value` = `$role->code`, label = `$role->name` |
| `branch_id` | select | yes, **except administrators** | first option must be `<option value="">No branch</option>`; must be empty for System Administrator |
| `job_title` | text | no | max 80 |
| `hired_on` | date | no | can't be in the future |
| `password` | password | yes | temporary password the admin gives to the new staff member |
| `password_confirmation` | password | yes | |

- **Staff code:** generated automatically (`H4R-STF-0001`, `0002`, ...). There's no field for it.
- **On success:** redirected to the staff list with a status message that includes the new code.

## 6. Edit staff (admin)

| | |
|---|---|
| View | `resources/views/admin/staff/edit.blade.php` |
| URL | `GET /admin/staff/{user}/edit`; link with `route('admin.staff.edit', $member)` |
| Data received | `$user` (with `$user->role`, `$user->staffProfile`, `$user->staffProfile->branch`); `$roles`; `$branches`: **active** only |

This page only works for staff. Any other user id gives a 404. The page has **two separate forms**.

**a) Details form:** `POST` to `route('admin.staff.update', $user)`, plus `@method('PUT')`.

- **Fields:** the same as Create staff, **without the two password fields**.
- **Refill with the current value as the fallback:**
  - `old('name', $user->name)`
  - `old('role', $user->role->code)`
  - `old('branch_id', $user->staffProfile->branch_id)`
  - `old('hired_on', optional($user->staffProfile->hired_on)->format('Y-m-d'))`
- **Staff code:** show it as plain text (`$user->staffProfile->staff_code`), never as an input. It never changes.
- **On success:** redirected to the staff list with a status message.
- **Own account:** an admin editing their own account can't change their own role. The error appears on `role`.

**b) Activate / deactivate form:** `POST` to `route('admin.staff.status', $user)`, plus `@method('PATCH')`.

- **Hidden field:** `is_active` set to `0` to deactivate or `1` to activate.
- **On success:** back to the same page with a status message.
- **Own account:** an admin can't deactivate themselves. The error appears on `is_active`.
- **Effect:** a deactivated user is logged out on their next click and sees the "deactivated" message on the sign-in page.

Password changes are not on this page; they come with password reset (see below).

---

## Not built yet

Don't build pages for these yet:

- **Forgot / reset password, email verification, phone verification (SMS) and MFA:** Looth, Sprint 3.
  Their pages and field names will be added to this document when they're ready.
- **A branch manager's view of their own branch's staff:** a separate manager-portal feature, not part of this work.

## Checklist before opening a pull request for a page

- [ ] Route names, field names, `@csrf` and `@method` unchanged
- [ ] Every field shows its own error with `@error`, and refills with `old()` (except passwords)
- [ ] `session('status')` shown where this document says so
- [ ] `docker compose exec app php artisan test`: everything passes
- [ ] Compared side by side with the prototype
