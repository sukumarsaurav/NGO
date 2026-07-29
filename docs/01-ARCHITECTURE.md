# 01 — Architecture & Project Structure

## 1. The layered rule

One rule governs everything below:

> **Controllers and Filament Resources are thin. They validate input, call one Action, and return a
> response. All business logic lives in Actions and Services. Models hold relations, casts, scopes —
> never business logic.**

This is what keeps a project with 12 modules from turning into 4,000-line controllers.

```
HTTP Request
    │
    ▼
Route ──► Middleware ──► FormRequest (validation)
    │
    ▼
Controller / Filament Resource / Livewire Component   ← thin, no logic
    │
    ▼
Action  (single public method: handle())               ← one business operation
    │
    ├──► Service   (reusable capability: PDF, QR, payment gateway, receipt numbering)
    ├──► Model     (Eloquent: relations, casts, scopes)
    └──► Event ──► Listener ──► Queued Job / Mailable
    │
    ▼
Response (Blade view / redirect / JSON)
```

**Action vs Service — the distinction that matters:**

- An **Action** is a business operation, named after what the user does:
  `RecordDonation`, `IssueIdCard`, `ActivateSubscription`. One public `handle()` method. Not reusable
  across contexts by design — it *is* the use case.
- A **Service** is a technical capability with no business opinion:
  `PdfRenderer`, `QrCodeGenerator`, `RazorpayGateway`, `ReceiptNumberGenerator`. Reused by many Actions.

If you catch yourself writing `class DonationService` with fifteen methods, you wanted Actions.

## 2. Panels

Three distinct entry points, three distinct auth guards' worth of separation:

| Panel | URL | Built with | For |
|---|---|---|---|
| Public site | `/` | Blade + Tailwind + Alpine | Visitors, donors |
| Admin panel | `/admin` | Filament v5 | `super-admin`, `admin` |
| Manager panel | `/manager` | Filament v5 (second panel, scoped) | `manager` |
| Member portal | `/portal` | Blade + Livewire | `member`, `donor` |

Filament v5 runs on Livewire v4. The panel, resource, and widget concepts are unchanged from v3/v4,
so the folder layout below holds — but the v4 release did change parts of the schema/component API.
**Read the Filament v5 docs before writing your first resource** rather than pattern-matching from
older tutorials, most of which are still v3.

**Why manager gets its own Filament panel rather than permission-gating `/admin`:** a separate panel
means the navigation, dashboard widgets, and default query scopes are all defined once for managers.
Trying to hide half of `/admin` behind policies produces a confusing, leaky UI and is a common source
of accidental data exposure. Two panels, two clear surfaces.

## 3. Module boundaries

Modules are **logical**, not separate Composer packages. We use folder namespacing inside a standard
Laravel app — a `nwidart/laravel-modules` setup would add friction disproportionate to a single-team
project this size.

Each module owns:
- its models and migrations
- its actions
- its Filament resources
- its policies
- its tests

Cross-module communication happens through **events**, not direct calls, wherever the coupling would
otherwise be circular. Example: `M05 Donations` fires `DonationSucceeded`. `M07 Receipts` listens and
generates the 80G receipt. `M09 Communication` listens and sends the thank-you email. `M08 Campaigns`
listens and bumps the raised total. Donations knows about none of them.

```
┌────────────────────────────────────────────────────────────┐
│  M01 Foundation & Auth   │  M02 Organisation Settings      │  ← everything depends on these
└────────────────────────────────────────────────────────────┘
            │                                │
    ┌───────┴────────┐              ┌────────┴────────┐
    ▼                ▼              ▼                 ▼
┌─────────┐   ┌──────────────┐  ┌──────────┐  ┌──────────────┐
│ M03     │──►│ M04 Document │  │ M05      │─►│ M07 Receipts │
│ Members │   │    Engine    │  │ Donations│  │    & 80G     │
└─────────┘   └──────────────┘  └────┬─────┘  └──────────────┘
     │                                │
     │                          ┌─────┴──────┐
     │                          ▼            ▼
     │                   ┌──────────┐  ┌──────────────┐
     │                   │ M06      │  │ M08          │
     │                   │ Recurring│  │ Campaigns    │
     │                   └──────────┘  └──────────────┘
     │                                        │
     ▼                                        ▼
┌──────────────────┐                 ┌──────────────────┐
│ M09 Notices &    │◄────────────────│ M10 Public Site  │
│    Communication │                 │     & CMS        │
└──────────────────┘                 └──────────────────┘
            │                                │
            └──────────┬─────────────────────┘
                       ▼
        ┌──────────────────────────────┐
        │ M11 Manager Panel │ M12 Reports│
        └──────────────────────────────┘
```

