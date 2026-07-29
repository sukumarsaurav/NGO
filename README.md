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
| Framework | Laravel 13 (PHP 8.3+) |
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

**Sprint 1 (Project skeleton & auth) is built and passing**, ahead of the Phase 0 UI/UX foundation
work in the roadmap — see [`docs/03-ROADMAP.md`](docs/03-ROADMAP.md) for what's still ahead.

Done so far:
- Laravel 13 + Filament v5 installed; two panels (`/admin`, `/manager`) boot, gated by role, dark
  mode off per [`08-DESIGN-SYSTEM.md`](docs/08-DESIGN-SYSTEM.md) §11
- `users`, `settings`, `webhook_events` + spatie permission/activitylog/medialibrary tables migrated
- `RolePermissionSeeder` (5 roles, 52 permissions) and `AdminUserSeeder`
- `App\Support\Money`, `FinancialYear`, `NumberToWords` — unit tested, including the exact
  roadmap acceptance-criteria strings
- Public auth (register, login, logout, forgot/reset password, email verification) with
  role-based post-login redirect
- Design tokens (`resources/css/tokens.css`, `tailwind.config.js`) wired through Tailwind v4 via
  `@config`, self-hosted Inter + Noto Sans Devanagari
- CI (`.github/workflows/ci.yml`): Pint, Larastan, Pest on every push/PR
- 45 tests passing, Larastan level 5 clean, Pint clean

**Package compatibility spike (Sprint 1) — results:**

| Package | Status |
|---|---|
| `razorpay/razorpay` | ✅ Installs clean on Laravel 13 / PHP 8.4 |
| `simplesoftwareio/simple-qrcode` | ✅ Installs clean |
| `pestphp/pest` | ⚠️ Needs **v4**, not v3 — v3 requires PHPUnit 11, Laravel 13 ships PHPUnit 12 |
| `spatie/laravel-backup` | ❌ No version yet supports Laravel 13 — **not installed**, revisit before Sprint 15 |
| `barryvdh/laravel-debugbar` | ❌ No version yet supports Laravel 13 — **not installed**, revisit before Sprint 15 |
| `laravel/pao` (scaffold default) | Removed — not in the architecture spec, was blocking the Pest v4 upgrade |

Two things still need to happen regardless of sequencing: submit the **Razorpay recurring-payments
application** (2–3 week approval, blocks Sprint 7) and confirm dompdf Devanagari rendering once M04
starts (Sprint 4).

Not yet started: the Phase 0 design-token *component library* (Blade components beyond the auth
form primitives built here), and Sprints 2 onward.
