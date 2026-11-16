# Data dictionary

Generated from the PostgreSQL database after running every migration, so it matches the application exactly. Field descriptions are the column comments stored in the database.

**Conventions.** Money is `decimal(12,2)` in MVR. `timestamptz` holds a date and time with time zone. Every table with `created_at` and `updated_at` has them filled in automatically. Status columns only accept the values listed under "Rules". Rows are never hard-deleted where history matters: accounts and lookup items are switched off with `is_active`.

| Group | Tables |
|---|---|
| Reference data | [`branches`](#branches), [`districts`](#districts), [`lease_terms`](#lease_terms), [`payment_methods`](#payment_methods), [`feedback_categories`](#feedback_categories), [`system_parameters`](#system_parameters) |
| People and access | [`roles`](#roles), [`permissions`](#permissions), [`permission_role`](#permission_role), [`users`](#users), [`staff_profiles`](#staff_profiles), [`staff_compensation`](#staff_compensation), [`owner_profiles`](#owner_profiles), [`client_profiles`](#client_profiles), [`client_preferred_branches`](#client_preferred_branches), [`password_reset_tokens`](#password_reset_tokens), [`staff_invite_tokens`](#staff_invite_tokens), [`one_time_codes`](#one_time_codes), [`trusted_devices`](#trusted_devices), [`sessions`](#sessions) |
| Properties | [`properties`](#properties), [`property_photos`](#property_photos) |
| Viewings and applications | [`viewings`](#viewings), [`applications`](#applications) |
| Leases and money | [`leases`](#leases), [`lease_signatures`](#lease_signatures), [`payments`](#payments), [`payouts`](#payouts) |
| Support | [`maintenance_requests`](#maintenance_requests), [`inquiries`](#inquiries), [`support_tickets`](#support_tickets), [`escalations`](#escalations) |
| Communication | [`message_threads`](#message_threads), [`thread_participants`](#thread_participants), [`messages`](#messages), [`notifications`](#notifications) |
| System | [`audit_log`](#audit_log) |

Laravel also keeps its own housekeeping tables, which hold no business data: `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`.

## Reference data

### branches

The three regional offices. Staff, districts and properties each belong to one.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the branches table |
| `branch_no` | smallint | yes | unique |  | Internal unique branch sequence number, must be positive |
| `code` | varchar(3) | yes | unique |  | 3-letter uppercase identifier code e.g., MLE, HLM, VIL |
| `name` | varchar(60) | yes | unique |  | Official location name of the regional housing branch office |
| `phone` | varchar(20) | no |  |  | Primary public contact phone number for this office branch |
| `is_active` | boolean | yes |  | `true` | Toggle status to enable or disable branch routing operations |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((branch_no > 0))`
- Check: `(((code) ~ '^[A-Z]{3}$'))`

### districts

Areas of the city. A property's district decides which branch handles it.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the districts table |
| `name` | varchar(80) | yes | unique |  | Official name of the district |
| `branch_id` | bigint | yes | FK → branches.id |  | Foreign key linking the district to its managing parent branch office |
| `is_active` | boolean | yes |  | `true` | Status flag to determine if the district is active for housing routing operations |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### lease_terms

The fixed lease lengths a client can choose from.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the lease_terms table |
| `months` | smallint | yes | unique |  | Lease length in months; must be above 0 |
| `label` | varchar(30) | yes |  |  | Name shown to clients |
| `is_active` | boolean | yes |  | `true` | False hides the term from new applications without changing existing leases |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((months > 0))`

### payment_methods

How rent can be paid.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the payment_methods table |
| `code` | varchar(30) | yes | unique |  | Short code used in the app |
| `name` | varchar(60) | yes |  |  | Name shown to users, e.g. Bank deposit |
| `is_active` | boolean | yes |  | `true` | False hides the method from new leases and payments without changing old records |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### feedback_categories

Standard reasons staff pick after a viewing; they feed the owner's rejection chart.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the feedback_categories table |
| `label` | varchar(60) | yes | unique |  | Reason staff pick after a viewing |
| `is_rejection` | boolean | yes |  | `true` | True if this reason counts in the owner rejection charts; false for Client proceeding |
| `sort_order` | smallint | yes |  | `'0'` | Display order in the feedback list and charts |
| `is_active` | boolean | yes |  | `true` | False hides the category from new viewings without deleting old feedback |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### system_parameters

Global settings the administrator can change, e.g. the management fee.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the system_parameters table |
| `key` | varchar(60) | yes | unique |  | Setting name used in the code |
| `value` | varchar(255) | yes |  |  | Current value of the setting, stored as text |
| `description` | varchar(255) | no |  |  | What the setting controls, shown on the admin configuration screen |
| `updated_by_user_id` | bigint | no | FK → users.id |  | Administrator who last changed this setting; empty if never changed |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

## People and access

### roles

The six user roles used for role-based access control.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the roles table |
| `code` | varchar(30) | yes | unique |  | Unique string identifier used in code checks, in lowercase |
| `name` | varchar(60) | yes |  |  | Human-readable name for the role |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### permissions

Fine-grained permission codes. Reserved: access is currently decided by role.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the permissions table |
| `code` | varchar(60) | yes | unique |  | Unique action permission identifier code |
| `description` | varchar(255) | no |  |  | Detailed explanation outlining what specific access permissions this code grants to a user |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### permission_role

Which permissions each role has (many-to-many).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `permission_id` | bigint | yes | PK, FK → permissions.id |  | Foreign key reference mapping back to the primary permissions structural definitions table |
| `role_id` | bigint | yes | PK, FK → roles.id |  | Foreign key reference mapping back to the primary system application roles definitions table |

Rules:

- Primary key: (`permission_id`, `role_id`)

### users

Every account: staff, owners and clients. The role decides the portal.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the users table |
| `role_id` | bigint | yes | FK → roles.id |  | Role that decides what this user can access |
| `name` | varchar(255) | yes |  |  | Full name of the user |
| `email` | varchar(255) | no |  |  | Login email address; optional, unique when present |
| `email_verified_at` | timestamptz | no |  |  | When the user confirmed their email address; empty until verified |
| `phone` | varchar(20) | no | unique |  | Contact number; attached to an owner's listings once verified |
| `phone_verified_at` | timestamptz | no |  |  | When the phone number was verified; empty until verified |
| `password` | varchar(255) | yes |  |  | Hashed password |
| `is_active` | boolean | yes |  | `true` | False deactivates the account without deleting its history |
| `last_login_at` | timestamptz | no |  |  | When the user last signed in; empty if never |
| `remember_token` | varchar(100) | no |  |  | Laravel “remember me” login token |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |
| `mfa_enabled` | boolean | yes |  | `false` | True when an owner, client or other non-admin user has switched on sign-in codes; admins and managers always use them |

Rules:

- Unique: `(lower((email)))`

### staff_profiles

Extra details for staff accounts (one-to-one with users).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `user_id` | bigint | yes | PK, FK → users.id |  | Same id as the user this profile belongs to |
| `staff_code` | varchar(20) | yes | unique |  | Staff reference shown on viewing logs and leases, e.g. H4R-STF-0042 |
| `branch_id` | bigint | no | FK → branches.id |  | Home branch; empty only for the system administrator |
| `job_title` | varchar(80) | no |  |  | Job title shown on the staff profile |
| `hired_on` | date | no |  |  | Date the staff member joined Housing_4_Raajje |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### staff_compensation

Salary history per staff member, visible to managers and the administrator only.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the staff_compensation table |
| `staff_user_id` | bigint | yes | FK → staff_profiles.user_id |  | Staff member this salary belongs to |
| `monthly_salary` | decimal(12,2) | yes |  |  | Monthly salary in MVR; never negative |
| `effective_from` | date | yes |  |  | First day this salary applies |
| `effective_to` | date | no |  |  | Last day this salary applies; empty while it is the current salary |
| `recorded_by_user_id` | bigint | yes | FK → users.id |  | Branch manager or administrator who recorded this salary |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((monthly_salary >= (0)))`

### owner_profiles

Extra details for owners, including payout bank details (one-to-one with users).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `user_id` | bigint | yes | PK, FK → users.id |  | Same id as the user this profile belongs to |
| `national_id_or_passport` | varchar(30) | yes | unique |  | Owner National ID card or passport number |
| `bank_name` | varchar(80) | no |  |  | Bank that receives the owner payouts |
| `bank_account_name` | varchar(120) | no |  |  | Name on the payout bank account |
| `bank_account_no` | varchar(40) | no |  |  | Account number that receives the owner payouts |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### client_profiles

Extra details and rental preferences for clients (one-to-one with users).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `user_id` | bigint | yes | PK, FK → users.id |  | Same id as the user this profile belongs to |
| `national_id_or_passport` | varchar(30) | no | unique |  | Client National ID card or passport number |
| `sex` | varchar(10) | no |  |  | Sex as entered on the client profile |
| `date_of_birth` | date | no |  |  | Client date of birth |
| `max_monthly_rent` | decimal(12,2) | no |  |  | Highest monthly rent in MVR the client will pay; used by search and the matchmaker |
| `preferred_rooms` | smallint | no |  |  | Number of rooms the client is looking for |
| `preferred_term_months` | smallint | no |  |  | Lease length the client prefers, in months |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### client_preferred_branches

Areas a client would like to live in (many-to-many); used by the matchmaker.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `client_user_id` | bigint | yes | PK, FK → client_profiles.user_id |  | Client who prefers this branch |
| `branch_id` | bigint | yes | PK, FK → branches.id |  | Branch area the client would like to live in |

Rules:

- Primary key: (`client_user_id`, `branch_id`)

### password_reset_tokens

Hashed "forgot password" tokens (15 minutes, single use).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `email` | varchar(255) | yes | PK |  |  |
| `token` | varchar(255) | yes |  |  |  |
| `created_at` | timestamp | no |  |  |  |

### staff_invite_tokens

Hashed staff invitation tokens (24 hours, single use).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `email` | varchar(255) | yes | PK |  | Email address associated with the staff invitation. |
| `token` | varchar(255) | yes |  |  | Hashed staff invitation token. |
| `created_at` | timestamp | no |  |  | Time the staff invitation token was created. |

### one_time_codes

Hashed 6-digit codes for sign-in (MFA) and phone verification.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key |
| `user_id` | bigint | yes | FK → users.id |  | User the code was sent to |
| `purpose` | varchar(30) | yes |  |  | mfa_login or phone_verification |
| `channel` | varchar(10) | yes |  |  | email or sms |
| `code_hash` | varchar(255) | yes |  |  | Hash of the 6-digit code; the plain code is never stored |
| `attempts` | smallint | yes |  | `'0'` | Wrong guesses so far; the code stops working at 5 |
| `expires_at` | timestamptz | yes |  |  | 10 minutes after the code is sent |
| `consumed_at` | timestamptz | no |  |  | Set when the code is used or replaced by a newer one |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `(((channel) = ANY ((ARRAY['email', 'sms']))))`
- Check: `(((purpose) = ANY ((ARRAY['mfa_login', 'phone_verification']))))`

### trusted_devices

Browsers a user chose to trust for 30 days so MFA is skipped.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Unique identifier for the trusted device record. |
| `user_id` | bigint | yes | FK → users.id |  | User who registered this trusted device. |
| `token_hash` | char(64) | yes | unique |  | SHA-256 hash of the trusted-device token. |
| `user_agent` | varchar(255) | no |  |  | Browser or device user-agent string. |
| `last_used_at` | timestamptz | no |  |  | Date and time this trusted device was last used. |
| `expires_at` | timestamptz | yes |  |  | Date and time this trusted device expires. |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### sessions

Signed-in browser sessions (Laravel).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | varchar(255) | yes | PK |  |  |
| `user_id` | bigint | no |  |  |  |
| `ip_address` | varchar(45) | no |  |  |  |
| `user_agent` | text | no |  |  |  |
| `payload` | text | yes |  |  |  |
| `last_activity` | integer | yes |  |  |  |

## Properties

### properties

Apartments registered by owners, with their review stage and availability.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the properties table |
| `reference` | varchar(16) | yes | unique | generated: `('H4R-APT-' || lpad((id), 4, '0'))` | Property reference generated by the database |
| `owner_user_id` | bigint | yes | FK → users.id |  | Owner who registered the property |
| `district_id` | bigint | yes | FK → districts.id |  | District from the address; decides which branch handles the property |
| `branch_id` | bigint | yes | FK → branches.id |  | Branch handling the property, copied from the district when submitted |
| `house_name` | varchar(80) | yes |  |  | House or plot name from the address |
| `road` | varchar(80) | yes |  |  | Road the house is on |
| `unit_no` | varchar(10) | no |  |  | Apartment or unit number, if any |
| `floor` | smallint | no |  |  | Floor the unit is on, if any |
| `property_type` | varchar(20) | yes |  |  | apartment or flat |
| `rooms` | smallint | yes |  |  | Number of rooms; must be above 0 |
| `monthly_rent` | decimal(12,2) | yes |  |  | Monthly rent in MVR asked by the owner; must be above 0 |
| `verification_status` | varchar(20) | yes |  | `'draft'` | Staff review stage: draft, pending, changes_requested, approved or rejected |
| `availability_status` | varchar(20) | no |  |  | available, rented or under_maintenance; set only once the property is approved |
| `submitted_at` | timestamptz | no |  |  | When the owner submitted the listing for review |
| `verified_by_user_id` | bigint | no | FK → users.id |  | Staff member who approved the property; empty until approved |
| `verified_at` | timestamptz | no |  |  | When the property was approved |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((((verification_status) = 'approved') = (availability_status IS NOT NULL)))`
- Check: `(((availability_status) = ANY ((ARRAY['available', 'rented', 'under_maintenance']))))`
- Check: `((monthly_rent > (0)))`
- Check: `(((property_type) = ANY ((ARRAY['apartment', 'flat']))))`
- Check: `((rooms > 0))`
- Check: `(((verification_status) = ANY ((ARRAY['draft', 'pending', 'changes_requested', 'approved', 'rejected']))))`

### property_photos

Photos of a property.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the property_photos table |
| `property_id` | bigint | yes | FK → properties.id |  | Property this photo belongs to; photos are deleted with the property |
| `file_path` | varchar(255) | yes |  |  | Where the image file is stored, relative to the storage disk |
| `sort_order` | smallint | yes |  | `'0'` | Display order in the photo gallery; 0 comes first |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

## Viewings and applications

### viewings

Each time a client asks to see a property, and what staff logged afterwards.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the viewings table |
| `property_id` | bigint | yes | FK → properties.id |  | Property being viewed |
| `client_user_id` | bigint | yes | FK → users.id |  | Client who requested the viewing |
| `status` | varchar(20) | yes |  | `'requested'` | requested, scheduled, completed, cancelled, declined or no_show |
| `requested_at` | timestamptz | yes |  | `now` | When the client asked for the viewing; filled in automatically |
| `scheduled_for` | timestamptz | no |  |  | Date and time the viewing is booked for |
| `scheduled_by_user_id` | bigint | no | FK → users.id |  | Receptionist or staff member who booked the slot |
| `feedback_category_id` | bigint | no | FK → feedback_categories.id |  | Main reason recorded after the viewing; required once completed |
| `staff_note` | text | no |  |  | Staff comment on the viewing; shown to the owner without the client name |
| `logged_by_user_id` | bigint | no | FK → users.id |  | Staff member who logged the outcome; required once completed |
| `logged_at` | timestamptz | no |  |  | When the outcome was logged; required once completed |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((((status) <> 'completed') OR ((feedback_category_id IS NOT NULL) AND (logged_by_user_id IS NOT NULL) AND (logged_at IS NOT NULL))))`
- Check: `(((status) = ANY ((ARRAY['requested', 'scheduled', 'completed', 'cancelled', 'declined', 'no_show']))))`

### applications

A client's request to rent a property for a chosen lease length.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the applications table |
| `reference` | varchar(16) | yes | unique | generated: `('H4R-APP-' || lpad((id), 4, '0'))` | Application reference generated by the database, e.g. H4R-APP-0042 |
| `property_id` | bigint | yes | FK → properties.id |  | Property the client is applying for |
| `client_user_id` | bigint | yes | FK → users.id |  | Client who made the application |
| `lease_term_id` | bigint | yes | FK → lease_terms.id |  | Lease length the client chose |
| `requested_start_date` | date | yes |  |  | Date the client wants the lease to start |
| `occupants` | smallint | yes |  |  | Number of people who will live there; must be above 0 |
| `employer` | varchar(120) | no |  |  | Client employer, if given |
| `income_bracket` | varchar(30) | no |  |  | Client income range, if given |
| `status` | varchar(20) | yes |  | `'draft'` | draft, submitted, under_review, approved, rejected or withdrawn |
| `submitted_at` | timestamptz | no |  |  | When the client submitted the application |
| `reviewed_at` | timestamptz | no |  |  | When staff approved or rejected the application |
| `reviewed_by_user_id` | bigint | no | FK → users.id |  | Staff member who approved or rejected it; empty until reviewed |
| `decision_note` | text | no |  |  | Reason given with the approval or rejection |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Unique: `(client_user_id, property_id) WHERE ((status) = ANY ((ARRAY['draft', 'submitted', 'under_review'])))`
- Check: `((occupants > 0))`
- Check: `(((status) = ANY ((ARRAY['draft', 'submitted', 'under_review', 'approved', 'rejected', 'withdrawn']))))`

## Leases and money

### leases

The contract between a client and an owner, executed by a staff member.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the leases table |
| `reference` | varchar(16) | yes | unique | generated: `('H4R-LSE-' || lpad((id), 4, '0'))` | Lease reference generated by the database |
| `application_id` | bigint | no | FK → applications.id |  | Approved application this lease came from; empty for renewals |
| `renewal_of_lease_id` | bigint | no | FK → leases.id |  | Earlier lease this one renews; empty for leases from an application |
| `property_id` | bigint | yes | FK → properties.id |  | Property being leased |
| `client_user_id` | bigint | yes | FK → users.id |  | Client renting the property |
| `owner_user_id` | bigint | yes | FK → users.id |  | Owner at signing; kept even if the property later changes hands |
| `executed_by_user_id` | bigint | yes | FK → users.id |  | Staff member responsible for the lease |
| `executed_at` | timestamptz | no |  |  | When staff executed the lease |
| `activated_at` | timestamptz | no |  |  | When the lease became active after both signatures |
| `terminated_at` | timestamptz | no |  |  | When the lease was ended early |
| `status` | varchar(20) | yes |  | `'drafting'` | drafting, pending_signature, active, expired, terminated, renewed or cancelled |
| `start_date` | date | yes |  |  | First day of the lease |
| `term_months` | smallint | yes |  |  | Lease length in months, copied from the chosen lease term; must be above 0 |
| `end_date` | date | yes |  | generated: `(((start_date + make_interval(months => (term_months))) - '1 day'))` | Last day of the lease, calculated by the database from start_date and term_months |
| `monthly_rent` | decimal(12,2) | yes |  |  | Monthly rent in MVR agreed in the lease; must be above 0 |
| `advance_amount` | decimal(12,2) | yes |  |  | Advance payment or security deposit in MVR; 0 or more |
| `payment_method_id` | bigint | yes | FK → payment_methods.id |  | How the client will pay the rent |
| `termination_reason` | text | no |  |  | Why the lease was ended early, if it was |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Unique: `(property_id) WHERE ((status) = ANY ((ARRAY['pending_signature', 'active'])))`
- Check: `((advance_amount >= (0)))`
- Check: `((monthly_rent > (0)))`
- Check: `((num_nonnulls(application_id, renewal_of_lease_id) = 1))`
- Check: `(((status) = ANY ((ARRAY['drafting', 'pending_signature', 'active', 'expired', 'terminated', 'renewed', 'cancelled']))))`
- Check: `((term_months > 0))`

### lease_signatures

One row per party (client, owner) when they sign a lease.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `lease_id` | bigint | yes | PK, FK → leases.id |  | Lease being signed |
| `party` | varchar(10) | yes | PK |  | Who signed: client or owner |
| `signed_by_user_id` | bigint | yes | FK → users.id |  | User who signed |
| `signed_at` | timestamptz | yes |  |  | When the lease was signed |
| `verification_method` | varchar(20) | yes |  |  | How the signer proved who they are |
| `ip_address` | varchar(45) | no |  |  | IP address the signature came from |
| `created_at` | timestamp | yes |  | `now` | When this signature was saved; signatures are never edited |

Rules:

- Primary key: (`lease_id`, `party`)
- Check: `(((party) = ANY ((ARRAY['client', 'owner']))))`

### payments

Rent and advance payments received against a lease.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the payments table |
| `reference` | varchar(16) | yes | unique | generated: `('H4R-RCT-' || lpad((id), 4, '0'))` | Receipt number generated by the database |
| `lease_id` | bigint | yes | FK → leases.id |  | Lease this payment is for |
| `kind` | varchar(10) | yes |  |  | rent or advance |
| `period_month` | date | no |  |  | Month the rent covers, stored as its first day; required for rent |
| `amount` | decimal(12,2) | yes |  |  | Amount paid in MVR; must be above 0 |
| `payment_method_id` | bigint | yes | FK → payment_methods.id |  | How the client paid |
| `bank_reference` | varchar(60) | yes |  |  | Deposit slip number or transaction ID |
| `received_on` | date | yes |  |  | Date the money was received |
| `recorded_by_user_id` | bigint | yes | FK → users.id |  | Staff member who recorded the payment |
| `payout_id` | bigint | no | FK → payouts.id |  | Owner payout this rent was included in; empty until paid out |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((amount > (0)))`
- Check: `(((kind) = ANY ((ARRAY['rent', 'advance']))))`
- Check: `((((kind) <> 'rent') OR (period_month IS NOT NULL)))`

### payouts

Monthly amount paid to an owner: rent collected minus the management fee.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the payouts table |
| `owner_user_id` | bigint | yes | FK → users.id |  | Owner receiving the payout |
| `period_month` | date | yes |  |  | Month covered, stored as its first day |
| `gross_amount` | decimal(12,2) | yes |  |  | Rent collected for the month in MVR, before the fee |
| `fee_amount` | decimal(12,2) | yes |  |  | Management fee taken in MVR |
| `net_amount` | decimal(12,2) | yes |  |  | Amount paid to the owner: gross minus fee |
| `fee_percent` | decimal(5,2) | yes |  |  | Management fee percentage at the time, kept so old statements stay correct |
| `status` | varchar(10) | yes |  | `'pending'` | pending or paid |
| `paid_at` | timestamptz | no |  |  | When the payout was sent to the owner |
| `bank_reference` | varchar(60) | no |  |  | Bank transfer reference for the payout |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Unique together: (`owner_user_id`, `period_month`)
- Check: `((net_amount = (gross_amount - fee_amount)))`
- Check: `(((status) = ANY ((ARRAY['pending', 'paid']))))`

## Support

### maintenance_requests

Repairs reported by owners or clients.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the maintenance_requests table |
| `property_id` | bigint | yes | FK → properties.id |  | Property that needs the repair |
| `lease_id` | bigint | no | FK → leases.id |  | Lease the request was raised under, if any |
| `raised_by_user_id` | bigint | yes | FK → users.id |  | Owner or client who reported the problem |
| `category` | varchar(30) | yes |  |  | Type of problem, e.g. plumbing or electrical |
| `description` | text | yes |  |  | What is wrong, as described by the person reporting it |
| `priority` | varchar(10) | yes |  | `'medium'` | How urgent the repair is |
| `status` | varchar(20) | yes |  | `'submitted'` | Progress of the request |
| `assigned_to_user_id` | bigint | no | FK → users.id |  | Staff member handling the repair |
| `resolved_at` | timestamptz | no |  |  | When the repair was completed |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `(((priority) = ANY ((ARRAY['low', 'medium', 'high', 'urgent']))))`
- Check: `(((status) = ANY ((ARRAY['submitted', 'triaged', 'assigned', 'in_progress', 'resolved', 'closed']))))`

### inquiries

Walk-in, phone and email inquiries logged by the front desk.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the inquiries table |
| `branch_id` | bigint | yes | FK → branches.id |  | Branch that received the inquiry |
| `logged_by_user_id` | bigint | yes | FK → users.id |  | Receptionist who logged it |
| `channel` | varchar(10) | yes |  |  | How the person got in touch |
| `contact_name` | varchar(120) | yes |  |  | Name of the person asking |
| `contact_phone` | varchar(20) | no |  |  | Phone number to call back, if given |
| `related_user_id` | bigint | no | FK → users.id |  | Matching account, if the person is already registered |
| `subject` | varchar(150) | yes |  |  | Short summary of the inquiry |
| `details` | text | no |  |  | Full notes from the conversation |
| `status` | varchar(10) | yes |  | `'open'` | open, routed to a specialist, or closed |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `(((channel) = ANY ((ARRAY['walk_in', 'phone', 'email']))))`
- Check: `(((status) = ANY ((ARRAY['open', 'routed', 'closed']))))`

### support_tickets

Issues routed to a specialist, usually from an inquiry.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the support_tickets table |
| `inquiry_id` | bigint | no | FK → inquiries.id |  | Inquiry this ticket was created from, if any |
| `branch_id` | bigint | yes | FK → branches.id |  | Branch responsible for the ticket |
| `opened_by_user_id` | bigint | yes | FK → users.id |  | Staff member who opened the ticket |
| `assigned_to_user_id` | bigint | no | FK → users.id |  | Specialist the ticket was routed to |
| `subject` | varchar(150) | yes |  |  | Short summary of the issue |
| `description` | text | no |  |  | Full description of the issue |
| `status` | varchar(20) | yes |  | `'open'` | Progress of the ticket |
| `resolved_at` | timestamptz | no |  |  | When the ticket was resolved |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `(((status) = ANY ((ARRAY['open', 'in_progress', 'resolved', 'closed']))))`

### escalations

Disputes passed to the branch manager about exactly one lease, application or maintenance request.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the escalations table |
| `branch_id` | bigint | yes | FK → branches.id |  | Branch whose manager handles the escalation |
| `raised_by_user_id` | bigint | yes | FK → users.id |  | Staff member who escalated the issue |
| `lease_id` | bigint | no | FK → leases.id |  | Lease in dispute; exactly one subject is set |
| `maintenance_request_id` | bigint | no | FK → maintenance_requests.id |  | Maintenance request in dispute; exactly one subject is set |
| `application_id` | bigint | no | FK → applications.id |  | Application in dispute; exactly one subject is set |
| `reason` | text | yes |  |  | Why the issue was escalated |
| `status` | varchar(10) | yes |  | `'open'` | open or resolved |
| `resolved_by_user_id` | bigint | no | FK → users.id |  | Branch manager who resolved it |
| `resolution` | text | no |  |  | How the escalation was resolved |
| `resolved_at` | timestamptz | no |  |  | When the escalation was resolved |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Check: `((num_nonnulls(lease_id, maintenance_request_id, application_id) = 1))`
- Check: `(((status) = ANY ((ARRAY['open', 'resolved']))))`

## Communication

### message_threads

A conversation about a property, supervised by its branch.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the message_threads table |
| `branch_id` | bigint | yes | FK → branches.id |  | Branch whose staff take part in the conversation |
| `property_id` | bigint | no | FK → properties.id |  | Property the conversation is about, if any |
| `subject` | varchar(150) | yes |  |  | Topic of the conversation |
| `created_by_user_id` | bigint | yes | FK → users.id |  | User who started the conversation |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

### thread_participants

Who takes part in a conversation (many-to-many).

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `thread_id` | bigint | yes | PK, FK → message_threads.id |  | Conversation the user takes part in |
| `user_id` | bigint | yes | PK, FK → users.id |  | User taking part |
| `last_read_at` | timestamptz | no |  |  | When the user last opened the conversation; used for unread counts |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

Rules:

- Primary key: (`thread_id`, `user_id`)

### messages

The messages in a conversation.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the messages table |
| `thread_id` | bigint | yes | FK → message_threads.id |  | Conversation the message belongs to |
| `sender_user_id` | bigint | yes | FK → users.id |  | User who wrote the message |
| `body` | text | yes |  |  | Message text |
| `created_at` | timestamp | yes |  | `now` | When the message was sent; messages are never edited |

### notifications

In-app alerts (Laravel notifications); the same alert is also emailed.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | uuid | yes | PK |  | Unique id of the notification |
| `type` | varchar(255) | yes |  |  | Notification class that created it |
| `notifiable_type` | varchar(255) | yes |  |  | Model the notification is for, normally App\Models\User |
| `notifiable_id` | bigint | yes |  |  | Id of that user; no foreign key because Laravel links it by type + id |
| `data` | text | yes |  |  | Message content as JSON |
| `read_at` | timestamptz | no |  |  | When the user read it; empty while unread |
| `created_at` | timestamp | no |  |  |  |
| `updated_at` | timestamp | no |  |  |  |

## System

### audit_log

Append-only record of important actions: who, what, when and from where.

| Field | Type | Required | Key | Default | Description |
|---|---|---|---|---|---|
| `id` | bigint | yes | PK | auto | Auto-incrementing primary key for the audit_log table |
| `user_id` | bigint | no | FK → users.id |  | User who did the action; empty for automatic system actions |
| `action` | varchar(60) | yes |  |  | What happened, e.g. property.approve, lease.execute |
| `table_name` | varchar(60) | no |  |  | Table that was changed |
| `record_id` | bigint | no |  |  | Id of the changed row |
| `old_values` | jsonb | no |  |  | Values before the change |
| `new_values` | jsonb | no |  |  | Values after the change |
| `ip_address` | varchar(45) | no |  |  | IP address the action came from |
| `created_at` | timestamp | yes |  | `now` | When it happened; entries are never changed |
