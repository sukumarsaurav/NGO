# 03 — Phased Roadmap

## How to read this

A one-week Phase 0, then eight phases of seventeen sprints. Estimates assume **one full-time senior
developer**. Two developers running the parallel tracks noted below lands closer to 12–13 weeks.

Each sprint has **acceptance criteria** — the sprint is not done until every box passes. Resist the
temptation to move on with "I'll come back to that." In a system that issues legal receipts, the
things you skip are exactly the things that bite.

**Total: ~18 weeks solo, ~12–13 weeks with two developers.**

Phase 5 runs three sprints rather than two because of the campaign needs-catalogue (Sprint 9B).
Sprint numbers are stable; every phase after 5 shifts one week later.

---

## Phase overview

| Phase | Weeks | Theme | Ships |
|---|---|---|---|
| 0 | 0 | UI/UX Foundation | Design tokens, component library, public layout, wireframes |
| 1 | 1–2 | Foundation | Laravel + Filament running, auth, roles, settings |
| 2 | 3–4 | Members & Documents | Member CRUD, ID cards, letters, certificates, QR verify |
| 3 | 5–6 | Donations | Public donation flow, Razorpay one-time, instant receipts |
| 4 | 7–8 | Recurring & 80G | Auto-pay mandates, 80G receipts, Form 10BD export |
| 5 | 9–11 | Campaigns | Crowdfunding, categories, campaign pages, **needs catalogue**, fundraiser requests |
| 6 | 12–13 | Public site & Comms | Full website, CMS, notices, email engine |
| 7 | 14–15 | Manager panel & Reports | Scoped panel, dashboards, exports |
| 8 | 16–17 | Hardening & Launch | Security, performance, SEO, UAT, deploy |

---

## Phase 0 — UI/UX Foundation (Week 0)

Full specification: [`06-UI-UX-FOUNDATION.md`](06-UI-UX-FOUNDATION.md).

**Build**

- `resources/css/tokens.css` + `tailwind.config.js` per [`08-DESIGN-SYSTEM.md`](08-DESIGN-SYSTEM.md) —
  colour, type, space, radius, elevation, **z-index scale**, motion, focus ring
- Self-hosted Inter (variable) + Noto Sans Devanagari subset
- Filament v5 custom theme registering the same primitives, **with `->darkMode(false)`** on both
  panel providers (Filament ships dark mode on; left alone the admin builds itself a second,
  unrelated appearance — `08 §11`)
- Public layout shell: header with sticky behaviour, mobile drawer, footer, WhatsApp float
- Blade component library (campaign card, progress bar, donate button, trust badges, form inputs,
  empty states, skeletons) with a local-only `/dev/components` gallery
- Wireframes: donation form, campaign detail, portal dashboard
- 8 category placeholder images + default OG image
- **URL map agreed and frozen** (`07-SEO.md` §1) — `/causes/{category}` and `/campaigns/{slug}` are
  separate namespaces; getting this wrong is the one SEO decision that cannot be retrofitted
- `<x-seo-meta>` and `<x-json-ld>` components; SERP and WhatsApp/OG preview components for the admin
- Doc amendments: `banners` + `contact_messages` in the schema, `homepage` settings group in M02

**Acceptance criteria**

- [ ] Every token pair passes 4.5:1 contrast (3:1 for ≥ 24 px and for control boundaries) — the ratios
      are already measured in §2; the check here is that the built config matches them
- [ ] The donate button is green with white text, and no amber surface anywhere carries white text
- [ ] `/dev/components` renders every component at 360 px, 768 px and 1280 px without horizontal scroll
- [ ] Every component shows all its states: hover, active, focus-visible, disabled, loading, error
- [ ] **The focus ring is visible on a filled donate button** — drawn flush it measures 1.35:1 and
      disappears; it must be offset (`08 §8`)
- [ ] Tailwind scales **replace** the defaults, so `z-50`, `rounded-3xl` and `bg-emerald-400` do not
      resolve — utilities that do not exist cannot drift