## 4. Full folder structure

```
vgwgf-platform/
├── app/
│   ├── Actions/                         # business operations, one per use case
│   │   ├── Donations/
│   │   │   ├── InitiateDonation.php
│   │   │   ├── RecordSuccessfulDonation.php
│   │   │   ├── RecordFailedDonation.php
│   │   │   └── RefundDonation.php
│   │   ├── Subscriptions/
│   │   │   ├── CreateSubscriptionMandate.php
│   │   │   ├── ActivateSubscription.php
│   │   │   ├── RecordSubscriptionCharge.php
│   │   │   ├── PauseSubscription.php
│   │   │   └── CancelSubscription.php
│   │   ├── Members/
│   │   │   ├── CreateMember.php
│   │   │   ├── UpdateMember.php
│   │   │   ├── AssignDesignation.php
│   │   │   ├── DeactivateMember.php
│   │   │   └── BulkImportMembers.php
│   │   ├── Documents/
│   │   │   ├── IssueIdCard.php
│   │   │   ├── IssueAppointmentLetter.php
│   │   │   ├── IssueCertificate.php
│   │   │   ├── RevokeDocument.php
│   │   │   └── RegenerateDocument.php
│   │   ├── Receipts/
│   │   │   ├── GenerateDonationReceipt.php
│   │   │   ├── Generate80GReceipt.php
│   │   │   └── ExportForm10BD.php
│   │   ├── Campaigns/
│   │   │   ├── CreateCampaign.php
│   │   │   ├── PublishCampaign.php
│   │   │   ├── PostCampaignUpdate.php
│   │   │   ├── RecalculateCampaignTotals.php
│   │   │   └── ApproveFundraiserRequest.php
│   │   └── Notices/
│   │       ├── PublishNotice.php
│   │       └── DispatchNoticeToRecipients.php
│   │
│   ├── Services/                        # technical capabilities, no business opinion
│   │   ├── Payment/
│   │   │   ├── PaymentGateway.php            # interface — donation code depends on THIS
│   │   │   ├── RazorpayGateway.php
│   │   │   ├── FakeGateway.php               # used in tests
│   │   │   ├── DTO/
│   │   │   │   ├── PaymentIntent.php
│   │   │   │   ├── PaymentResult.php
│   │   │   │   ├── MandateRequest.php
│   │   │   │   └── WebhookEvent.php
│   │   │   └── WebhookSignatureVerifier.php
│   │   ├── Pdf/
│   │   │   ├── PdfRenderer.php
│   │   │   └── Templates/
│   │   │       ├── IdCardTemplate.php
│   │   │       ├── AppointmentLetterTemplate.php
│   │   │       ├── CertificateTemplate.php
│   │   │       ├── DonationReceiptTemplate.php
│   │   │       └── Receipt80GTemplate.php
│   │   ├── Qr/
│   │   │   ├── QrCodeGenerator.php
│   │   │   └── DocumentVerifier.php
│   │   ├── Numbering/
│   │   │   ├── ReceiptNumberGenerator.php    # FY-scoped, gap-free, row-locked
│   │   │   ├── MemberCodeGenerator.php
│   │   │   └── DocumentNumberGenerator.php
│   │   ├── Settings/
│   │   │   └── SettingsRepository.php        # cached DB-backed settings
│   │   └── Export/
│   │       ├── Form10BDExporter.php
│   │       └── DonationLedgerExporter.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Member.php
│   │   ├── Designation.php
│   │   ├── Department.php
│   │   ├── IssuedDocument.php
│   │   ├── DocumentTemplate.php
│   │   ├── Donation.php
│   │   ├── Donor.php
│   │   ├── PaymentTransaction.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionCharge.php
│   │   ├── Receipt.php
│   │   ├── ReceiptSequence.php
│   │   ├── Campaign.php
│   │   ├── CampaignCategory.php
│   │   ├── CampaignUpdate.php
│   │   ├── FundraiserRequest.php
│   │   ├── Notice.php
│   │   ├── NoticeRecipient.php
│   │   ├── EmailTemplate.php
│   │   ├── Page.php
│   │   ├── Post.php
│   │   ├── Testimonial.php
│   │   ├── PressMention.php
│   │   ├── ImpactStat.php
│   │   ├── Subscriber.php
│   │   ├── Setting.php
│   │   └── Concerns/
│   │       ├── HasUuid.php
│   │       ├── Auditable.php
│   │       └── HasMediaCollection.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Public/
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── CampaignController.php
│   │   │   │   ├── DonationController.php
│   │   │   │   ├── FundraiserRequestController.php
│   │   │   │   ├── BlogController.php
│   │   │   │   ├── PageController.php
│   │   │   │   ├── SubscriberController.php
│   │   │   │   └── DocumentVerificationController.php   # QR landing page
│   │   │   ├── Portal/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── MyDocumentsController.php
│   │   │   │   ├── MyDonationsController.php
│   │   │   │   ├── MySubscriptionsController.php
│   │   │   │   └── MyNoticesController.php
│   │   │   └── Webhooks/
│   │   │       └── RazorpayWebhookController.php
│   │   ├── Requests/
│   │   │   ├── StoreDonationRequest.php
│   │   │   ├── StoreFundraiserRequest.php
│   │   │   └── ...
│   │   └── Middleware/
│   │       ├── VerifyRazorpayWebhook.php
│   │       └── EnsureMemberIsActive.php
│   │
│   ├── Filament/
│   │   ├── Admin/                       # /admin panel
│   │   │   ├── Resources/
│   │   │   │   ├── MemberResource.php
│   │   │   │   ├── DonationResource.php
│   │   │   │   ├── DonorResource.php
│   │   │   │   ├── SubscriptionResource.php
│   │   │   │   ├── ReceiptResource.php
│   │   │   │   ├── CampaignResource.php
│   │   │   │   ├── FundraiserRequestResource.php
│   │   │   │   ├── NoticeResource.php
│   │   │   │   ├── IssuedDocumentResource.php
│   │   │   │   ├── DocumentTemplateResource.php
│   │   │   │   ├── PageResource.php
│   │   │   │   ├── PostResource.php
│   │   │   │   ├── UserResource.php
│   │   │   │   └── ...
│   │   │   ├── Pages/
│   │   │   │   ├── Dashboard.php
│   │   │   │   ├── OrganisationSettings.php
│   │   │   │   └── Form10BDExport.php
│   │   │   └── Widgets/
│   │   │       ├── DonationStatsOverview.php
│   │   │       ├── MonthlyDonationChart.php
│   │   │       ├── RecentDonationsTable.php
│   │   │       ├── ActiveSubscriptionsWidget.php
│   │   │       └── CampaignProgressWidget.php
│   │   └── Manager/                     # /manager panel — scoped subset
│   │       ├── Resources/
│   │       │   ├── MemberResource.php        # scoped to manager's department
│   │       │   ├── NoticeResource.php
│   │       │   └── IssuedDocumentResource.php
│   │       └── Widgets/
│   │           └── DepartmentOverview.php
│   │
│   ├── Livewire/
│   │   ├── DonationForm.php
│   │   ├── CampaignFilter.php
│   │   └── Portal/
│   │       └── SubscriptionManager.php
│   │
│   ├── Events/
│   │   ├── DonationSucceeded.php
│   │   ├── DonationFailed.php
│   │   ├── SubscriptionActivated.php
│   │   ├── SubscriptionCharged.php
│   │   ├── SubscriptionCancelled.php
│   │   ├── MemberCreated.php
│   │   ├── DesignationAssigned.php
│   │   ├── DocumentIssued.php
│   │   ├── NoticePublished.php
│   │   └── FundraiserRequestSubmitted.php
│   │
│   ├── Listeners/
│   │   ├── SendDonationThankYou.php
│   │   ├── GenerateReceiptForDonation.php
│   │   ├── UpdateCampaignTotals.php
│   │   ├── IssueIdCardOnMemberCreated.php
│   │   ├── IssueLetterOnDesignationAssigned.php
│   │   └── NotifyAdminOfFundraiserRequest.php
│   │
│   ├── Jobs/
│   │   ├── GeneratePdfDocument.php
│   │   ├── SendNoticeEmail.php
│   │   ├── SyncSubscriptionStatus.php
│   │   ├── RetryFailedSubscriptionCharge.php
│   │   └── RecalculateAllCampaignTotals.php
│   │
│   ├── Mail/
│   │   ├── DonationThankYouMail.php
│   │   ├── DonationReceiptMail.php
│   │   ├── Receipt80GMail.php
│   │   ├── IdCardIssuedMail.php
│   │   ├── AppointmentLetterMail.php
│   │   ├── CertificateIssuedMail.php
│   │   ├── NoticeMail.php
│   │   ├── SubscriptionActivatedMail.php
│   │   ├── SubscriptionChargeFailedMail.php
│   │   └── FundraiserRequestReceivedMail.php
│   │
│   ├── Policies/
│   │   ├── MemberPolicy.php
│   │   ├── DonationPolicy.php
│   │   ├── CampaignPolicy.php
│   │   ├── NoticePolicy.php
│   │   └── IssuedDocumentPolicy.php
│   │
│   ├── Enums/
│   │   ├── DonationStatus.php
│   │   ├── PaymentMode.php
│   │   ├── SubscriptionStatus.php
│   │   ├── DocumentType.php
│   │   ├── DocumentStatus.php
│   │   ├── CampaignStatus.php
│   │   ├── MemberStatus.php
│   │   └── NoticeAudience.php
│   │
│   ├── Support/
│   │   ├── Money.php                    # amounts in paise, never floats
│   │   ├── FinancialYear.php            # Apr 1 – Mar 31 helpers
│   │   └── NumberToWords.php            # "₹ Five Thousand Only" for receipts
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── EventServiceProvider.php
│       ├── AuthServiceProvider.php
│       ├── PaymentServiceProvider.php   # binds PaymentGateway → RazorpayGateway
│       └── Filament/
│           ├── AdminPanelProvider.php
│           └── ManagerPanelProvider.php
│
├── config/
│   ├── ngo.php                          # non-secret app config
│   ├── razorpay.php
│   └── documents.php                    # PDF page sizes, template paths
│
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolePermissionSeeder.php
│       ├── AdminUserSeeder.php
│       ├── SettingsSeeder.php
│       ├── CampaignCategorySeeder.php
│       ├── EmailTemplateSeeder.php
│       └── DocumentTemplateSeeder.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── public.blade.php
│   │   │   └── portal.blade.php
│   │   ├── components/
│   │   │   ├── campaign-card.blade.php
│   │   │   ├── progress-bar.blade.php
│   │   │   ├── donate-button.blade.php
│   │   │   ├── tax-benefit-badge.blade.php
│   │   │   └── share-buttons.blade.php
│   │   ├── public/
│   │   │   ├── home.blade.php
│   │   │   ├── campaigns/
│   │   │   │   ├── index.blade.php
│   │   │   │   ├── show.blade.php
│   │   │   │   └── monthly.blade.php
│   │   │   ├── donate/
│   │   │   │   ├── form.blade.php
│   │   │   │   ├── success.blade.php
│   │   │   │   └── failed.blade.php
│   │   │   ├── fundraiser/request.blade.php
│   │   │   ├── blog/
│   │   │   ├── verify/document.blade.php     # QR scan landing
│   │   │   └── pages/show.blade.php
│   │   ├── portal/
│   │   ├── pdf/                              # PDF-only Blade templates
│   │   │   ├── id-card.blade.php
│   │   │   ├── appointment-letter.blade.php
│   │   │   ├── certificate.blade.php
│   │   │   ├── donation-receipt.blade.php
│   │   │   └── receipt-80g.blade.php
│   │   └── emails/
│   ├── css/app.css
│   └── js/app.js
│
├── routes/
│   ├── web.php
│   ├── portal.php
│   ├── webhooks.php
│   └── console.php
│
├── storage/app/public/org/{slug}/
│   ├── documents/                       # generated PDFs
│   ├── receipts/
│   ├── campaigns/
│   └── members/
│
├── tests/
│   ├── Feature/
│   │   ├── Donations/
│   │   ├── Subscriptions/
│   │   ├── Documents/
│   │   ├── Receipts/
│   │   └── Campaigns/
│   └── Unit/
│       ├── ReceiptNumberGeneratorTest.php
│       ├── FinancialYearTest.php
│       └── NumberToWordsTest.php
│
└── docs/                                # you are here
```

