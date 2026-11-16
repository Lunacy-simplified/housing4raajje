# Conceptual diagram

## 1. Actors, system components and interactions

Six kinds of user reach the system through four portals. Every portal talks to the same application and the same database.
What a user can see is decided by their role and, for staff, by their branch.

```mermaid
flowchart LR
    subgraph External["External users"]
        Owner([Property owner])
        Client([Client / tenant])
    end

    subgraph Staff["Internal staff (about 20, in 3 branches)"]
        Reception([Receptionist])
        Specialist([Property specialist])
        Manager([Branch manager])
        Admin([System administrator])
    end

    subgraph System["Housing_4_Raajje web application"]
        direction TB
        Gateway["Identity gateway<br/>sign in, registration, MFA,<br/>email and phone verification"]

        subgraph Portals["Portals (role-based access)"]
            OwnerPortal["Owner portal<br/>register property, availability,<br/>viewing feedback, earnings"]
            ClientPortal["Client portal<br/>search, viewings, applications,<br/>leases, maintenance"]
            StaffPortal["Staff portal (own branch only)<br/>verification queue, viewings, leases,<br/>payments, front desk"]
            ManagerPortal["Manager portal<br/>branch report, salaries, escalations"]
            AdminPortal["Admin portal<br/>staff accounts, payouts,<br/>master data, settings, audit log"]
        end

        subgraph Core["Shared services"]
            Match["Lease matchmaker"]
            Messages["Messaging hub<br/>(staff supervised)"]
            Notify["Notifications"]
            Audit["Audit trail"]
            Daily["Daily job<br/>lease expiry, renewal reminders"]
        end

        DB[("PostgreSQL database<br/>37 tables")]
    end

    Mail[/"Email server"/]
    SMS[/"SMS gateway (MsgOwl)"/]

    Owner --> Gateway
    Client --> Gateway
    Reception --> Gateway
    Specialist --> Gateway
    Manager --> Gateway
    Admin --> Gateway

    Gateway --> OwnerPortal
    Gateway --> ClientPortal
    Gateway --> StaffPortal
    Gateway --> ManagerPortal
    Gateway --> AdminPortal

    OwnerPortal --> DB
    ClientPortal --> DB
    StaffPortal --> DB
    ManagerPortal --> DB
    AdminPortal --> DB

    StaffPortal --> Match
    OwnerPortal <--> Messages
    ClientPortal <--> Messages
    StaffPortal <--> Messages
    Core --> DB
    Daily --> Notify
    Notify --> Mail
    Gateway --> Mail
    Gateway --> SMS
    Mail -.-> Owner
    Mail -.-> Client
    SMS -.-> Client
```

| Component | What it does |
|---|---|
| Identity gateway | One sign-in for everyone. Owners and clients register themselves; staff are invited by the administrator. Administrators and managers must enter an emailed code; others may switch that on. |
| Owner portal | Apartment Registration Form, availability switch, viewing statistics and rejection reasons, earnings and statements. |
| Client portal | Property search, viewing requests, rental applications, lease signing, payment history, maintenance requests. |
| Staff portal | The work of one branch: checking listings and applications, booking and logging viewings, executing leases, recording payments, front-desk inquiries. |
| Manager portal | Branch occupancy and income report, staff activity and salaries, resolving escalated disputes. |
| Admin portal | Staff accounts, owner payouts, master data, global settings, audit log. |
| Lease matchmaker | Compares a newly approved listing with each client's maximum rent, preferred area and room count, and tells the clients who fit. |
| Messaging hub | Conversations about a property between client and owner, visible to that branch's staff. |
| Notifications | In-app and email alerts for decisions, signatures, payments, maintenance and renewals. |
| Audit trail | Append-only log of approvals, leases, payments, payouts and settings changes. |
| Daily job | Marks finished leases as expired, frees the property, and sends renewal reminders. |

## 2. The rental process

How one apartment moves from registration to monthly payouts, and who acts at each step.

```mermaid
flowchart TD
    A["Owner registers apartment<br/>(draft)"] --> B["Owner submits<br/>branch set from district"]
    B --> C{"Specialist checks listing"}
    C -- "send back" --> A
    C -- "reject" --> X1([Rejected])
    C -- "approve" --> D["Live and Available<br/>matching clients notified"]
    D --> E["Client finds it in search"]
    E --> F["Client requests viewing"]
    F --> G["Receptionist books date and time"]
    G --> H["Specialist logs outcome<br/>and feedback reason"]
    H --> I["Owner sees view count<br/>and rejection reasons"]
    E --> J["Client applies<br/>chooses lease length"]
    H --> J
    J --> K{"Specialist reviews application"}
    K -- "reject" --> X2([Rejected, client told why])
    K -- "approve" --> L["Specialist creates lease<br/>rent, advance, payment method"]
    L --> M["Client signs"]
    L --> N["Owner signs"]
    M --> O{"Both signed?"}
    N --> O
    O -- "yes" --> P["Lease Active<br/>property Rented"]
    P --> Q["Staff record rent payments<br/>client and owner notified"]
    Q --> R["Administrator prepares monthly payout<br/>rent minus management fee"]
    R --> S["Owner paid, statement available"]
    P --> T["Daily job: renewal reminder,<br/>then Expired at end date"]
    T --> D
```

## 3. Who can do what

| Action | Owner | Client | Receptionist | Specialist | Manager | Admin |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Register and edit own properties | ✓ | | | | | |
| Search properties, request viewings, apply | | ✓ | | | | |
| Sign a lease they are party to | ✓ | ✓ | | | | |
| Report maintenance, send messages | ✓ | ✓ | | | | |
| See branch properties, viewings, leases | | | ✓ | ✓ | ✓ | |
| Book viewings, log inquiries, update contacts, record payments | | | ✓ | ✓ | ✓ | |
| Approve listings and applications, log viewing feedback, create and end leases | | | | ✓ | ✓ | |
| Branch report, staff salaries, resolve escalations | | | | | ✓ | |
| Staff accounts, payouts, master data, settings, audit log | | | | | | ✓ |

Staff rows apply to the staff member's own branch only.