- [ ] The admin panel and the public site visibly share one brand palette
- [ ] The donation-form wireframe resolves the 80G / anonymous / monthly interaction (§5 of the doc)
- [ ] Schema and M02 amendments are merged before Sprint 1 opens

> **Why this comes first rather than in Sprint 11.** Sprint 6 builds the public donation page and
> Sprints 9–10 build campaign pages. Without this, all three are built against a throwaway layout and
> then rebuilt. The donation form is the highest-stakes screen in the product; it should be built
> once, by someone who has already decided what a button looks like.

---

## Phase 1 — Foundation (Weeks 1–2)

### Sprint 1: Project skeleton & auth

**Build**

- `laravel new`, PHP 8.3, Git repo, branch strategy from `05-CONVENTIONS.md`
- Install: Filament v5, spatie/permission, spatie/medialibrary, spatie/activitylog, Pint, Larastan, Pest
- Tailwind + Alpine + Vite configured
- `users` migration with `uuid`, `phone`, `is_active`
- `RolePermissionSeeder`: 5 roles, ~60 permissions
- `AdminPanelProvider` at `/admin`, login gated to `super-admin` + `admin`
- Public auth: register, login, forgot password, email verification
- `App\Support\Money`, `App\Support\FinancialYear`, `App\Support\NumberToWords` **with unit tests**
- All `Enums`
- Local dev environment documented in README
- **Day 1, before any code:** submit the Razorpay recurring-payments application. Approval takes
  2–3 weeks and Sprint 7 cannot start without it — this is the project's real critical path.
- **Spike:** verify `razorpay/razorpay` and `simplesoftwareio/simple-qrcode` against Laravel 13 /
  PHP 8.3, and render one Devanagari string through dompdf. Half a day now; a re-plan in week 7 if skipped.

**Acceptance criteria**

- [ ] `php artisan migrate:fresh --seed` runs clean from zero
- [ ] Admin can log in at `/admin` and see an (empty) dashboard
- [ ] A `member`-role user is rejected from `/admin` with 403
- [ ] `Money::fromRupees(1500.50)->toPaise() === 150050` and round-trips exactly
- [ ] `FinancialYear::for('2026-03-31') === '2025-26'` and `for('2026-04-01') === '2026-27'`
- [ ] `NumberToWords::rupees(150050)` returns "One Thousand Five Hundred Rupees and Fifty Paise Only"
- [ ] Pint and Larastan (level 5) pass with zero errors
- [ ] CI runs tests on push

> The three `Support` classes look trivial and are not. `NumberToWords` appears on every legal
> receipt; `FinancialYear` decides which receipt series a donation lands in. Unit test them now.

### Sprint 2: Settings, media, audit

**Build**

- `settings` table + `SettingsRepository` with forever-cache and bust-on-write
- `Settings` facade
- `OrganisationSettings` Filament page: identity, address, PAN, 80G number + validity, 12A, logo,
  signature image, seal image, social links, SEO defaults
- PAN encrypted at rest
- Medialibrary configured, storage namespaced to `org/{slug}/`
- Activitylog on User, Setting
- `webhook_events` table
- Global exception handler + logging strategy
- `.env.example` fully documented

**Acceptance criteria**

- [ ] Changing `org.name` in admin updates it everywhere on the next request
- [ ] `Settings::get('org.pan')` returns plaintext; the DB column is ciphertext
- [ ] Uploading a logo stores it under `storage/app/public/org/{slug}/`
- [ ] Every settings change appears in `activity_log` with the acting user
- [ ] Settings reads hit cache, not DB (verify with Debugbar)

---

## Phase 2 — Members & Document Engine (Weeks 3–4)

### Sprint 3: Members

**Build**

- `departments`, `designations`, `members` migrations + models + factories
- `MemberCodeGenerator` (`VGWGF-{YYYY}-{00000}`)
- Actions: `CreateMember`, `UpdateMember`, `AssignDesignation`, `DeactivateMember`
- `MemberResource` in Filament: list with filters (status, department, designation), create/edit form,
  photo upload, bulk actions
