# Housing_4_Raajje: assignment brief

The full project brief, kept here as the source of truth for scope. When a feature question comes up, check it against this file.

## Introduction

In the rapidly evolving real estate market of the Maldives, efficient property management is vital for bridging the gap between property owners and prospective tenants across major urban hubs like Malé, Hulhumalé, and Villingili. Housing_4_Raajje is a specialized property management firm operating across three regional branches with a workforce of approximately 20 staff members. The enterprise specializes in matching local property owners who want to monetize their furnished apartments with clients seeking verified, fixed-term accommodations.

Currently, the organization relies on localized, manual operations, utilizing physical paper forms to handle critical tasks like staff provisioning, apartment registrations, client onboarding, and lease tracking. To modernize its infrastructure, improve service delivery, and eliminate operational friction, Housing_4_Raajje is transitioning its business model into an integrated, multi-tenant web application. This system will digitize traditional workflows into an enterprise-grade database platform, introducing tailored digital portals and robust Role-Based Access Control (RBAC) for clients, owners, and stratified tiers of administrative and operational staff.

## Problem statement

The current operational framework of Housing_4_Raajje is restricted by its reliance on paper-based records and fragmented communication channels across its Malé, Hulhumalé, and Villingili branches. This dependency introduces significant operational inefficiencies, data silos, and a high risk of transactional errors. Specifically, the organization faces four critical bottlenecks:

- **High Administrative Burden on Owners:** Property owners lack a direct, transparent channel to list their units, track active occupancy statuses, or monitor rental income, forcing them to rely heavily on manual interactions with staff specialists.
- **Inefficient Client Discovery:** Prospective tenants cannot dynamically search or filter available properties based on their specific room counts, geographic preferences, or budget caps. This leads to delayed matching and prolonged vacancies.
- **Operational Inefficiencies and Silos:** With 20 staff members spread across three distinct geographic branches, tracking which staff member registered an apartment or managed a client relationship is highly error-prone under a manual system. This makes branch-level performance reporting incredibly complex.
- **Lack of Granular Access Control:** The current setup does not support data segregation by staff role (e.g., separating high-level managerial actions and salary oversight from front-desk reception tasks), creating severe data security and operational governance risks.

Without a centralized, secure, and relational web application, Housing_4_Raajje cannot scale its operations efficiently, leaving its data vulnerable to inconsistencies and limiting its ability to provide a seamless, modern experience for both apartment owners and clients.

## 1. User roles

To support the legacy workflows and the required administrative granularities, the system will implement Role-Based Access Control (RBAC) across three primary umbrellas: Internal Operations, Owners, and Clients.

### A. Staff roles

- **System Administrator:** Full, unrestricted access to the system. Manages system configurations, global parameters, master data, audits logs, database backups, and provisions internal staff accounts.
- **Branch Manager:** Inherits regional control over a specific branch (e.g., Malé, Hulhumalé, or Villingili). Oversees branch-specific staff performance, generates regional financial/occupancy reports, and resolves escalated disputes.
- **Staff / Property Specialist:** The primary operational tier (e.g., "Ahmed Afzal" in the forms). Responsible for vetting and processing Apartment Registration Forms and Client Registration Forms, managing viewings, and executing lease agreements.
- **Receptionist / Front Desk:** Handles initial inquiries, schedules appointments/viewings, updates basic client/owner contact profiles, and routes support tickets to specialized staff.

### B. External roles

- **Property Owner:** Individuals (from Malé, Hulhumalé, or Villingili) looking to monetize their furnished apartments. They can list properties, track rental income, view active lease states, and interact with the staff regarding maintenance or contracts.
- **Client (Tenant):** Individuals looking to lease properties for a fixed duration. They can browse active inventories based on preferences (location, budget, room count), submit rental applications, sign digital leases, track payments, and log maintenance requests.

## 2. Core features

### A. Authentication

- **Universal Identity Gateway:** Secure login and registration forms split into distinct workflows for owners, clients, and internal staff (staff enrollment is restricted to admin provisioning).
- **Profile Self-Management**
  - Owners: Input personal data, National ID/Passport, bank details for payouts, and verified contact numbers (e.g., owner phone).
  - Clients: Input demographic details (full name, sex, DOB), maximum rent preferences, and current contact information.
