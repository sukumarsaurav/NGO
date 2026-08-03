# NGO Management Platform — Laravel 13

A complete NGO website + management system: public crowdfunding site, donor-facing donation flows
(one-time, monthly auto-pay), member management with QR ID cards, auto-generated 80G receipts,
appointment letters, achievement certificates, notices, and a role-based manager panel.

**Live at:** https://visiongoodworkglobalfoundation.org

**Design references** (neither is our site — see [`docs/06-UI-UX-FOUNDATION.md`](docs/06-UI-UX-FOUNDATION.md)):
structure from [trueimpactfoundation.org](https://trueimpactfoundation.org/), brand palette from
[bright-minds-haven.vercel.app](https://bright-minds-haven.vercel.app/).

---

## Quick facts

| | |
|---|---|
| Organisation | Vision Good Work Global Foundation |
| Production domain | https://visiongoodworkglobalfoundation.org |
| Framework | Laravel 13 (PHP 8.4+) |
| Admin panel | Filament v5 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Database | MySQL 8 |
| Payments | Razorpay (one-time + UPI Autopay / e-mandate subscriptions) |
| PDF | `barryvdh/laravel-dompdf` |
| QR | `simplesoftwareio/simple-qrcode` |
| Roles | `spatie/laravel-permission` |
| Media | `spatie/laravel-medialibrary` |
| Hosting | Hostinger (Business / Cloud / VPS — needs SSH + Composer + cron) |
| Scope | Single NGO (schema kept SaaS-ready) |

---

## Documentation index

Read these in order. Each doc is standalone enough to hand to a developer.

| Doc | What's in it |
|---|---|
| [`docs/00-PROJECT-OVERVIEW.md`](docs/00-PROJECT-OVERVIEW.md) | Scope, goals, user roles, full feature inventory, out-of-scope list |
| [`docs/01-ARCHITECTURE.md`](docs/01-ARCHITECTURE.md) | Layered architecture, module boundaries, complete folder tree, naming rules |
| [`docs/02-DATABASE-SCHEMA.md`](docs/02-DATABASE-SCHEMA.md) | Every table, column, relation, index; ERD; migration order |
| [`docs/03-ROADMAP.md`](docs/03-ROADMAP.md) | Phase 0 + 8 phases → 17 sprints, with deliverables and acceptance criteria |
| [`docs/04-DEPLOYMENT-HOSTINGER.md`](docs/04-DEPLOYMENT-HOSTINGER.md) | Hostinger setup, cron, queue worker, deploy script, `.env` reference |
| [`docs/05-CONVENTIONS.md`](docs/05-CONVENTIONS.md) | Coding standards, git flow, testing, PR checklist |
| [`docs/06-UI-UX-FOUNDATION.md`](docs/06-UI-UX-FOUNDATION.md) | Design tokens, layout contract, donation-form & payment-state specs, portal IA |
| [`docs/07-SEO.md`](docs/07-SEO.md) | URL structure, sitemaps, JSON-LD, on-page rules per template, admin SEO UX, technical checklist |
| [`docs/08-DESIGN-SYSTEM.md`](docs/08-DESIGN-SYSTEM.md) | Tokens, z-index scale, motion, focus, 13 component specs, dark-mode decision, governance |
| [`docs/modules/`](docs/modules/) | One detailed spec per module (M01–M12) |

---

## Module map

| # | Module | Phase | Doc |
|---|---|---|---|
| M01 | Foundation & Auth | 1 | [M01](docs/modules/M01-foundation-auth.md) |
| M02 | Organisation Settings | 1 | [M02](docs/modules/M02-organisation-settings.md) |
| M03 | Members & Volunteers | 2 | [M03](docs/modules/M03-members.md) |
| M04 | Document Engine (PDF + QR) | 2 | [M04](docs/modules/M04-document-engine.md) |
| M05 | Donations & Payments | 3 | [M05](docs/modules/M05-donations-payments.md) |
| M06 | Recurring / Auto-Pay | 4 | [M06](docs/modules/M06-recurring-autopay.md) |
| M07 | 80G Receipts & Compliance | 4 | [M07](docs/modules/M07-receipts-80g.md) |
| M08 | Campaigns & Crowdfunding | 5 | [M08](docs/modules/M08-campaigns-crowdfunding.md) |
| M09 | Notices & Communication | 6 | [M09](docs/modules/M09-notices-communication.md) |
| M10 | Public Website & CMS | 6 | [M10](docs/modules/M10-public-site-cms.md) |
| M11 | Manager Panel & Permissions | 7 | [M11](docs/modules/M11-manager-panel.md) |
| M12 | Reports & Analytics | 7 | [M12](docs/modules/M12-reports-analytics.md) |

---

## Getting started

```bash
git clone <repo> vgwgf-platform && cd vgwgf-platform
composer install
cp .env.example .env && php artisan key:generate
# fill DB + Razorpay + mail credentials in .env, and set ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
composer dev   # serve + queue:listen + pail + vite, concurrently
```

Local dev runs on SQLite by default — there's no MySQL requirement to get started. Switch
`DB_CONNECTION` to `mysql` in `.env` before anything touches a MySQL-only schema feature (see the
comment in `.env.example`).

Admin login: `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` from `.env`, seeded by
`database/seeders/AdminUserSeeder.php`. **Change the password on first login** — this is a local
convenience credential, not something to carry into staging or production.

```bash
composer check   # Pint + Larastan (level 5) + Pest — same as CI
```

---

## Status

**Sprints 1–3 (Project skeleton & auth; Settings, media, audit; Members) are built and passing**,
ahead of the Phase 0 UI/UX foundation work in the roadmap — see
[`docs/03-ROADMAP.md`](docs/03-ROADMAP.md) for what's still ahead.

**Sprint 3 (Members) added:**
- `departments` (nested, cycle-checked), `designations`, `members`, `member_code_sequences` —
  migrations, models, factories
- `App\Services\Numbering\MemberCodeGenerator` — same row-locked, cold-start-race-safe pattern as
  the receipt-number generator (`VGWGF-2026-00123`, sequential per calendar year)
- All 5 actions: `CreateMember`, `UpdateMember`, `AssignDesignation`, `DeactivateMember` (the general
  status-transition handler — approve/suspend/resign/expire/reinstate, guarded by
  `MemberStatus::canTransitionTo()`), `BulkImportMembers`
- `MemberPolicy` — self-service (own record only), manager (own department + sub-departments,
  IDOR-tested), donor (no access), super-admin/admin (`Gate::before` bypass)
- `MemberResource` — tabbed form (Personal/Contact/Organisation/ID proof/Internal notes), 1:1 photo
  crop resized to 400×400, filters (status/department/designation/joined-date/has-photo), bulk
  actions (export, change department, change status — the last two route through `DeactivateMember`
  per-record, not a blanket mass-update, so a heterogeneous selection doesn't force an invalid
  transition on some of them)
- CSV import (`ImportMembers` page) — dry run, per-row error report, near-miss department/designation
  suggestions via `similar_text()`, duplicate-email detection (both against the DB and within the
  same file), never writes on dry run
- Member portal (`/portal`, `/portal/profile`) — contact-details self-service, explicitly excludes
  department/designation/status/member_code even if posted
- `is_active` now actually gates login (folded into the credential lookup, not a post-hoc check) —
  this is what makes `DeactivateMember`'s "portal access removal" claim true
- **132 tests passing** (up from 71), Larastan level 5 clean, Pint clean

One deliberate scope reduction: `DepartmentResource`'s table is a flat list with a Parent column, not
a drag-and-drop tree widget — Filament ships no built-in tree table, and hierarchy is still fully
enforced (cycle prevention in the form, `selfAndDescendantIds()` for manager scoping); building a real
tree UI was disproportionate to what Sprint 3 needed.

**Four more real bugs found by running the code, on top of Sprint 2's two:**
- `User`'s `#[Fillable(...)]` list didn't include `is_active` — `update(['is_active' => ...])` was
  being silently mass-assignment-filtered. Fixed by having the trusted internal action set the
  attribute directly rather than widening the fillable surface for a security-sensitive field.
- Filament's relationship dot-notation (`TextInput::make('user.name')` on a real `BelongsTo`) does
  **not** auto-hydrate on edit — confirmed by dumping the actual component state, which came back
  `null` despite a real linked user with data. Fixed with an explicit `mutateFormDataBeforeFill()`.
  (Different bug from Sprint 2's: that one had no real relation at all; this one has a real relation
  that Filament still doesn't auto-load for you.)
- A custom Filament page outside the standard List/Create/Edit trio doesn't inherit resource-policy
  gating for free — `ImportMembers` needed an explicit `canAccess()` override, caught by a test that
  expected a manager to get 403 and didn't.
- A `Select` field backed by enum `::options()` returns the **enum instance itself** in the submitted
  state, not its scalar value — `MemberStatus::from($data['status'])` threw a `TypeError` until this
  was accounted for.

Done in Sprints 1–2:
- Laravel 13 + Filament v5 installed; two panels (`/admin`, `/manager`) boot, gated by role, dark
  mode off per [`08-DESIGN-SYSTEM.md`](docs/08-DESIGN-SYSTEM.md) §11
- `users`, `settings`, `webhook_events` + spatie permission/activitylog/medialibrary tables migrated
- `RolePermissionSeeder` (5 roles, 52 permissions), `SettingsSeeder` (60 keys across 6 groups),
  `AdminUserSeeder`
- `App\Support\Money`, `FinancialYear`, `NumberToWords` — unit tested, including the exact
  roadmap acceptance-criteria strings
- Public auth (register, login, logout, forgot/reset password, email verification) with
  role-based post-login redirect
- `App\Services\Settings\SettingsRepository` — forever-cached + in-memory-memoized, typed casting
  (int/bool/json/file), transparent encryption for PAN, cache busted and `activity_log`'d on every
  write, refuses to write an unseeded key
- `OrganisationSettings` Filament page — 6 tabs, PAN masked with a super-admin-gated reveal action
  (never hydrated into the editable field, so a blank submit can't wipe it), a live 80G
  expiry/expired banner, money fields edited in rupees and stored in paise
- `User` activity logging (name/email/phone/is_active only — never password or anything encrypted)
- Exception handler redaction (`dontFlash`) for password/PAN/card fields
- Design tokens (`resources/css/tokens.css`, `tailwind.config.js`) wired through Tailwind v4 via
  `@config`, self-hosted Inter + Noto Sans Devanagari
- CI (`.github/workflows/ci.yml`): Pint, Larastan, Pest on every push/PR

**Package compatibility spike (Sprint 1) — results:**

| Package | Status |
|---|---|
| `razorpay/razorpay` | ✅ Installs clean on Laravel 13 / PHP 8.4 |
| `simplesoftwareio/simple-qrcode` | ✅ Installs clean |
| `pestphp/pest` | ⚠️ Needs **v4**, not v3 — v3 requires PHPUnit 11, Laravel 13 ships PHPUnit 12 |
| `spatie/laravel-backup` | ❌ No version yet supports Laravel 13 — **not installed**, revisit before Sprint 15 |
| `barryvdh/laravel-debugbar` | ❌ No version yet supports Laravel 13 — **not installed**, revisit before Sprint 15 |
| `laravel/pao` (scaffold default) | Removed — not in the architecture spec, was blocking the Pest v4 upgrade |

**Two bugs worth knowing about, found by actually running things:**
- Filament treats a dot in a field name (`TextInput::make('org.name')`) as a *nested* state path, not
  a literal key — the naive implementation silently bound every field to nothing. Fixed by naming
  fields with underscores and mapping to real setting keys only at the form/save boundary
  (`OrganisationSettings::fname()`/`settingKey()`).
- `UserFactory` didn't set `is_active` explicitly, so Eloquent's in-memory model after `create()` had
  no original value for it — the *next* `update()` call, for anything, saw it as spuriously dirty and
  polluted the activity log with a phantom `is_active` change. Fixed by setting it explicitly in the
  factory.

Since no MySQL server exists on this machine and `barryvdh/laravel-debugbar` isn't installable on
Laravel 13 yet, the "settings reads hit cache, not DB" acceptance criterion was verified with a
`DB::enableQueryLog()` assertion instead of Debugbar — see `SettingsRepositoryTest`.

Two things still need to happen regardless of sequencing: submit the **Razorpay recurring-payments
application** (2–3 week approval, blocks Sprint 7) and confirm dompdf Devanagari rendering once M04
starts (Sprint 4).

Not yet started: the Phase 0 design-token *component library* (Blade components beyond the auth/
settings form primitives built here), and Sprint 3 onward.