- `DepartmentResource`, `DesignationResource`
- CSV bulk import with validation and a dry-run preview
- Member self-service portal: view/edit own profile
- `MemberPolicy`
- Events: `MemberCreated`, `DesignationAssigned`

**Acceptance criteria**

- [ ] Creating a member auto-generates a unique sequential `member_code`
- [ ] Photo uploads resize to 400×400 and store correctly
- [ ] Importing a 200-row CSV with 3 bad rows reports the 3 errors and imports 197
- [ ] Import dry-run changes nothing in the DB
- [ ] `id_proof_number` is encrypted in the DB
- [ ] A member logging into `/portal` sees only their own record

### Sprint 4: Document engine

**Build**

- `document_templates`, `issued_documents` migrations + models
- `PdfRenderer` service wrapping dompdf
- `QrCodeGenerator` + public verify route `/verify/{uuid}`
- `DocumentNumberGenerator`
- Actions: `IssueIdCard`, `IssueAppointmentLetter`, `IssueCertificate`, `RevokeDocument`
- `GeneratePdfDocument` queued job
- Blade PDF templates for all three document types (CR80 landscape for ID cards, A4 for the rest)
- Filament: `DocumentTemplateResource` with live preview, `IssuedDocumentResource`,
  "Issue document" action on `MemberResource`, bulk issue
- Public verification page: valid / revoked / not-found states
- Member portal: download own documents
- Mailables: `IdCardIssuedMail`, `AppointmentLetterMail`, `CertificateIssuedMail`

**Acceptance criteria**

- [ ] Issuing an ID card queues a job; the PDF exists within 30 seconds
- [ ] Scanning the QR with a phone opens the verify page showing member name, code, designation, status
- [ ] Revoking a document makes the verify page say "REVOKED" with the reason
- [ ] Reissuing marks the old document `superseded` and links `superseded_by_id`
- [ ] `snapshot_data` is populated; changing the member's department afterwards does **not** alter the old PDF
- [ ] **Hindi text renders correctly in all three PDFs** (Noto Sans Devanagari embedded)
- [ ] ID card PDF measures exactly 85.6 × 54 mm
- [ ] Bulk-issuing 50 ID cards completes without timeout or memory exhaustion

> The Hindi-font check is not optional polish. dompdf silently renders unembedded Devanagari as
> empty boxes, and it will be discovered by an angry user in month three otherwise.

---

## Phase 3 — Donations & Payments (Weeks 5–6)

### Sprint 5: Payment abstraction & Razorpay

**Build**

- `donors`, `donations`, `payment_transactions` migrations + models
- `PaymentGateway` interface + DTOs
- `RazorpayGateway` implementation: create order, verify signature, fetch payment, refund
- `FakeGateway` for tests
- `PaymentServiceProvider` binding
- `RazorpayWebhookController` + `VerifyRazorpayWebhook` middleware (HMAC signature check)
- Idempotency via `webhook_events` unique constraint
- Actions: `InitiateDonation`, `RecordSuccessfulDonation`, `RecordFailedDonation`, `RefundDonation`
- Events: `DonationSucceeded`, `DonationFailed`

**Acceptance criteria**

- [ ] A webhook with a bad signature returns 400 and writes nothing
- [ ] The **same** webhook delivered 5 times produces exactly one donation record
- [ ] `payment.captured` moves the donation to `succeeded` and records fee, tax, and `net_amount`
- [ ] `payment.failed` records the error code and description
- [ ] Concurrent webhook + client callback for the same payment produce one record, not two
- [ ] Full donation flow tested end-to-end against `FakeGateway` with no network calls
- [ ] Razorpay **test-mode** payment completes successfully in a browser

### Sprint 6: Public donation flow & instant receipts

**Build**

- Public donation page built on the Phase 0 layout, per `06-UI-UX-FOUNDATION.md` §5 (80G checkbox
  default on, PAN/address revealed, anonymous conflict handled)
