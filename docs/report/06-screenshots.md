# Application screenshots

Taken from the running application with the demo data (`php artisan migrate:fresh --seed`), at 1440 pixels wide in light mode.
The last one shows dark mode, which the application switches to automatically when the device is set to dark.

## Authentication

**1. Sign in.** One sign-in page for every role, with links to register as a client or an owner.

![Sign in](screenshots/01-sign-in.png)

**2. Register as owner.** The segmented control switches between the client and owner forms; owners also give an ID or passport number.

![Register as owner](screenshots/02-register-owner.png)

**19. Sign-in code (MFA).** Administrators and managers enter a 6-digit code sent by email after their password.

![Sign-in code](screenshots/19-sign-in-code.png)

## Owner portal

**3. Owner dashboard.** Counts that link to the lists behind them.

![Owner dashboard](screenshots/03-owner-dashboard.png)

**4. Apartment Registration Form.** The digital version of the paper form. The branch is set from the district.

![Apartment registration form](screenshots/04-apartment-registration-form.png)

**5. Property dashboard.** Availability switch, total viewings, rejection reasons as shares, and the anonymized viewing history.

![Owner property dashboard](screenshots/05-owner-property-dashboard.png)

**6. Earnings.** Monthly rent collected, management fee and amount paid, with a CSV statement download.

![Owner earnings](screenshots/06-owner-earnings.png)

**7. Lease agreement.** Terms, the staff member who executed it, both signatures and the payments received.

![Lease agreement](screenshots/07-lease-agreement.png)

## Client portal

**8. Property search.** Filters for location, rooms and maximum rent. Only approved, available properties appear.

![Client search](screenshots/08-client-search.png)

**9. Property page.** Details, viewing status, application status and a message form. The owner's phone shows only when verified.

![Client property page](screenshots/09-client-property-page.png)

**10. Applications.** Each application with its chosen lease length and status.

![Client applications](screenshots/10-client-applications.png)

**11. Messages.** A conversation about a property between the client, the owner and branch staff.

![Messages](screenshots/11-messages.png)

**12. Profile.** Personal details, rental preferences and the optional sign-in code switch.

![Client profile](screenshots/12-client-profile.png)

## Staff portal

**13. Staff dashboard.** The branch's work queues.

![Staff dashboard](screenshots/13-staff-dashboard.png)

**14. Property verification.** A specialist reviews a submitted listing and approves it, sends it back or rejects it.

![Property verification](screenshots/14-property-verification.png)

**15. Viewing log.** Booking a date and time, then recording the outcome, the standard feedback reason and the client's comment.

![Viewing log](screenshots/15-viewing-log.png)

**16. Lease and payments.** The contract, payments received, and the form to record a rent or advance payment.

![Lease and payments](screenshots/16-lease-and-payments.png)

**17. Front-desk inquiries.** Logging an inquiry and routing it to a specialist as a support ticket.

![Front desk inquiries](screenshots/17-front-desk-inquiries.png)

**18. Maintenance.** Requests for the branch's properties, with status updates and escalation to the manager.

![Maintenance](screenshots/18-maintenance.png)

## Manager portal

**20. Branch report.** Occupancy, rent collected this month and each staff member's activity.

![Branch report](screenshots/20-branch-report.png)

**21. Branch staff.** The manager's own branch staff and their salaries.

![Branch staff](screenshots/21-branch-staff.png)

## Admin portal

**22. Staff accounts.** Filterable list of all staff, with invitation and account status.

![Admin staff](screenshots/22-admin-staff.png)

**23. Owner payouts.** Preparing a month's payouts and marking them as paid.

![Owner payouts](screenshots/23-owner-payouts.png)

**24. Master data.** Districts, lease lengths, payment methods and viewing feedback reasons.

![Master data](screenshots/24-master-data.png)

**25. Audit log.** Who did what, when and from which address.

![Audit log](screenshots/25-audit-log.png)

## Dark mode

**26. Staff property page in dark mode,** including the lease matchmaker's list of matching clients.

![Dark mode](screenshots/26-dark-mode.png)

## Small screens

Below 900px the menu folds behind a Menu button and each table row becomes a stacked block. Taken at 360px wide.

![Phone: an owner's property list](screenshots/27-phone-property-list.png)

![Phone: the client menu opened](screenshots/28-phone-menu.png)