## 5. Non-negotiable technical rules

**Money is stored in paise as `BIGINT`. Never `FLOAT`, never `DECIMAL` in application code.**
`₹1,500.50` is stored as `150050`. All arithmetic goes through `App\Support\Money`. Formatting for
display happens at the Blade layer only. Floating-point rupees will eventually produce a receipt that
says ₹999.9999999 and that receipt is a legal document.

**Every payment write is idempotent.** Razorpay will deliver the same webhook more than once — this
is normal, documented behaviour, not a bug. Every webhook handler must key on the gateway's event ID,
store processed IDs in `webhook_events`, and no-op on repeats. Wrap donation writes in a transaction
with a `SELECT ... FOR UPDATE` on the donation row.

**Receipt numbers are generated inside a transaction with a row lock, against a row that already
exists.** Two simultaneous donations must never receive the same 80G receipt number, and the sequence
must have no gaps within a financial year. This is the single most likely place for a
compliance-breaking race condition — and there are two of them: the obvious lost-update on the
counter, and the subtler one where `lockForUpdate()` is called on a sequence row that has not been
created yet and therefore locks nothing.

`modules/M07-receipts-80g.md` §`ReceiptNumberGenerator` is the **canonical implementation**. Do not
restate it here or anywhere else; earlier versions of this file and `05-CONVENTIONS.md` each carried a
paraphrase, and the paraphrases drifted into different bugs.