- Guest checkout (no account required) with lazy donor creation and dedupe by email
- Razorpay Checkout.js integration including the `ondismiss` path
- Success / failure / pending pages per `06-UI-UX-FOUNDATION.md` §6; pending polls for 20 s
- `AbandonStalePendingDonations` scheduled job
- Browser tests: happy path, modal dismissal, 80G/anonymous conflict, 360 px usability
- Instrument the form: log 80G checked/unchecked and abandonment, so the default-on decision can be
  revisited with data after ~500 donations rather than by opinion
- `receipts`, `receipt_sequences` migrations
- `ReceiptNumberGenerator` with `SELECT ... FOR UPDATE` row locking
- `GenerateDonationReceipt` action + PDF template
- `SendDonationThankYou` listener + Mailable with receipt attached
- Offline donation entry in admin (cash, cheque, bank transfer)
- `DonationResource`, `DonorResource` in Filament
- Donor portal: donation history, receipt downloads

**Acceptance criteria**

- [ ] A guest donates ₹500 and receives a receipt email within **5 minutes** on shared hosting
      (target: 60 seconds on a VPS)
- [ ] Dismissing the Checkout modal keeps the donor on the page with their fields intact
- [ ] A donation whose callback is lost still completes via webhook, and the pending page says so
- [ ] Donating again with the same email links to the existing donor, not a duplicate
- [ ] **100 concurrent donations produce 100 unique, gap-free receipt numbers** (load-tested)
- [ ] **Cold start: with `receipt_sequences` empty, concurrent donations do not throw a unique-key
      violation** — the Apr 1 / fresh-install race the test above cannot reach, because by then the
      sequence row already exists
- [ ] The amount is read server-side; tampering with the client-side amount field is rejected
- [ ] Offline cash donation over ₹2,000 sets `eligible_for_80g = false` and warns the admin
- [ ] Receipt PDF shows amount in both figures and words, correctly
- [ ] Failed payment shows a clear retry path, and no receipt is issued
- [ ] `donor.total_donated` and `donation_count` update on success

> The 100-concurrent-donations test is the single most important test in this project. Run it with
> `ab` or `k6` against a staging DB. A duplicated 80G receipt number is a compliance failure, and it
> will only ever show up under real traffic.

---

## Phase 4 — Recurring & 80G Compliance (Weeks 7–8)

### Sprint 7: Auto-pay subscriptions

**Build**

- `subscriptions`, `subscription_charges` migrations + models
- Razorpay Subscriptions / UPI Autopay integration
- Actions: `CreateSubscriptionMandate`, `ActivateSubscription`, `RecordSubscriptionCharge`,
  `PauseSubscription`, `CancelSubscription`
- Webhook handlers for `subscription.activated`, `subscription.charged`, `subscription.pending`,
  `subscription.halted`, `subscription.cancelled`
- Full status state machine
- Failure handling: 3 consecutive failures → halt + notify donor and admin
- `SyncSubscriptionStatus` scheduled job (reconciles our state with the gateway daily)
- Donor portal: view, pause, cancel own mandates
- `SubscriptionResource` in Filament
- Mailables: activated, charged, charge-failed, cancelled

**Acceptance criteria**

- [ ] A donor sets up ₹1,000/month UPI Autopay and completes the mandate
- [ ] `subscription.charged` creates a linked `donations` row and a `subscription_charges` row
- [ ] Each successful charge generates its own 80G receipt
- [ ] Donor can cancel from the portal; the mandate is cancelled at the gateway too
- [ ] 3 consecutive failed charges halt the subscription and send both emails
- [ ] `SyncSubscriptionStatus` corrects a deliberately desynced local status
- [ ] Cancelling at the gateway (bank side) is reflected locally within 24h

### Sprint 8: 80G receipts & statutory export

**Build**

- 80G receipt series, separate numbering from the general donation series
- `Generate80GReceipt` action, gated on donor PAN + address completeness
- 80G PDF template: org PAN, 80G number and validity, donor PAN and address, amount in words,
  authorised signatory image, seal
