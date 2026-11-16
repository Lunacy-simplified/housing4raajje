# Technical documentation

The documentation package the [assignment brief](../assignment-brief.md) asks for in section 4.

| Brief | Document | Notes |
|---|---|---|
| 4A | [Conceptual diagram](01-conceptual-diagram.md) | Actors, portals, modules and outside services, plus the rental process from listing to payout |
| 4B | [Data dictionary](02-data-dictionary.md) | Every table and field, generated from the database |
| 4C | [Relational schema](03-relational-schema.md) | ER diagram, relational notation, foreign keys, database rules |
| 4D | [Use case tabulations](04-use-cases.md) | 16 use cases covering the major functions |
| 4E | [Scrum meeting logs](05-scrum-logs.md) | Template and repository timeline. **The team must add the real meeting records.** |
| 4F | [Application screenshots](06-screenshots.md) | 26 captioned screens from the running application |

The diagrams are written in Mermaid, which GitHub draws automatically. To put one in a Word or PDF report, open the file on
GitHub and take a screenshot, or paste the diagram code into <https://mermaid.live> and export an image.

## Where each requirement in the brief is met

| Brief | Requirement | Where in the application |
|---|---|---|
| 1 | Six roles with role-based access | Role middleware on every route group; staff queries limited to their own branch |
| 2A | Identity gateway, separate owner/client/staff sign-up | Sign in, register as client or owner, staff invited by the administrator |
| 2A | Profile self-management | Profile page: owner bank details and ID; client sex, date of birth, budget, areas |
| 2A | MFA, mandatory for admin roles, optional for others | Emailed sign-in code for administrators and managers; a switch on the profile page for everyone else |
| 2B | Digital Apartment Registration Form, branch from district | Owner: Properties → Register a property |
| 2B | Listing tagged with the owner's verified phone | Shown on the listing only once the phone is verified |
| 2B | Availability toggle and lease linkage | Owner property page: Available / Under maintenance; Rented is set by the lease and shows the current lease |
| 2B | View counter, rejection reasons, anonymized timeline | Owner property page |
| 2B | Earnings and payout dashboard with statements | Owner: Earnings, with CSV download |
| 2C | Search by rooms, budget and location | Client: Search |
| 2C | Lease term configuration | Lease length chosen on the application (6 months to 5 years; editable in Master data) |
| 2C | Communication hub with staff supervision | Messages: client and owner talk; the property's branch staff read and reply |
| 2D | Verification queue | Staff: Properties and Applications |
| 2D | Branch-level segmentation | Staff see only their branch's properties, clients' applications, viewings, leases, inquiries and conversations |
| 2D | Automated lease matchmaker | Matching clients listed on the staff property page and notified when a listing is approved |
| 2D | Viewing log, feedback and comments | Staff: Viewings |
| 2D | Advance payment and payment method | Captured when the lease is created |
| 2D | Dual digital sign-off | Client and owner each sign by re-entering their password |
| 2D | Lease timeline engine | End date calculated by the database; daily job expires leases |
| FR-1 | Lease creation logs staff ID, date and duration | `leases.executed_by_user_id`, `executed_at`, `term_months`; shown on the lease |
| FR-2 | Alerts for renewals, payments and maintenance | Email and in-app notifications; renewal reminders from the daily job |
| 1A | Admin: configuration, master data, audit log, staff accounts | Admin: Settings, Master data, Audit log, Staff |
| 1A | Manager: staff performance, reports, escalations | Manager: Branch report, Branch staff, Escalations |
| 1A | Receptionist: inquiries, appointments, contacts, ticket routing | Staff: Inquiries, Viewings (booking), Contacts, Support tickets |

## Known limits of this first version

Kept out on purpose so the first version stays small. Each is a candidate for a later sprint.

- **Lease renewal.** The database supports a lease that renews an earlier one; the screens only send the reminder.
- **Database backups.** Done with PostgreSQL's own tools (see the main README), not from an admin screen.
- **SMS alerts.** Phone verification uses SMS. Other alerts go by email and in-app; the SMS channel is ready to reuse.
- **Permissions table.** Access is decided by role. The `permissions` tables are in place for finer control later.
- **Online payments.** Staff record payments received by bank deposit or transfer; there is no payment gateway.