**PDFs are generated in queued jobs, never in the request cycle.** dompdf on shared hosting can take
2–5 seconds. The user gets an immediate "your receipt is on its way" and the email arrives seconds
later. The only exception is an on-demand download of an already-generated PDF.

**Never trust the amount from the client.** The donation amount is read from the campaign / preset
options server-side, or validated against min/max bounds. A hidden form field is not a source of truth.

The same applies to the **needs catalogue** (`M08 §Products`). The client submits
`{product_id, quantity}` pairs; the server re-reads every `unit_price` from `campaign_products`,
computes each `line_total` and the order total itself, and snapshots the price onto `donation_items`.
Quantities come from the client. Money never does.

**All `.env` secrets stay out of git.** Razorpay keys, webhook secret, mail credentials, `APP_KEY`.
`.env.example` documents every key with a dummy value.

**Enums, not magic strings.** `DonationStatus::Succeeded`, not `'succeeded'`. Backed string enums,
cast on the model.

## 6. Package list

Versions below were current as of **July 2026**. Pin whatever `composer require` actually resolves —
don't copy these constraints blindly a year from now.

```jsonc
// composer.json — require
"php": "^8.3",                           // Laravel 13 minimum
"laravel/framework": "^13.0",
"filament/filament": "^5.0",             // Filament v5 (built on Livewire v4)
"spatie/laravel-permission": "^8.0",     // roles & permissions
"spatie/laravel-medialibrary": "^11.0",  // images, uploads
"spatie/laravel-activitylog": "^4.8",    // audit trail
"spatie/laravel-sitemap": "^7.0",        // SEO
"barryvdh/laravel-dompdf": "^3.0",       // PDF generation
"simplesoftwareio/simple-qrcode": "^4.2",// QR codes
"razorpay/razorpay": "^2.9",             // payment gateway SDK
"maatwebsite/excel": "^3.1",             // CSV / XLSX exports
"spatie/laravel-backup": "^9.0",         // nightly backups — required by 04-DEPLOYMENT §Backups
"laravel/sanctum": "^4.0",               // API tokens (future mobile app)
"intervention/image": "^3.0"             // image resizing for cards

// require-dev
"pestphp/pest": "^3.0",
"pestphp/pest-plugin-laravel": "^3.0",
"laravel/pint": "^1.0",                  // code style
"larastan/larastan": "^3.0",             // static analysis
"barryvdh/laravel-debugbar": "^3.9"
```