- Donor PAN/address collection prompt on the donation form and in the portal
- `ExportForm10BD` action + `Form10BDExporter` (CSV in the prescribed shape)
- Cash > ₹2,000 → 80G blocked, with a clear reason surfaced in the UI
- Annual receipt consolidation: one summary statement per donor per FY
- Bulk 80G regeneration for a financial year
- FY-wise receipt register with totals

**Acceptance criteria**

- [ ] An 80G receipt contains every field required by §2 of `00-PROJECT-OVERVIEW`
- [ ] A donation without donor PAN produces a normal receipt, **not** an 80G one, and the donor is
      prompted to add their PAN
- [ ] Form 10BD CSV export for FY 2026-27 includes only that FY's eligible donations
- [ ] Cash donation of ₹2,500 cannot be issued an 80G receipt; the UI explains why
- [ ] A cancelled receipt keeps its number — the number is never reused
- [ ] Receipt numbers are gap-free within each FY, verified by a report
- [ ] Receipt series resets to 1 on April 1

---

## Phase 5 — Campaigns & Crowdfunding (Weeks 9–11)

### Sprint 9: Campaign core

**Build**

- `campaign_categories`, `campaigns`, `campaign_updates` migrations + models
- Category seeder (8 causes)
- Actions: `CreateCampaign`, `PublishCampaign`, `PostCampaignUpdate`, `RecalculateCampaignTotals`
- `UpdateCampaignTotals` listener on `DonationSucceeded`
- Nightly `RecalculateAllCampaignTotals` reconciliation job
- `CampaignResource` in Filament with rich-text story editor and image upload
- Public: `/campaigns` listing, `/causes/{category}` landing pages, campaign detail page, progress bar,
  donor wall, updates timeline, social share
- Campaign-scoped donation flow
- SEO (`07-SEO.md`): slugs frozen on publish, `redirects` table + middleware, category `intro_body` and
  per-category meta, admin SEO tab with SERP and WhatsApp previews, `DonateAction` + `BreadcrumbList`,
  OG images generated non-queued

**Acceptance criteria**

- [ ] A donation to a campaign increments `raised_amount` and `donor_count` immediately
- [ ] The nightly reconciler detects and corrects a manually-corrupted total
- [ ] Anonymous donors appear as "Anonymous" on the donor wall
- [ ] `/causes/animals` and a campaign slugged `animals-…` both resolve correctly — no route shadowing
- [ ] Editing a published slug creates a 301 and the old URL still reaches the campaign
- [ ] Campaign page scores ≥ 90 Lighthouse SEO
- [ ] WhatsApp share produces a correct preview card, including for a campaign published one minute ago
- [ ] A completed campaign stops accepting donations

### Sprint 9B: Campaign needs catalogue & cart

Full specification: `M08 §Products`.

**Build**

- `campaign_products`, `campaign_stats`, `campaign_faqs`, `donation_items` migrations + models
- Product cards with per-item progress and `− 1 +` steppers; live total feeding the sticky donation card
- `InitiateDonation` extended: quantities in, server-recomputed line totals out, `unit_price`
  snapshotted onto `donation_items`
- Price-changed-since-page-load confirmation before the gateway opens
- `units_funded` incremented on success, decremented on refund, recomputed by the nightly reconciler
- `CampaignProductResource`; `campaign_stats` and `campaign_faqs` as repeaters on `CampaignResource`
- Global FAQ seeder (the six organisation-level questions) + `FAQPage` JSON-LD
- Campaign checkout modal (same Livewire component as `/donate`), exit-intent guard, UTM capture
- Donor wall `Recent` / `Most Generous` tabs; anchor sub-nav with scroll-spy

**Acceptance criteria**

- [ ] Selecting 2 kits at ₹900 and 1 bag at ₹500 produces a ₹2,300 donation with three correct
      `donation_items` rows
- [ ] **Submitting a tampered `unit_price` or `line_total` is ignored** — the server price is used and
      the donation records the true amount
