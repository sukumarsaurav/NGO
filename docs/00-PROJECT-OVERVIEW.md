# 00 — Project Overview

## 1. What we are building

A single-organisation NGO platform with two faces:

**Public face** — a donor-facing website modelled on the UX of
The platform ships at **https://visiongoodworkglobalfoundation.org** for **Vision Good Work Global
Foundation**. The site referenced below is a structural model only, not this organisation.

[trueimpactfoundation.org](https://trueimpactfoundation.org/): campaign listings by cause,
individual campaign pages with live raised/donor counters, one-time and monthly donation flows,
"start a fundraiser" requests, blog, and static pages. Optimised for trust signals (80G tax benefit
badge, verified-campaign badge, press mentions, testimonials).

**Internal face** — a Filament-powered admin/manager panel that runs the NGO's operations: member
records, QR-coded ID cards, appointment letters, achievement certificates, donation ledger,
auto-generated 80G receipts, notices to members, and reporting.

## 2. Decisions locked in

| Decision | Choice | Why |
|---|---|---|
| Backend framework | **Laravel 13** | Auth, queues, ORM, scheduler, mail all built in. Plain PHP would mean hand-rolling all of it. |
| Scope | **Single NGO** | One organisation's data. No tenant switching, no per-tenant billing. |
| SaaS readiness | **Deferred, not blocked** | See §5 — we keep a `settings` abstraction and avoid hard-coded org data, so a future `organisation_id` migration is additive, not a rewrite. |
| Admin panel | **Filament v5** | Generates CRUD, tables, filters, forms, and a dashboard. Saves ~4–6 weeks vs hand-built Blade admin. |
| Public frontend | **Blade + Tailwind + Alpine** | Server-rendered = good SEO, which matters a lot for a donation site. No SPA complexity. |
| Payments | **Razorpay** | Best-supported Indian gateway for UPI Autopay and NACH e-mandate recurring donations. |
| Payment abstraction | **`PaymentGateway` interface** | Razorpay is the only implementation now, but donation logic never calls the Razorpay SDK directly. Swapping to Cashfree later = one new class. |
| Database | **MySQL 8** | Hostinger default. |
| Deployment | **Hostinger** | Requires a plan with SSH, Composer, and cron. See `04-DEPLOYMENT-HOSTINGER.md`. |

## 3. User roles

Five roles, managed by `spatie/laravel-permission`.

| Role | Who | Can do |
|---|---|---|
| `super-admin` | Owner / founder | Everything, including settings, user management, and destructive actions. |
| `admin` | Trusted staff | Everything except organisation settings, role editing, and permanent deletes. |
| `manager` | Department / regional head | Scoped: manage members in their department, issue documents, publish notices, view donations (read-only). This is the "Manager Panel" from the pamphlet. |
| `member` | Volunteer / registered member | Self-service portal: view own ID card, letters, certificates; read notices; update own profile. |
| `donor` | Public donor with an account | View own donation history, download own 80G receipts, manage recurring mandates. |

Guests (unauthenticated visitors) can browse campaigns and donate — a `donor` account is created
lazily on first donation, or the donation is stored against email/phone only if they decline signup.

## 4. Feature inventory

Traced from the two pamphlets and the reference site. Every row maps to a module.

### From the pamphlets

| Pamphlet feature | Module | Notes |
|---|---|---|
| Member ID Card with QR code | M04 | PDF + QR encoding a public verify URL. |
| Appointment Letter with QR | M04 | Issued when a member is assigned a designation. |
| Achievement Certificate with QR | M04 | Manually issued or triggered by milestone. |
| Manager Panel | M11 | Scoped Filament panel for the `manager` role. |
| Notice feature (send notices to members) | M09 | In-app + email; optional WhatsApp later. |
| Visitor donation (donate without account) | M05 | Guest checkout. |
| Donation receipt after donating | M05 | Instant transactional receipt PDF + email. |
| 80G donation receipt (certified, auto-generated) | M07 | Separate numbered series, PAN capture, FY-scoped, Form 10BD export. |
| Email action on every website event | M09 | Event → queued Mailable, driven by a template table. |
| Auto Pay Donation (monthly recurring) | M06 | Razorpay Subscriptions / UPI Autopay. |
| Crowdfunding — launch & track campaigns | M08 | Goal, raised, donor count, updates. |
| Reports & analytics | M12 | Dashboards + CSV/Excel exports. |
| Mobile friendly | M10 | Tailwind responsive, mobile-first. |
| Secure data | M01, M05 | Encryption at rest for PAN, HTTPS, webhook signature verification. |
| 24×7 support | — | Business process, not software. |

### From the reference site

| Reference-site feature | Module |
|---|---|
| Featured campaigns carousel on homepage | M08 / M10 |
| Campaign browse filtered by cause (Animals, Children, Elderly, Education, Faith, Women, Disaster Relief, Specially Abled) | M08 |
| Campaign card: image, title, beneficiary NGO name, "Tax Benefit" badge, amount raised, donor count | M08 |
| Monthly campaign page (recurring-only campaigns) | M06 / M08 |
| "Start a Fundraise" request form | M08 |
| Donor login / signup | M01 |
| Impact stats counters (team members, reviews, projects, lives impacted) | M10 |
| "How to donate" 4-step explainer | M10 |
| Featured-in / press logos | M10 |
| Testimonials | M10 |
| Blog | M10 |
| Newsletter subscribe | M09 |
| Social share per campaign (FB, WhatsApp, LinkedIn) | M08 |
| Static pages: About, Privacy Policy, Terms | M10 |
| Payment-method logos in footer | M10 |

## 5. Keeping the door open for multi-tenant

We are **not** building SaaS now. But these three cheap habits mean adding it later is a migration,
not a rewrite:

1. **No hard-coded org data anywhere.** NGO name, logo, address, PAN, 80G registration number,
   80G validity dates — all live in a `settings` table read through a `Settings` facade, never in
   `config/` or Blade literals.
2. **All queries go through Eloquent scopes / repositories.** Adding a global `organisation_id`
   scope later means touching one trait, not 200 queries.
3. **File storage is namespaced.** Uploads go to `storage/app/public/org/{slug}/...` from day one.

Cost today: about half a day. Cost of skipping it: a multi-week rewrite.

## 6. Explicitly out of scope (v1)

- Multi-tenant SaaS onboarding, per-NGO subdomains, subscription billing for NGOs
- Native mobile apps (the site is responsive; a PWA is a possible v1.5)
- SMS / WhatsApp Business API notifications (email only in v1 — hooks are left in place)
- Multi-language / i18n (English + Hindi content is entered manually as separate CMS entries)
- Accounting-software integration (Tally, Zoho Books)
- FCRA-specific reporting (only 80G / Form 10BD in v1)
- Donor CRM automations (drip campaigns, segmentation)
- Payment gateways other than Razorpay

## 7. Compliance notes (India)

These are product requirements, not legal advice — confirm details with the NGO's CA before launch.

- **80G receipts** need: NGO name, address, PAN, 80G registration number and validity period,
  donor name, donor PAN (required for donations where the NGO must report them), donor address,
  amount in figures and words, mode of payment, date, and a unique sequential receipt number.
- **Receipt numbering** must be sequential and gap-free **per financial year** (Apr 1 – Mar 31).
  This has real design consequences — see `M07` for the locking strategy.
- **Form 10BD** is the annual statement of donations the NGO files. The system must export a CSV
  matching its schema. **Form 10BE** is the certificate issued to donors afterwards.
- **Donor PAN** is sensitive. Store encrypted (`encrypted` cast), never log it, mask it in the UI
  except on the receipt itself.
- **Cash donations above ₹2,000** are not eligible for 80G deduction — the system must flag this
  and refuse to issue an 80G receipt for such entries.

## 8. Success criteria for v1

- A visitor can find a campaign, donate ₹500 as a guest, and receive a valid 80G receipt PDF by
  email within **5 minutes on Hostinger shared hosting**, or 60 seconds on a VPS.

  > The original 60-second target is not achievable on the shared-hosting deployment described in
  > `04-DEPLOYMENT-HOSTINGER.md`: the queue is driven by a one-minute cron with
  > `--stop-when-empty`, so worst-case latency is ~60 s before dompdf even starts. Either accept the
  > 5-minute figure, or budget for a VPS at launch rather than "once volume grows". Do not ship a
  > stated SLA the infrastructure cannot meet.
- A donor can set up a ₹1,000/month UPI Autopay mandate and cancel it themselves.
- An admin can add a member and issue an ID card, appointment letter, and certificate in under
  2 minutes total, with working QR verification.
- A manager can only see and act on their own department's members.
- Admin can export a Form 10BD-shaped CSV for a chosen financial year.
- Homepage scores ≥ 90 on Lighthouse mobile performance and SEO.

---

Next: [`01-ARCHITECTURE.md`](01-ARCHITECTURE.md)
