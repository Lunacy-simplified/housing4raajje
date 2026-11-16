# Detailed use case tabulations

Sixteen use cases covering the major functions. Each one describes what the application does today and is covered by automated tests.

| # | Use case | Main actor |
|---|---|---|
| [UC-01](#uc-01-register-as-an-owner-or-client) | Register as an owner or client | Owner, Client |
| [UC-02](#uc-02-sign-in) | Sign in | Any user |
| [UC-03](#uc-03-create-a-staff-account) | Create a staff account | System administrator |
| [UC-04](#uc-04-manage-own-profile) | Manage own profile | Any user |
| [UC-05](#uc-05-register-an-apartment) | Register an apartment | Owner |
| [UC-06](#uc-06-verify-a-property-listing) | Verify a property listing | Property specialist |
| [UC-07](#uc-07-change-a-propertys-availability) | Change a property's availability | Owner |
| [UC-08](#uc-08-search-for-a-property) | Search for a property | Client |
| [UC-09](#uc-09-arrange-and-log-a-viewing) | Arrange and log a viewing | Client, Receptionist, Property specialist |
| [UC-10](#uc-10-apply-to-rent-a-property) | Apply to rent a property | Client, Property specialist |
| [UC-11](#uc-11-execute-and-sign-a-lease) | Execute and sign a lease | Property specialist, Client, Owner |
| [UC-12](#uc-12-record-a-payment) | Record a payment | Receptionist, Property specialist |
| [UC-13](#uc-13-pay-out-an-owner) | Pay out an owner | System administrator |
| [UC-14](#uc-14-report-and-resolve-a-maintenance-request) | Report and resolve a maintenance request | Client, Owner, Staff |
| [UC-15](#uc-15-discuss-a-property-by-message) | Discuss a property by message | Client, Owner, Staff |
| [UC-16](#uc-16-handle-an-inquiry-and-escalate-a-dispute) | Handle an inquiry and escalate a dispute | Receptionist, Property specialist, Branch manager |

---

## UC-01: Register as an owner or client

| | |
|---|---|
| **Actors** | Property owner, Client |
| **Objective** | Create an account without staff help. |
| **Preconditions** | The person is not signed in and has an email address and a Maldivian mobile number not already registered. |
| **Trigger** | The person chooses "Register as a client" or "Register as a property owner" on the sign-in page. |
| **Normal flow** | 1. The system shows the registration form for the chosen type.<br>2. The person enters name, email, mobile number, password (twice) and accepts the terms. An owner also enters a National ID or passport number.<br>3. The system checks the details, creates the user and the matching profile together, and sends a verification email.<br>4. The system signs the person in and opens their dashboard. |
| **Alternative flows** | 3a. A field is missing or wrong (for example the email is already registered): the form is shown again with a message under that field; the password is not refilled.<br>3b. The verification email cannot be sent: the account is still created and the person can ask for the email again from the dashboard.<br>2a. More than 10 attempts in a minute: the system refuses further attempts for a minute. |
| **Postconditions** | A user with the role owner or client exists, with an empty profile, signed in, email not yet verified. |
| **Business rules** | Staff cannot register here. Mobile numbers are 7 digits starting with 7 or 9. Passwords have at least 8 characters with letters and numbers and are stored only as a hash. An owner's ID number must be unique. |

## UC-02: Sign in

| | |
|---|---|
| **Actors** | Any user |
| **Objective** | Reach the portal that belongs to the user's role. |
| **Preconditions** | The user has an active account. |
| **Trigger** | The user opens the application. |
| **Normal flow** | 1. The user enters email and password.<br>2. The system checks them and that the account is active.<br>3. For an administrator, a branch manager, or anyone who switched on sign-in codes: the system emails a 6-digit code and asks for it. The user enters the code.<br>4. The system signs the user in, records the time, and opens the dashboard for their role. |
| **Alternative flows** | 2a. Wrong details or a deactivated account: an error is shown on the email field. After 5 failed attempts in a minute the system pauses sign-in.<br>3a. The browser was marked as trusted in the last 30 days: the code step is skipped.<br>3b. Wrong or expired code: an error is shown; after 5 wrong tries the code stops working and a new one must be requested.<br>3c. The user ticks "Don't ask for a code on this device for 30 days": the browser is remembered. |
| **Postconditions** | The user is signed in with a fresh session; `last_login_at` is updated. |
| **Business rules** | Codes last 10 minutes and work once; only their hash is stored. Sign-in codes are mandatory for administrators and managers. A deactivated user is signed out on their next request. |

## UC-03: Create a staff account

| | |
|---|---|
| **Actors** | System administrator, new staff member |
| **Objective** | Give a new employee an account with the right role and branch. |
| **Preconditions** | The administrator is signed in. |
| **Trigger** | The administrator chooses "Create staff". |
| **Normal flow** | 1. The administrator enters name, work email, mobile number, role, branch, and optionally job title and hire date.<br>2. The system creates the user and staff profile, generates the next staff code (H4R-STF-0001, ...), and emails an invitation link.<br>3. The staff member opens the link, chooses a password, and can then sign in. |
| **Alternative flows** | 1a. Role is System Administrator: the branch must be left empty. Any other role must have an active branch.<br>2a. The invitation email fails: the account is still created and the administrator can use "Resend invite".<br>3a. The link is older than 24 hours or already used: an error is shown and the administrator sends a new one. |
| **Postconditions** | An active staff account exists; after the invite is accepted the email counts as verified. |
| **Business rules** | Only administrators create staff. The administrator never sets or sees the password. An administrator cannot change their own role or deactivate themselves. |

## UC-04: Manage own profile

| | |
|---|---|
| **Actors** | Any user |
| **Objective** | Keep personal details, payout details or rental preferences up to date. |
| **Preconditions** | The user is signed in. |
| **Trigger** | The user opens "Profile". |
| **Normal flow** | 1. The system shows the user's details.<br>2. The user changes name or mobile number. An owner can add ID number and bank details for payouts. A client can add ID number, sex, date of birth, maximum monthly rent, rooms wanted, preferred lease length and the areas they want to live in.<br>3. The user may switch "Ask for an emailed code every time I sign in" on or off.<br>4. The system saves the changes. |
| **Alternative flows** | 2a. The mobile number changes: phone verification is reset and any code sent to the old number stops working.<br>2b. A value is invalid (for example a date of birth in the future): the form is shown again with the error. |
| **Postconditions** | The profile is updated. A client's budget and areas are now used by the lease matchmaker. |
| **Business rules** | Administrators and managers cannot switch sign-in codes off. Users can only edit their own profile. |

## UC-05: Register an apartment

| | |
|---|---|
| **Actors** | Property owner |
| **Objective** | Put an apartment forward for listing. |
| **Preconditions** | The owner is signed in and has verified their email. |
| **Trigger** | The owner chooses "Register a property". |
| **Normal flow** | 1. The owner enters property type (apartment or flat), rooms, house or plot name, road, district, optional unit number and floor, and the monthly rent.<br>2. The system saves the property as a draft, gives it a reference (H4R-APT-0001) and sets its branch from the district.<br>3. The owner adds up to 6 photos.<br>4. The owner chooses "Submit for review".<br>5. The system marks it pending and it appears in that branch's verification queue. |
| **Alternative flows** | Pre-a. The email is not verified: the owner is sent to the verification page.<br>1a. A value is invalid (rooms below 1, rent of 0): the form is shown again with the error.<br>5a. Staff send it back for changes: the owner edits and submits again. |
| **Postconditions** | A property exists with status pending, linked to the owner and to one branch. |
| **Business rules** | The branch is never chosen by hand; it follows the district. Only drafts and listings sent back for changes can be edited. An owner sees only their own properties. |

## UC-06: Verify a property listing

| | |
|---|---|
| **Actors** | Property specialist (or branch manager) |
| **Objective** | Make sure a listing is genuine and complete before clients can see it. |
| **Preconditions** | A property in the specialist's branch is pending. |
| **Trigger** | The specialist opens "Properties" and picks a listing from "To check". |
| **Normal flow** | 1. The system shows the property's details, photos and the owner's name, phone and ID number.<br>2. The specialist chooses "Approve and publish".<br>3. The system marks it approved and available, records who approved it and when, and writes the audit log.<br>4. The system notifies the owner.<br>5. The lease matchmaker finds clients whose maximum rent covers the rent, who want that area, and whose room count matches, and notifies them. |
| **Alternative flows** | 2a. "Send back for changes" or "Reject": a note to the owner is required; the owner is notified with the note.<br>1a. The property belongs to another branch: the system answers "not found".<br>2b. A receptionist tries to decide: the system refuses. |
| **Postconditions** | The property is approved and appears in client search, or is returned to the owner, or is rejected. |
| **Business rules** | Staff only ever see their own branch's properties. A property has an availability status only once approved. Matching clients are also listed on the staff property page. |

## UC-07: Change a property's availability

| | |
|---|---|
| **Actors** | Property owner |
| **Objective** | Take a property out of search during repairs, or put it back. |
| **Preconditions** | The property is approved and not rented. |
| **Trigger** | The owner opens the property and chooses "Mark as under maintenance" or "Mark as available". |
| **Normal flow** | 1. The system changes the availability.<br>2. An under-maintenance property disappears from client search; an available one reappears. |
| **Alternative flows** | 1a. The property is rented: the switch is not offered, and the page shows the current lease (start, length, end, whether this month's rent is paid) with a link to it. |
| **Postconditions** | The availability status is updated. |
| **Business rules** | "Rented" is set only by the system when a lease becomes active, and cleared when the lease ends. |

## UC-08: Search for a property

| | |
|---|---|
| **Actors** | Client |
| **Objective** | Find properties that fit the client's needs. |
| **Preconditions** | The client is signed in. |
| **Trigger** | The client opens "Search". |
| **Normal flow** | 1. The system lists approved, available properties, cheapest first.<br>2. The client filters by location (Malé, Hulhumalé, Villingili), number of rooms and maximum rent.<br>3. The client opens a property to see its details and photos. |
| **Alternative flows** | 2a. Nothing matches: the system suggests a higher budget or another location.<br>3a. The owner's phone is verified: it is shown on the listing; otherwise it is hidden. |
| **Postconditions** | None; searching changes nothing. |
| **Business rules** | Pending, rejected, rented and under-maintenance properties never appear in search. |

## UC-09: Arrange and log a viewing

| | |
|---|---|
| **Actors** | Client, Receptionist, Property specialist |
| **Objective** | Show a property to a client and capture why they did or did not want it. |
| **Preconditions** | The property is approved and available. |
| **Trigger** | The client chooses "Request a viewing" on a property. |
| **Normal flow** | 1. The system records the request for the property's branch.<br>2. A receptionist books a date and time; the client is notified.<br>3. A staff member shows the client the property.<br>4. The specialist records the outcome as completed, picks the main feedback reason (for example "Financial constraint") and types what the client said (for example "Little expensive").<br>5. The owner's property page shows one more viewing, the updated share of each rejection reason, and a new line in the viewing history. |
| **Alternative flows** | 1a. The client already has an open viewing for the property: the request is refused.<br>2a. The client cancels before the viewing.<br>4a. The client did not come or declined: the specialist records "No show", "Declined" or "Cancelled"; no feedback reason is needed. |
| **Postconditions** | The viewing is closed with its outcome; a completed one has a reason, the staff member and the time. |
| **Business rules** | A completed viewing must have a feedback reason. The owner never sees the client's name. Only specialists and managers record outcomes. |

## UC-10: Apply to rent a property

| | |
|---|---|
| **Actors** | Client, Property specialist |
| **Objective** | Ask to rent a property for a fixed period and get a decision. |
| **Preconditions** | The client has verified their email. The property is approved and available. |
| **Trigger** | The client fills in "Apply to rent" on the property page. |
| **Normal flow** | 1. The client chooses a lease length (6 months, 1, 2 or 5 years), a start date and the number of occupants, and may add employer and income range.<br>2. The system saves the application with a reference (H4R-APP-0001) for the branch to review.<br>3. The specialist reviews it, with the client's budget shown, and approves it.<br>4. The system records who decided and when, and notifies the client. |
| **Alternative flows** | Pre-a. The email is not verified: the client is sent to the verification page.<br>1a. The client already has an open application for this property: refused.<br>2a. The client withdraws before a decision.<br>3a. The specialist rejects: a reason is required and is shown to the client. |
| **Postconditions** | The application is approved (ready for a lease), rejected or withdrawn. |
| **Business rules** | One open application per client and property (also enforced by the database). Start dates cannot be in the past. |

## UC-11: Execute and sign a lease

| | |
|---|---|
| **Actors** | Property specialist, Client, Owner |
| **Objective** | Turn an approved application into a signed, active lease. |
| **Preconditions** | An approved application without a lease exists in the specialist's branch. |
| **Trigger** | The specialist chooses "Create lease" for the application. |
| **Normal flow** | 1. The specialist confirms the rent start date, monthly rent, advance payment or deposit, and payment method. The length comes from the application.<br>2. The system creates the lease with a reference (H4R-LSE-0001), records the specialist's staff ID and the date, calculates the end date, and asks the client and the owner to sign.<br>3. The client reads the lease and signs by entering their password.<br>4. The owner reads the lease and signs by entering their password.<br>5. The system marks the lease active, marks the property rented, and notifies both parties. |
| **Alternative flows** | 2a. The property already has a live lease: refused.<br>3a. Wrong password: the signature is refused.<br>3b. Someone who is not the client or the owner opens the lease: "not found".<br>5a. Before both have signed, the specialist cancels the lease with a reason.<br>5b. Later, the specialist ends an active lease early with a reason; the property becomes available again. |
| **Postconditions** | An active lease exists with two signatures (who, when, from which address); the property is rented and out of search. |
| **Business rules** | FR-1: the lease stores the executing staff member, date and fixed length. End date = start date + length − 1 day. One live lease per property. Each party signs once. A daily job marks leases expired after their end date and reminds both parties 30 days before. |

## UC-12: Record a payment

| | |
|---|---|
| **Actors** | Receptionist, Property specialist |
| **Objective** | Record rent or an advance received from a client. |
| **Preconditions** | The lease is active and belongs to the staff member's branch. |
| **Trigger** | The client pays by bank deposit or transfer and the staff member opens the lease. |
| **Normal flow** | 1. The staff member chooses rent or advance, the month the rent covers, the amount, the payment method, the slip or transaction number and the date received.<br>2. The system saves the payment with a receipt number (H4R-RCT-0001) and who recorded it, and writes the audit log.<br>3. The system notifies the client and the owner. |
| **Alternative flows** | 1a. Rent without a month, an amount of 0, or a date in the future: the form is shown again with the error. |
| **Postconditions** | The payment appears on the lease for staff, client and owner, and counts toward the owner's next payout. |
| **Business rules** | Rent payments must name the month they cover. Payments are never edited or deleted. FR-2: both parties are alerted. |

## UC-13: Pay out an owner

| | |
|---|---|
| **Actors** | System administrator |
| **Objective** | Pay each owner the rent collected for a month, minus the management fee. |
| **Preconditions** | Rent payments for the month have been recorded. |
| **Trigger** | The administrator opens "Owner payouts" and chooses a month. |
| **Normal flow** | 1. The system gathers the month's rent payments not yet paid out, per owner.<br>2. For each owner it creates a pending payout: rent collected, fee (the percentage in Settings), and the net amount.<br>3. The administrator transfers the money using the owner's bank details shown on the page.<br>4. The administrator enters the transfer reference and marks the payout paid.<br>5. The system notifies the owner. The owner's Earnings page shows it and the owner can download a CSV statement. |
| **Alternative flows** | 1a. No unpaid rent for that month: the system says so.<br>2a. A late payment arrives for a month whose payout is still pending: running the step again adds it to that payout.<br>2b. The payout was already paid: it is left unchanged. |
| **Postconditions** | One payout per owner and month, marked paid with its reference; each payment is linked to its payout. |
| **Business rules** | Net = gross − fee (enforced by the database). The fee percentage is stored on each payout so old statements stay correct when the setting changes. Advance payments are not paid out. |

## UC-14: Report and resolve a maintenance request

| | |
|---|---|
| **Actors** | Client (tenant), Owner, Branch staff |
| **Objective** | Get a problem in a property fixed. |
| **Preconditions** | The client has an active lease on the property, or the owner's property is approved. |
| **Trigger** | The user opens "Maintenance" and reports a problem. |
| **Normal flow** | 1. The user chooses the property, the type of problem and how urgent it is, and describes it.<br>2. The system saves the request for the property's branch and notifies the other party (the owner when a tenant reports, the tenant when the owner reports).<br>3. A staff member picks it up and moves it through triaged, assigned, in progress and resolved.<br>4. The person who reported it is notified at each change. |
| **Alternative flows** | 1a. The user has no property they may report on: the form is not offered.<br>3a. Staff cannot settle it: they escalate it to the branch manager with a reason (UC-16). |
| **Postconditions** | The request is resolved or closed, with who handled it and when. |
| **Business rules** | A client can only report on a property they currently rent. Staff handle only their own branch's requests. FR-2: new requests alert the other party. |

## UC-15: Discuss a property by message

| | |
|---|---|
| **Actors** | Client, Owner, Branch staff |
| **Objective** | Ask and answer questions about a property with staff supervision. |
| **Preconditions** | The property is approved. |
| **Trigger** | The client fills in "Ask a question" on a property page. |
| **Normal flow** | 1. The client enters a subject and a message.<br>2. The system creates a conversation linked to the property and its branch, with the client and the owner as participants, and notifies the owner.<br>3. The owner replies.<br>4. Any staff member of that branch can read the whole conversation and reply. |
| **Alternative flows** | 2a. Someone who is not a participant, or staff from another branch, opens the conversation: "not found". |
| **Postconditions** | The messages are kept in order and are never edited. |
| **Business rules** | Every conversation is visible to the property's branch staff (mediated supervision). Messages are limited to 2,000 characters. |

## UC-16: Handle an inquiry and escalate a dispute

| | |
|---|---|
| **Actors** | Receptionist, Property specialist, Branch manager |
| **Objective** | Make sure every inquiry and dispute reaches someone who can deal with it. |
| **Preconditions** | The staff member is signed in and belongs to a branch. |
| **Trigger** | Someone walks in, phones or emails the branch; or a staff member cannot settle an issue. |
| **Normal flow** | 1. The receptionist logs the inquiry: channel, name, phone, subject and notes.<br>2. The receptionist routes it to a specialist in the same branch. The system creates a support ticket and notifies the specialist.<br>3. The specialist works the ticket and marks it resolved.<br>4. For a disputed lease, application or maintenance request, a staff member chooses "Escalate to manager" and gives a reason.<br>5. The branch manager sees it under "Escalations", decides, and records the resolution. The staff member is notified. |
| **Alternative flows** | 2a. The inquiry needs no follow-up: the receptionist closes it without a ticket.<br>1a. The caller is a registered owner or client with wrong details: the receptionist corrects their name or phone under "Contacts"; a changed phone must be verified again.<br>5a. A manager from another branch tries to resolve it: "not found". |
| **Postconditions** | The inquiry is routed or closed; the escalation is resolved with who decided and what. |
| **Business rules** | Inquiries, tickets and escalations stay inside one branch. An escalation is about exactly one lease, application or maintenance request. Contact changes and resolutions are written to the audit log. |