- [ ] Catalogue items combine with a free amount; both appear as separate summary lines
- [ ] A refund decrements `units_funded` as well as `raised_amount`
- [ ] The nightly reconciler corrects a manually-corrupted `units_funded`
- [ ] Two concurrent donations for the last available unit both succeed
- [ ] A campaign with no products renders with no Products tab and no empty section
- [ ] `FAQPage` validates in the Rich Results Test; no `Product` or `Offer` markup anywhere

> The tampering test is this sprint's equivalent of Sprint 6's concurrency test. A catalogue is a
> price list rendered in HTML, and the first thing anyone curious will try is editing it.

### Sprint 10: Fundraiser requests & monthly campaigns

**Build**

- `fundraiser_requests` migration + public form with document upload
- Admin review workflow: new → under review → approved (creates campaign) / rejected
- `ApproveFundraiserRequest` action
- `/monthly-giving` page listing `allows_recurring` campaigns
- Featured-campaign carousel management
- Campaign update → notify donors flow
- Homepage campaign widgets

**Acceptance criteria**

- [ ] A public fundraiser request lands in admin and emails the admin team
- [ ] Approving one creates a draft campaign pre-filled from the request
- [ ] Rejecting sends a courteous email with the reason
- [ ] Publishing an update with `notify_donors` emails every donor to that campaign, once each
- [ ] `/monthly-giving` shows only recurring-enabled campaigns

---

## Phase 6 — Public Website & Communication (Weeks 12–13)

### Sprint 11: Public website & CMS

**Build**

> **Scope reduced.** The public layout, navigation, footer and component library ship in **Phase 0**.
> This sprint is CMS, content and SEO — it consumes that foundation rather than creating it.

- `pages`, `posts`, `testimonials`, `press_mentions`, `impact_stats`, `banners`, `contact_messages`
  migrations + resources
- `homepage` settings group + `HomepageContentSeeder`
- Homepage: all 12 sections wired to the sources named in M10 — hero from `banners` (first slide
  server-rendered), featured campaigns, impact stats, category browse, how-to-donate steps, press
  logos, testimonials, newsletter
- Blog index, enum-backed category filter, post page
- Static pages: About, Privacy Policy, Terms
- Contact form with honeypot, rate limiting, and admin notification
- `spatie/laravel-sitemap`, `robots.txt`, canonical URLs, indexing policy (M10)
- Image optimisation, lazy loading, WebP; OG and card conversions `nonQueued()`

**Acceptance criteria**

- [ ] Homepage Lighthouse mobile: performance ≥ 90, SEO ≥ 95, accessibility ≥ 90
- [ ] Every page is usable at 360 px width
- [ ] Sitemap auto-includes new campaigns and posts; `/portal`, `/admin`, `/verify` are excluded
- [ ] All content in §4 of `00-PROJECT-OVERVIEW` is admin-editable — no hard-coded copy
- [ ] Largest Contentful Paint under 2.5 s on a throttled 4G connection
- [ ] CLS under 0.1 with the hero rotating and the impact counters animating
- [ ] A campaign published one minute ago has a working WhatsApp preview (OG image already generated)

### Sprint 12: Notices & email engine

**Build**

- `notices`, `notice_recipients`, `email_templates`, `subscribers` migrations + models
- `PublishNotice` + `DispatchNoticeToRecipients` actions
- Audience targeting: all members / by department / by designation / specific users / all donors
- `SendNoticeEmail` queued job, chunked and throttled
- `NoticeResource` in Filament with recipient preview and count
- Member portal notice inbox with read tracking
- Email template management UI with variable documentation and test-send
- Newsletter double opt-in + one-click unsubscribe
- Every transactional email routed through `email_templates`

**Acceptance criteria**

- [ ] A notice to "all active members" reaches exactly the right recipient set
- [ ] Sending to 500 members does not time out and does not trip the SMTP rate limit
- [ ] Read tracking updates when a member opens the notice in the portal
- [ ] Editing the `donation.thank_you` template changes the next real email
- [ ] Test-send delivers to the admin with variables substituted
- [ ] Unsubscribe link works in one click and is honoured on subsequent sends
- [ ] Failed emails are recorded with a reason, not silently dropped