Frontend: `tailwindcss`, `alpinejs`, `vite`, `@tailwindcss/typography`, `@tailwindcss/forms`.
Livewire v4 ships as a Filament v5 dependency — don't install it separately.

**Verify before you start:** run `composer require` for each package and confirm it resolves against
Laravel 13. Most of the ecosystem had caught up by mid-2026, but check `razorpay/razorpay` and
`simplesoftwareio/simple-qrcode` specifically — smaller packages lag major framework releases, and
you don't want to discover an incompatibility in Sprint 5.

## 7. Where to be careful

A few places in this design are genuinely tricky, flagged now so they don't surprise anyone in
week six:

1. **Receipt numbering under concurrency** — `M07`, §4. Get this right early; retrofitting is painful.
2. **Razorpay recurring mandates** — the state machine (`created → authenticated → active → paused →
   halted → cancelled`) is more complicated than one-time payments, and charges can fail months
   later. `M06` covers the full flow.
3. **dompdf and Unicode/Hindi text** — dompdf needs an explicitly embedded font (e.g. Noto Sans
   Devanagari) or Hindi renders as boxes. Test this in Phase 2, not at launch.
4. **Queue reliability on Hostinger shared hosting** — there's no Supervisor. We run
   `queue:work --stop-when-empty` from cron every minute. `M04` and `04-DEPLOYMENT` explain the
   trade-offs and when to move to a VPS.
5. **Campaign totals** — denormalised `raised_amount` and `donor_count` columns for fast listings,
   kept in sync by an event listener, with a nightly reconciliation job as a safety net. Never trust
   a denormalised counter without a reconciler.

---

Next: [`02-DATABASE-SCHEMA.md`](02-DATABASE-SCHEMA.md)