- **Multi-Factor Authentication (MFA):** Mandatory for administrative roles; optional but recommended for external users to protect personal data.

### B. Owner portal

- **Digital Apartment Registration Form**
  - Converts the legacy paper layout into an online submission funnel.
  - Inputs: Property type (flat/apartment), room count, exact address (including house/plot name, goalhi, district/island), and target monthly rent.
  - System Automation: Upon submission, the system automatically tags the listing with the owner's verified phone number and matches it to the nearest operational branch (Malé, Hulhumalé, or Villingili) based on the district input.
- **Availability Toggle**
  - Status Controls: Allows owners to instantly flag an apartment as "Available" (open for viewings), "Rented" (active contract locked), or "Under Maintenance" (temporarily hidden from active client searches).
  - Lease Linkage: When a status changes to "Rented", the view dynamically displays a shortcut to the active, anonymized lease contract ledger (start date, duration, and payment status).
- **Property Dashboard**
  - Total View Counter: A real-time metric displaying the cumulative number of times staff have escorted a prospective client to the unit.
  - Aggregated Rejection Reasons: A clean graphical breakdown of standardized feedback collected by staff during viewings. The owner sees charts summarizing categorical reasons for non-selection.
    - Pricing sentiment: e.g., "70% of viewers indicated 'Little Expensive'."
    - Location constraints: e.g., "30% of viewers indicated 'Too far from work/school'."
    - Anonymized viewing timeline: a historical table showing dates of visits and custom notes synthesized by the staff.
  - Earnings & Payout Dashboard: Provides real-time and historical breakdowns of monthly rental yields minus platform management fees, complete with downloadable statements.

Client Apartment Viewings (example):

| Viewing Date | Staff Verification Status | Standardized Feedback Metric |
|---|---|---|
| 23/08/2018 | Verified by Staff: Ahmed Afzal | Financial Constraint (Ref: "Little expensive") |
| 25/08/2018 | Verified by Staff: Mohamed Muaz | |

### C. Client portal

- **Smart Property Search Engine:** Advanced filtering system enabling clients to query properties matching specific room configurations (e.g., 3-room apartments), maximum budget caps, and targeted geographic locations (Malé, Hulhumalé, Villingili).
- **Lease Term Configuration:** Select fixed rental periods ranging from short-term monthly cycles to long-term multi-year commitments.
- **Communication Hub:** A secure, localized messaging system allowing clients to interact with owners via mediated staff supervision to coordinate viewings, discuss furnishing requirements, or clarify contract terms.

### D. Staff dashboard

- **Centralized Verification Queue:** Staff can review incoming Apartment Registrations and Client Applications, performing compliance checks before pushing listings live.
- **Branch-Level Segmentation:** The system automatically groups staff, properties, and clients by branch numbers (Branch 1: Malé, Branch 2: Hulhumalé, Branch 3: Villingili) for clear operational boundaries.
- **Automated Lease Matchmaker:** Automatically matches new property listings with existing client profiles based on the client's documented Max Rent Preference and desired geographic location.
- **Apartment Viewing & Lead Management Module:** The paper system tracks client interactions before a lease is signed. The web application needs an interactive funnel to manage this.
  - Viewing Log & Feedback Tracker: Staff must be able to log when a specific client views a property (e.g., Client X viewing APT Y on 23/8/2018).
  - Qualitative Comments Field: The system must capture unstructured text feedback from clients regarding the property (e.g., "Little expensive" or "Too far from work") to help owners understand why their property isn't renting.
  - Client Engagement History: Owners should have visibility into how many times their apartment has been viewed, on what dates, and the associated staff notes.
- **Formal Lease Execution & Financial Booking**
  - Advance Payment & Deposit Tracking: The application must capture the exact payment parameters upon contract finalization: monthly rent amount; advance payment / security deposit (e.g., 23000); payment method selection (e.g., bank deposit, online transfer, etc.).
  - Digital Sign-Offs: Incorporate dual-signature workflows with clear digital sign-offs from both the client and the owner to legally execute the lease agreement online.
  - Lease Timeline Engine: Tracks dynamic lease boundaries, recording the exact rent started date and calculating expirations based on multi-year terms (e.g., a "5 years" term).