---

## Phase 7 — Manager Panel & Reports (Weeks 14–15)

### Sprint 13: Manager panel

**Build**

- `ManagerPanelProvider` at `/manager`
- Department-scoped `MemberResource`, `NoticeResource`, `IssuedDocumentResource` — **one scope per
  resource**, written against that table's own columns (`M11 §Scoping`). Only `members` has a
  `department_id`; documents scope through `member`, notices through creator + audience filter.
- `DepartmentOverview` dashboard widget
- Full policy layer across all models
- Permission matrix wired to the 5 roles
- Manager can: view/edit own-department members, issue documents, publish scoped notices,
  view (not edit) donations
- Role and permission management UI for `super-admin`

**Acceptance criteria**

- [ ] A manager sees only their department's members — verified by an automated test
- [ ] Manipulating a URL ID to reach another department's member returns 403
- [ ] Manager cannot access `/admin` at all
- [ ] Manager cannot edit donations or settings
- [ ] Every model has a policy; no resource relies on navigation-hiding alone for security —
      asserted by a test that iterates `app/Models` and checks `Gate::getPolicyFor()`
- [ ] Permission changes take effect without re-login (or force re-login deliberately)

### Sprint 14: Reports & analytics

**Build**

- Admin dashboard widgets: today/MTD/YTD donations, active subscriptions, monthly trend chart,
  campaign progress, recent donations, member growth
- Reports: donation ledger (filterable), donor summary, FY receipt register, subscription health
  (active/halted/cancelled), campaign performance, member roster, document issue log
- Exports via `maatwebsite/excel`: CSV + XLSX for all reports
- Form 10BD export surfaced in the reports section
- Scheduled monthly summary email to admins

**Acceptance criteria**

- [ ] Dashboard loads in under 2 s with 10,000 donations seeded (`DemoDataSeeder` provides them)
- [ ] Every report exports to both CSV and XLSX with correct headers
- [ ] Gap detection queries `receipts.sequence_number`, not a parsed `receipt_number` string
- [ ] Report totals reconcile exactly against raw DB sums
- [ ] Date-range filters respect the Apr–Mar financial year
- [ ] Exporting 10,000 rows streams rather than exhausting memory

---

## Phase 8 — Hardening & Launch (Weeks 16–17)

### Sprint 15: Security, performance, testing

**Build**

- Security pass: OWASP top 10, rate limiting on donation and auth endpoints, CSRF everywhere,
  XSS audit of all rich-text output, SQL injection audit, file-upload validation (type, size, content)
- Rate limit: donation endpoint, login, password reset, webhook
- N+1 query elimination (Debugbar across every page)
- DB index review against real query patterns
- Cache: config, routes, views, campaign listings
- Test coverage target: ≥ 70% overall, **100% on payment, receipt-numbering, and subscription paths**
- Load test: 100 concurrent donations, 1,000 concurrent campaign-page views
- Backup strategy: nightly DB dump + off-site copy, documented restore procedure
- Error monitoring (Sentry or Flare)
- **Full SEO audit against `07-SEO.md` §6** — every JSON-LD type through the Rich Results Test, a
  spider crawl to catch orphan pages and broken internal links, and Core Web Vitals (LCP, CLS **and
  INP**) measured on a real mid-range Android over 4G

**Acceptance criteria**

- [ ] No N+1 queries on any public page
- [ ] Rate limiting blocks a 100-requests-per-minute donation flood
- [ ] Uploading a `.php` renamed to `.jpg` is rejected
- [ ] Rich text from the CMS cannot execute injected script
- [ ] Payment, receipt, and subscription code paths are at 100% test coverage
- [ ] A restore from backup into a clean DB succeeds — **actually perform this, don't assume it**
- [ ] `php artisan route:list` shows no unintended public routes
- [ ] Every `07-SEO.md` §6 checkbox passes; no `noindex` URL appears in any sitemap
- [ ] INP under 200 ms on the category filter and the donate button

### Sprint 16: Deployment, UAT, launch

