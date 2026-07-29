# Module Specs

One spec per module. Each contains: purpose, scope, data, actions, events, UI surfaces, edge cases,
and a build checklist.

| # | Module | Phase | Depends on |
|---|---|---|---|
| [M01](M01-foundation-auth.md) | Foundation & Auth | 1 | — |
| [M02](M02-organisation-settings.md) | Organisation Settings | 1 | M01 |
| [M03](M03-members.md) | Members & Volunteers | 2 | M01, M02 |
| [M04](M04-document-engine.md) | Document Engine (PDF + QR) | 2 | M02, M03 |
| [M05](M05-donations-payments.md) | Donations & Payments | 3 | M01, M02 |
| [M06](M06-recurring-autopay.md) | Recurring / Auto-Pay | 4 | M05 |
| [M07](M07-receipts-80g.md) | Receipts & 80G Compliance | 4 | M02, M05 |
| [M08](M08-campaigns-crowdfunding.md) | Campaigns & Crowdfunding | 5 | M05 |
| [M09](M09-notices-communication.md) | Notices & Communication | 6 | M01, M03 |
| [M10](M10-public-site-cms.md) | Public Website & CMS | 6 | M08 |
| [M11](M11-manager-panel.md) | Manager Panel & Permissions | 7 | M03, M04, M09 |
| [M12](M12-reports-analytics.md) | Reports & Analytics | 7 | M05, M06, M07, M08 |

## Reading order for a new developer

1. `../00-PROJECT-OVERVIEW.md` — what and why
2. `../01-ARCHITECTURE.md` — how the code is organised
3. `../02-DATABASE-SCHEMA.md` — the data model
4. `../05-CONVENTIONS.md` — how we write code
5. `../06-UI-UX-FOUNDATION.md` — if your module renders anything a user sees
6. The module you're assigned, plus its dependencies

## Specs that live outside the module docs

Three things are specified in [`../06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) rather than in
a module, because they span several:

- **The donation form** (§5) — supersedes `M05 §UI`. Resolves the conflict between M05's "keep it
  short", M07's PAN requirement, and M07's anonymous-versus-80G rule.
- **Payment interaction states** (§6) — supersedes step 9 of M05's flow diagram. Checkout.js is a
  modal, not a redirect.
- **Portal information architecture** (§9) — `/portal` is referenced by M03, M04, M05, M06 and M09
  and owned by none of them.

## The three modules to read carefully

**M05 (Payments)**, **M06 (Recurring)**, and **M07 (Receipts)** contain every genuinely hard problem
in this project: idempotency, race conditions, gateway state machines, and legal compliance. Budget
your attention accordingly.

**M08** deserves a second look too. Its needs catalogue (`§Products`) is a price list rendered in
HTML that feeds directly into a payment amount — every line total must be recomputed server-side from
the database, exactly like M05's amount validation. The rest of the modules are competent CRUD.