## 3. System requirements

### Functional requirements

- **FR-1 (Lease Creation):** The system must generate a legally binding digital contract when a staff member processes a match between an owner's property and a client. It must log the executing staff ID, date, and fixed duration.
- **FR-2 (Automated Notifications):** The system must send automated alerts (via SMS or Email) to clients and owners when lease renewals are due, rent payments are processed, or a new maintenance request is submitted.

## Conclusion

In conclusion, the proposed Housing_4_Raajje web application provides a practical and scalable solution to the operational challenges created by manual, paper-based property management processes. By centralizing apartment listings, client onboarding, lease execution, payment tracking, and branch-level administration into a single secure platform, the system will improve efficiency, strengthen data accuracy, and enhance service delivery across Malé, Hulhumalé, and Villingili. With clearly defined user roles, structured workflows, and digital access to critical information, the platform will enable Housing_4_Raajje to modernize its operations, improve transparency for owners and clients, and establish a stronger foundation for future growth.

## 4. Technical documentation

In addition to the functional system implementation, students are required to submit a complete technical documentation package that demonstrates the conceptual design, data design, system behavior, development process, and implementation evidence of their application. This section outlines the minimum documentation components that must be included in the final submission.

- **A. Conceptual Diagram:** Students must include a conceptual diagram that illustrates the overall structure of the proposed system. The diagram should identify the main actors, system components, major processes, and the high-level interactions between them. It should provide a clear visual representation of how the application operates from a business and systems perspective.
- **B. Data Dictionary:** Students must prepare a detailed data dictionary for all key entities used in the system. The data dictionary should define each table or entity, attribute name, data type, size or length where applicable, key constraints, allowed values, and a brief description of the purpose of each field. The documentation should be sufficiently detailed to support implementation and database validation.
- **C. Relational Schema:** Students must submit the relational schema of the proposed database design. This should clearly show the tables, primary keys, foreign keys, and the relationships between entities. The schema must reflect the logical database structure that supports the functional requirements of the application and should be presented in a manner that is clear, accurate, and easy to interpret.
- **D. Detailed Use Case Tabulations:** Students must provide detailed use case tabulations for the major functions of the system. Each use case should include, at minimum, the use case name, actors involved, objective, preconditions, trigger, normal flow, alternative flow, postconditions, and any relevant business rules. These tabulations should demonstrate a clear understanding of how users interact with the system and how the system responds under different conditions.
- **E. Scrum Meeting Logs:** Students are required to maintain and submit Scrum meeting log files as evidence of project planning, progress tracking, and team coordination throughout the development cycle. These logs should record meeting dates, participants, agenda items, discussion points, decisions made, assigned action items, and sprint-related updates. The log files should demonstrate continuous engagement with the development process and reflect the iterative nature of the project.
- **F. Application Screenshots:** Students must include screenshots of the developed application as part of the final documentation. The screenshots should capture the main interfaces, key workflows, and representative outputs of the system, such as login pages, dashboards, forms, reports, and transaction screens. Each screenshot should be clear, relevant, and, where appropriate, accompanied by short labels or captions to explain what is being demonstrated.

## 5. Submission order

1. **Prototype Submission for Approval (Pre-Requisite)**
   - Before any development work begins, students must prepare and submit a detailed interface prototype for approval. This prototype must be created using a recognized design or prototyping tool such as Figma, Figma Make, Google Stitch, Claude Code Design, or any other equivalent tool capable of producing structured, high-fidelity layouts.
   - The purpose of this requirement is to ensure that the system is properly designed in advance and not developed through unstructured or purely AI-driven "vibe coding." All major screens, workflows, and interface layouts must be clearly represented in the prototype.
   - Once development begins, the actual implemented system screenshots submitted in the final report must closely follow the approved prototype, with at least 80% consistency in layout, structure, and interface design.
2. **Final Report Submission:** All remaining required submission items must be compiled and submitted together as one complete final report. This includes the full technical documentation, implementation evidence, screenshots, and any other required deliverables. However, this final report may only be submitted after the prototype in Item 1 has been reviewed and approved.

> Good luck with your project. You are encouraged to embrace AI and make maximum, responsible use of it as a tool to support your thinking, design, and development process.