**Build**

- Hostinger production setup per `04-DEPLOYMENT-HOSTINGER.md`
- SSL, domain, DNS
- Cron: scheduler + queue worker
- Razorpay live keys and live webhook URL
- Production mail (SMTP or transactional provider — **not** shared-hosting default mail)
- Seed real organisation settings, real 80G details, real content
- UAT script covering all 8 success criteria from `00-PROJECT-OVERVIEW` §8
- Client training: 2 sessions (admin, manager) + a short written guide
- Google Analytics / Search Console, sitemap submission
- Soft launch with a small real donation, then full launch

**Acceptance criteria**

- [ ] A real ₹10 donation succeeds in production and produces a valid 80G receipt
- [ ] Queue worker survives 24h without stalling
- [ ] Scheduled jobs run on time (verified in logs)
- [ ] SSL A-grade on SSL Labs
- [ ] Admin and manager have completed training and can operate unaided
- [ ] Backups are running and verified
- [ ] Error monitoring is live and alerting

---

## Parallelisation with two developers

| | Dev A (backend-leaning) | Dev B (frontend-leaning) |
|---|---|---|
| Week 0 | Repo, CI, Razorpay application, package spikes | **Phase 0** — tokens, components, layout, wireframes |
| Weeks 1–2 | Sprint 1 + 2 | Filament theme, portal shell, `/dev/components` gallery |
| Weeks 3–4 | Sprint 3 (members) | Sprint 4 (document engine + PDF templates) |
| Weeks 5–6 | Sprint 5 (payments) | Sprint 6 UI + campaign page markup |
| Weeks 7–8 | Sprint 7 + 8 (recurring, 80G) | Sprint 11 (public site) |
| Weeks 9–11 | Sprint 9 + 9B + 10 (campaigns, catalogue) | Sprint 12 (notices, email templates) |
| Weeks 12–13 | Sprint 13 + 14 | Portal UI, polish, responsive QA |
| Weeks 14–15 | Sprint 15 + 16 | Content entry, UAT support, training material |

Sprint 5 (payments) should not be parallelised across two people. One owner, full concentration.

Phase 0 was previously buried in this table as Dev B filler work in weeks 1–2 and did not exist at
all in the solo plan. It is now a first-class phase, because every HTML-rendering sprint depends on
it.

---

## Risk register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Razorpay recurring approval delayed | Medium | High | Apply for UPI Autopay in **week 1**, not week 7. Approval can take 2–3 weeks. |
| dompdf can't render the desired design | Medium | Medium | Prototype the ID card in Sprint 4. Fallback: `spatie/browsershot` (needs VPS). |
| Duplicate receipt numbers under load | Low | **Critical** | Row-level locking + the 100-concurrent load test in Sprint 6. |
| Queue worker stalls on shared hosting | Medium | High | `--stop-when-empty` from cron + a stall-detection alert. Escalate to VPS if it recurs. |
| Client changes 80G details mid-build | Medium | Low | Everything is in `settings`, nothing hard-coded. |
| Scope creep (SMS, WhatsApp API, mobile app) | High | Medium | Out-of-scope list in `00-PROJECT-OVERVIEW` §6 is the contract. Everything else is v2. |
| Shared hosting can't handle campaign traffic spikes | Medium | Medium | Aggressive caching + CDN for images. Budget for VPS migration. |
| Client content not ready at launch | High | Low | Seed placeholder content in Sprint 11; start collecting real content in week 9. |

---

## Definition of Done (every sprint)

1. Code merged to `develop` via reviewed PR
2. Pint clean, Larastan level 5 clean
3. Feature tests written and passing
4. All acceptance criteria checked off
5. Migrations run cleanly on a fresh database
6. Deployed to staging and manually smoke-tested
7. Any new `.env` key added to `.env.example` and documented
8. Module doc in `docs/modules/` updated if the design changed

---

Next: [`04-DEPLOYMENT-HOSTINGER.md`](04-DEPLOYMENT-HOSTINGER.md) · Module specs in [`modules/`](modules/)
