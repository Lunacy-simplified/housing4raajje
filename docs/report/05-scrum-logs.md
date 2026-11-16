# Scrum meeting logs

> **To the team: this section is not finished.** Scrum logs are evidence of meetings that really took place, so they
> cannot be written after the fact by someone who wasn't there. Copy the template below once per meeting and fill it in
> from your own notes, chat history and the Trello board. The repository timeline at the end is factual and can be used
> to jog memories and to back up what was discussed.

## Template (copy for each meeting)

### Sprint _N_, meeting _M_ — _date_

| | |
|---|---|
| **Type** | Sprint planning / Daily stand-up / Sprint review / Retrospective |
| **Date and time** | |
| **Participants** | |
| **Absent** | |
| **Sprint goal** | |

**Agenda**

1.

**Discussion points**

| Member | Done since last meeting | Doing next | Blocked by |
|---|---|---|---|
| | | | |

**Decisions made**

-

**Action items**

| Action | Owner | Due | Trello card |
|---|---|---|---|
| | | | |

**Sprint update** (cards moved, scope added or dropped, risks)

-

---

## Repository timeline (factual, from the Git history)

Use this as supporting evidence. It shows what was delivered and when, not what was said in meetings.

| Date | What was merged or committed | By |
|---|---|---|
| 6–8 Oct 2026 | Laravel 13 project, PostgreSQL in Docker, agent workflow, first migrations and seeders | Looth |
| 11–12 Oct 2026 | Roles and users tables; sprint 1 migrations and models (profiles, permissions, compensation) | Looth |
| 13–15 Oct 2026 | Master data, properties, viewings, applications, leases, payments, support and messaging migrations and models | Looth |
| 18 Oct 2026 | PR #1: sign in and sign out, role-protected dashboards | Looth |
| 19 Oct 2026 | PR #2: owner and client registration | Looth |
| 22 Oct 2026 | PR #3: admin staff management (create, list, edit, activate), front-end handoff document | Looth |
| 25 Oct 2026 | PR #4: email verification, Mailpit for local email | Looth |
| 26 Oct 2026 | PR #5: password reset. PR #6: staff invitations | Looth |
| 29 Oct 2026 | PR #7: sign-in codes (MFA) and trusted devices | Looth |
| 13–14 Nov 2026 | PR #8: SMS channel and phone verification | Lunacy-simplified |
| 15 Nov 2026 | Front end added to the existing pages (shared stylesheet, layouts, restyled views) | Lujain |
| 17 Nov 2026 | PR #9: front-end cleanup and redesign on Apple's Human Interface Guidelines, design rules document | iw2-abdulsamad |
| 17 Nov 2026 | Assignment brief added to the repository; rental flow built end to end (properties, viewings, applications, leases, payments, payouts, support, manager and admin screens); technical documentation | iw2-abdulsamad |

Names in the last column are the Git author names as recorded in the repository.

To regenerate this list: `git log --date=short --format="%ad %an %s"`.

## Trello board

Sprint planning and progress were tracked on the team board:
<https://trello.com/b/jLOnUR4W/property-management-system> (lists: Product Backlog, Sprint Backlog, In Progress,
Review/Testing, Done). Include a screenshot of the board at the end of each sprint with that sprint's log.
