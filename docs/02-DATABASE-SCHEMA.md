# 02 — Database Schema

MySQL 8, InnoDB, `utf8mb4_unicode_ci`. All tables have `created_at` / `updated_at` unless noted.
Soft deletes (`deleted_at`) only where stated — financial records are **never** soft-deleted or
hard-deleted, only status-changed.

**Money convention:** every amount column is `BIGINT UNSIGNED`, stored in **paise**.
`amount = 150050` means ₹1,500.50. No floats anywhere.

---

## 1. ERD (logical)

```
users ─┬─1:1─ members ──1:n── issued_documents
       │         │
       │         └──n:1── departments, designations
       │
       └─1:1─ donors ──1:n── donations ──1:1── receipts
                   │             │
                   │             ├──n:1── campaigns ──n:1── campaign_categories
                   │             │            ├──1:n── campaign_updates
                   │             │            ├──1:n── campaign_stats, campaign_faqs
                   │             │            └──1:n── campaign_products ──1:n── donation_items
                   │             │                                                    │
                   │             └──1:n── donation_items ─────────────────────────────┘
                   │             │
                   │             └──1:n── payment_transactions
                   │
                   └──1:n── subscriptions ──1:n── subscription_charges
                                                        │
                                                        └──1:1── donations

notices ──1:n── notice_recipients ──n:1── users
settings          (singleton key/value)
receipt_sequences (one row per series per FY — the locking table)
webhook_events    (idempotency guard)
activity_log      (spatie audit trail)
```

---

## 2. Foundation & Auth (M01)

### `users`
Every human who can log in. Members, donors, staff all get a `users` row.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `uuid` | CHAR(36) UNIQUE | used in public URLs, never expose `id` |
| `name` | VARCHAR(150) | |
| `email` | VARCHAR(190) UNIQUE | |
| `phone` | VARCHAR(20) NULL, INDEX | E.164 preferred |
| `email_verified_at` | TIMESTAMP NULL | |
| `phone_verified_at` | TIMESTAMP NULL | |
| `password` | VARCHAR(255) NULL | null for guest-created donor records |
| `avatar_path` | VARCHAR(255) NULL | |
| `bio` | TEXT NULL | blog byline; feeds `Article.author` in JSON-LD |
| `is_active` | BOOLEAN default 1 | |
| `last_login_at` | TIMESTAMP NULL | |
| `remember_token` | VARCHAR(100) NULL | |
| `deleted_at` | TIMESTAMP NULL | soft delete |

Plus the standard Spatie tables: `roles`, `permissions`, `model_has_roles`,
`model_has_permissions`, `role_has_permissions`.

### `settings` (M02)
DB-backed singleton config. Read through `SettingsRepository`, cached forever, cache busted on write.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `key` | VARCHAR(100) UNIQUE | e.g. `org.name`, `org.pan`, `donation.min_amount` |
| `value` | TEXT NULL | |
| `type` | ENUM('string','int','bool','json','file') | for casting |
| `group` | VARCHAR(50) INDEX | `organisation`, `donation`, `receipt`, `social`, `homepage`, `seo` |
| `is_encrypted` | BOOLEAN default 0 | PAN and similar |

Seeded keys include: `org.name`, `org.legal_name`, `org.logo`, `org.address_line1/2`, `org.city`,
`org.state`, `org.pincode`, `org.email`, `org.phone`, `org.pan`, `org.80g_number`,
`org.80g_valid_from`, `org.80g_valid_to`, `org.12a_number`, `org.registration_number`,
`org.authorised_signatory_name`, `org.signature_image`, `org.seal_image`,
`donation.min_amount`, `donation.preset_amounts`, `donation.currency`,
`receipt.prefix`, `receipt.80g_prefix`, `social.*`, `seo.*`.

### `webhook_events`
Idempotency guard. **Every** gateway webhook writes here first.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `provider` | VARCHAR(30) | `razorpay` |
| `event_id` | VARCHAR(190) | gateway's own event id |
| `event_type` | VARCHAR(80) | `payment.captured`, `subscription.charged`, … |
| `payload` | JSON | raw body, for replay/debugging |
| `status` | ENUM('received','processed','failed','ignored') | |
| `error` | TEXT NULL | |
| `processed_at` | TIMESTAMP NULL | |

**UNIQUE (`provider`, `event_id`)** — this constraint is what makes replays safe.

---

## 3. Members (M03)

### `departments`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `name` | VARCHAR(120) | |
| `slug` | VARCHAR(120) UNIQUE | |
| `parent_id` | BIGINT NULL FK→departments | nested departments |
| `manager_user_id` | BIGINT NULL FK→users | drives manager-panel scoping |
| `is_active` | BOOLEAN default 1 | |

### `designations`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `title` | VARCHAR(120) | President, Secretary, Volunteer Coordinator… |
| `slug` | VARCHAR(120) UNIQUE | |
| `rank` | SMALLINT default 0 | for ordering on org chart |
| `letter_template_id` | BIGINT NULL FK→document_templates | which appointment-letter template |
| `is_active` | BOOLEAN default 1 | |

### `members`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | public identifier |
| `user_id` | BIGINT UNIQUE FK→users | |
| `member_code` | VARCHAR(30) UNIQUE | e.g. `VGWGF-2026-00123`, generated |
| `department_id` | BIGINT NULL FK→departments, INDEX | |
| `designation_id` | BIGINT NULL FK→designations, INDEX | |
| `photo_path` | VARCHAR(255) NULL | used on ID card |
| `date_of_birth` | DATE NULL | |
| `gender` | ENUM('male','female','other') NULL | |
| `blood_group` | VARCHAR(5) NULL | printed on ID card |
| `address_line1` | VARCHAR(190) NULL | |
| `address_line2` | VARCHAR(190) NULL | |
| `city` | VARCHAR(80) NULL | |
| `state` | VARCHAR(80) NULL | |
| `pincode` | VARCHAR(10) NULL | |
| `emergency_contact_name` | VARCHAR(120) NULL | |
| `emergency_contact_phone` | VARCHAR(20) NULL | |
| `id_proof_type` | VARCHAR(40) NULL | Aadhaar, PAN, DL |
| `id_proof_number` | VARCHAR(60) NULL | **encrypted cast** |
| `joined_on` | DATE | |
| `valid_until` | DATE NULL | ID card expiry |
| `status` | ENUM('pending','active','suspended','resigned','expired') default 'pending', INDEX | |
| `notes` | TEXT NULL | internal |
| `deleted_at` | TIMESTAMP NULL | |

Indexes: `(status, department_id)`, `(member_code)`.

---

## 4. Document Engine (M04)

### `document_templates`
Admin-editable Blade/HTML templates for each document type.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `name` | VARCHAR(120) | |
| `type` | ENUM('id_card','appointment_letter','certificate') INDEX | |
| `body_html` | LONGTEXT | Blade-compatible, uses `{{ $member->name }}` etc. |
| `css` | LONGTEXT NULL | |
| `page_size` | VARCHAR(20) default 'A4' | ID cards use `CR80` (85.6 × 54 mm) |
| `orientation` | ENUM('portrait','landscape') default 'portrait' | |
| `background_path` | VARCHAR(255) NULL | pre-designed background image |
| `qr_enabled` | BOOLEAN default 1 | |
| `qr_position` | JSON NULL | `{x, y, size}` in mm |
| `is_default` | BOOLEAN default 0 | one default per type |
| `is_active` | BOOLEAN default 1 | |

### `issued_documents`
Every ID card, letter, and certificate ever generated. Immutable once issued — reissuing creates a
new row and supersedes the old one.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | **this is what the QR code encodes** |
| `document_number` | VARCHAR(40) UNIQUE | e.g. `AL-2026-0042` |
| `type` | ENUM('id_card','appointment_letter','certificate') INDEX | |
| `member_id` | BIGINT FK→members, INDEX | |
| `template_id` | BIGINT FK→document_templates | |
| `title` | VARCHAR(190) | certificate title / letter subject |
| `snapshot_data` | JSON | **member data frozen at issue time** — see note below |
| `file_path` | VARCHAR(255) NULL | generated PDF, null until job completes |
| `qr_payload` | VARCHAR(255) | the verify URL |
| `issued_by_user_id` | BIGINT FK→users | |
| `issued_on` | DATE | |
| `valid_until` | DATE NULL | |
| `status` | ENUM('queued','issued','revoked','superseded') default 'queued', INDEX | |
| `revoked_at` | TIMESTAMP NULL | |
| `revoked_reason` | VARCHAR(255) NULL | |
| `superseded_by_id` | BIGINT NULL FK→issued_documents | |
| `download_count` | INT UNSIGNED default 0 | |
| `verified_count` | INT UNSIGNED default 0 | QR scans |

**Why `snapshot_data`:** if a member changes department in 2027, their 2026 appointment letter must
still show the 2026 department. Regenerating the PDF from live relations would silently rewrite
history. Freeze the data at issue time.

---

## 5. Donations & Payments (M05)

### `donors`
Separate from `members` — most donors are not members. Links to `users` only if they registered.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `user_id` | BIGINT NULL UNIQUE FK→users | null for guest donors |
| `name` | VARCHAR(150) | |
| `email` | VARCHAR(190) INDEX | |
| `phone` | VARCHAR(20) NULL INDEX | |
| `pan` | VARCHAR(20) NULL | **encrypted cast**, needed for 80G |
| `address_line1` | VARCHAR(190) NULL | required on 80G receipt |
| `address_line2` | VARCHAR(190) NULL | |
| `city` | VARCHAR(80) NULL | |
| `state` | VARCHAR(80) NULL | |
| `pincode` | VARCHAR(10) NULL | |
| `country` | VARCHAR(60) default 'India' | |
| `donor_type` | ENUM('individual','company','trust','huf','foreign') default 'individual' | Form 10BD needs this |
| `total_donated` | BIGINT UNSIGNED default 0 | denormalised, reconciled nightly |
| `donation_count` | INT UNSIGNED default 0 | denormalised |
| `first_donated_at` | TIMESTAMP NULL | |
| `last_donated_at` | TIMESTAMP NULL | |
| `is_anonymous` | BOOLEAN default 0 | hide name on public campaign page |
| `marketing_opt_in` | BOOLEAN default 0 | |

Index: `(email)`, `(phone)`.

### `donations`
The business record of a gift. One donation = one intent to give.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `donation_number` | VARCHAR(40) UNIQUE NULL | assigned on success |
| `donor_id` | BIGINT FK→donors, INDEX | |
| `campaign_id` | BIGINT NULL FK→campaigns, INDEX | null = general fund |
| `subscription_id` | BIGINT NULL FK→subscriptions, INDEX | set if this is a recurring instalment |
| `amount` | BIGINT UNSIGNED | paise — **authoritative total** = `items_amount + free_amount` |
| `items_amount` | BIGINT UNSIGNED default 0 | sum of `donation_items.line_total`; lets reporting split catalogue giving from open giving |
| `free_amount` | BIGINT UNSIGNED default 0 | the open "any amount" portion |
| `currency` | CHAR(3) default 'INR' | |
| `type` | ENUM('one_time','recurring') default 'one_time' | |
| `payment_mode` | ENUM('upi','card','netbanking','wallet','cash','cheque','bank_transfer','other') | |
| `status` | ENUM('pending','processing','succeeded','failed','refunded','cancelled','abandoned') default 'pending', INDEX | `abandoned` is set by the stale-pending sweeper (M05), not by anything a donor does |
| `is_offline` | BOOLEAN default 0 | manually recorded by admin |
| `donated_at` | TIMESTAMP NULL | when money actually moved |
| `financial_year` | CHAR(7) INDEX | `2026-27` — denormalised, makes 80G queries trivial |
| `eligible_for_80g` | BOOLEAN default 1 | false for cash > ₹2,000 |
| `message` | TEXT NULL | donor's note, shown on campaign page |
| `dedicated_to` | VARCHAR(150) NULL | "in memory of…" |
| `source` | VARCHAR(50) NULL | `website`, `admin`, `qr_poster`, campaign UTM |
| `utm_data` | JSON NULL | captured into a hidden field at checkout — `utm_source/medium/campaign/content`. Populate it from day one; attribution cannot be reconstructed retroactively |
| `ip_address` | VARCHAR(45) NULL | |
| `recorded_by_user_id` | BIGINT NULL FK→users | for offline entries |
| `notes` | TEXT NULL | |

Indexes: `(status, donated_at)`, `(campaign_id, status)`, `(financial_year, status)`,
`(donor_id, donated_at)`.

**Donations are never deleted.** Mistakes are corrected with `status = cancelled` plus a note.

### `payment_transactions`
The gateway record. A donation can have several attempts; only one succeeds.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `donation_id` | BIGINT FK→donations, INDEX | |
| `provider` | VARCHAR(30) default 'razorpay' | |
| `provider_order_id` | VARCHAR(120) NULL INDEX | Razorpay `order_id` |
| `provider_payment_id` | VARCHAR(120) NULL UNIQUE | Razorpay `payment_id` |
| `provider_signature` | VARCHAR(255) NULL | |
| `amount` | BIGINT UNSIGNED | |
| `fee` | BIGINT UNSIGNED default 0 | gateway fee, paise |
| `tax` | BIGINT UNSIGNED default 0 | GST on the fee |
| `net_amount` | BIGINT UNSIGNED default 0 | what actually lands in the bank |
| `status` | ENUM('created','authorized','captured','failed','refunded') INDEX | |
| `method` | VARCHAR(30) NULL | as reported by gateway |
| `bank` | VARCHAR(60) NULL | |
| `vpa` | VARCHAR(120) NULL | UPI id |
| `card_last4` | CHAR(4) NULL | never store full card data |
| `error_code` | VARCHAR(60) NULL | |
| `error_description` | TEXT NULL | |
| `raw_response` | JSON NULL | |
| `captured_at` | TIMESTAMP NULL | |
| `refunded_at` | TIMESTAMP NULL | |
| `refund_amount` | BIGINT UNSIGNED default 0 | |

**Tracking `fee` and `net_amount` matters:** the donor's 80G receipt shows the gross ₹1,000, but only
₹976 hits the bank. Without this column, bank reconciliation is guesswork.

---

## 6. Recurring / Auto-Pay (M06)

### `subscriptions`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `donor_id` | BIGINT FK→donors, INDEX | |
| `campaign_id` | BIGINT NULL FK→campaigns | |
| `provider` | VARCHAR(30) default 'razorpay' | |
| `provider_plan_id` | VARCHAR(120) NULL | |
| `provider_subscription_id` | VARCHAR(120) UNIQUE NULL | |
| `provider_token_id` | VARCHAR(120) NULL | mandate token |
| `amount` | BIGINT UNSIGNED | per cycle, paise |
| `interval` | ENUM('monthly','quarterly','yearly') default 'monthly' | |
| `total_cycles` | INT NULL | null = until cancelled |
| `completed_cycles` | INT default 0 | |
| `status` | ENUM('created','pending_authentication','active','paused','halted','completed','cancelled','expired') default 'created', INDEX | mirrors Razorpay's own state machine |
| `mandate_type` | ENUM('upi_autopay','emandate','card') NULL | |
| `started_at` | TIMESTAMP NULL | |
| `next_charge_at` | TIMESTAMP NULL INDEX | |
| `last_charged_at` | TIMESTAMP NULL | |
| `ended_at` | TIMESTAMP NULL | |
| `cancelled_at` | TIMESTAMP NULL | |
| `cancelled_by` | ENUM('donor','admin','gateway','bank') NULL | |
| `cancellation_reason` | VARCHAR(255) NULL | |
| `failed_charge_count` | SMALLINT default 0 | 3 consecutive → halt + email |
| `total_collected` | BIGINT UNSIGNED default 0 | |
| `raw_response` | JSON NULL | |

### `subscription_charges`
One row per attempted cycle. Successful charges also create a `donations` row.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `subscription_id` | BIGINT FK→subscriptions, INDEX | |
| `donation_id` | BIGINT NULL FK→donations | created on success |
| `cycle_number` | INT | |
| `amount` | BIGINT UNSIGNED | |
| `status` | ENUM('scheduled','processing','succeeded','failed','skipped') INDEX | |
| `provider_payment_id` | VARCHAR(120) NULL | |
| `scheduled_for` | TIMESTAMP | |
| `charged_at` | TIMESTAMP NULL | |
| `failure_reason` | VARCHAR(255) NULL | |
| `retry_count` | SMALLINT default 0 | |

UNIQUE (`subscription_id`, `cycle_number`).

---

## 7. Receipts & 80G (M07)

### `receipt_sequences`
**The concurrency-critical table.** One row per (series, financial year). Locked with
`SELECT ... FOR UPDATE` when allocating a number.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `series` | ENUM('donation','80g') | |
| `financial_year` | CHAR(7) | `2026-27` |
| `prefix` | VARCHAR(20) | `VGWGF/80G/2026-27/` |
| `last_number` | INT UNSIGNED default 0 | |
| `locked_at` | TIMESTAMP NULL | |

UNIQUE (`series`, `financial_year`).

### `receipts`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | QR verification target |
| `receipt_number` | VARCHAR(50) UNIQUE | the printed string, `VGWGF/80G/2026-27/00042` |
| `sequence_number` | INT UNSIGNED | the raw integer, `42` — what gap detection actually queries |
| `series` | ENUM('donation','80g') INDEX | |
| `revision` | SMALLINT UNSIGNED default 1 | incremented on reissue after cancellation |
| `donation_id` | BIGINT FK→donations, INDEX | |
| `donor_id` | BIGINT FK→donors, INDEX | |
| `financial_year` | CHAR(7) INDEX | |
| `amount` | BIGINT UNSIGNED | |
| `amount_in_words` | VARCHAR(255) | frozen at generation |
| `snapshot_data` | JSON | donor name, address, PAN, org 80G number — all frozen |
| `file_path` | VARCHAR(255) NULL | |
| `issued_on` | DATE | |
| `emailed_at` | TIMESTAMP NULL | |
| `email_status` | ENUM('pending','sent','failed','bounced') default 'pending' | |
| `download_count` | INT UNSIGNED default 0 | |
| `is_cancelled` | BOOLEAN default 0 | cancelled receipts keep their number — never reused |
| `cancelled_reason` | VARCHAR(255) NULL | |

UNIQUE (`donation_id`, `series`, `revision`). Index `(series, financial_year, sequence_number)` for
gap detection.

**Not** `UNIQUE (donation_id, series)` — that would make reissue after cancellation impossible, since
the cancelled row still holds the pair, and every partial refund would die on a constraint violation.

The real rule — *at most one **non-cancelled** receipt per donation per series* — is an application
invariant enforced by a guarded write in `Generate80GReceipt` (lock the donation, assert no live
receipt, insert at `MAX(revision) + 1`). MySQL 8 has no partial indexes, so it cannot live in the
schema. See `M07 §Cancellation and reissue`.

---

## 8. Campaigns & Crowdfunding (M08)

### `campaign_categories`
Seeded with the reference site's causes: Animals, Children, Elderly, Education, Faith, Women,
Disaster Relief, Specially Abled.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `name` | VARCHAR(100) | |
| `slug` | VARCHAR(100) UNIQUE | |
| `icon_path` | VARCHAR(255) NULL | |
| `description` | TEXT NULL | short blurb for the homepage tile |
| `intro_body` | TEXT NULL | 150+ words of unique copy on `/causes/{slug}` — what makes the page rank |
| `meta_title` | VARCHAR(190) NULL | |
| `meta_description` | VARCHAR(255) NULL | |
| `sort_order` | SMALLINT default 0 | |
| `is_active` | BOOLEAN default 1 | |

The eight category pages at `/causes/{slug}` are the most durable SEO assets in the project — unlike
individual campaigns they never close, and they rank for head terms like "donate for stray animals
india". A category page that is only a campaign grid has nothing to rank on, hence `intro_body`.

### `campaigns`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `slug` | VARCHAR(190) UNIQUE INDEX | SEO URL |
| `category_id` | BIGINT FK→campaign_categories, INDEX | |
| `title` | VARCHAR(190) | |
| `subtitle` | VARCHAR(255) NULL | |
| `beneficiary_name` | VARCHAR(150) NULL | the "by …" line on cards |
| `story` | LONGTEXT | rich text |
| `cover_image_path` | VARCHAR(255) NULL | |
| `video_url` | VARCHAR(255) NULL | |
| `goal_amount` | BIGINT UNSIGNED | paise |
| `raised_amount` | BIGINT UNSIGNED default 0 | **denormalised**, reconciled nightly |
| `donor_count` | INT UNSIGNED default 0 | **denormalised** |
| `offline_raised_amount` | BIGINT UNSIGNED default 0 | cheques etc., added to display total |
| `allows_recurring` | BOOLEAN default 1 | shows on the `/monthly-giving` page |
| `is_tax_benefit` | BOOLEAN default 1 | drives the "Tax Benefit" badge |
| `is_featured` | BOOLEAN default 0 INDEX | homepage carousel |
| `is_urgent` | BOOLEAN default 0 | |
| `status` | ENUM('draft','pending_review','active','paused','completed','closed') default 'draft', INDEX | |
| `starts_at` | TIMESTAMP NULL | |
| `ends_at` | TIMESTAMP NULL INDEX | |
| `sort_order` | SMALLINT default 0 | |
| `meta_title` | VARCHAR(190) NULL | |
| `meta_description` | VARCHAR(255) NULL | |
| `created_by_user_id` | BIGINT FK→users | |
| `deleted_at` | TIMESTAMP NULL | |

Indexes: `(status, is_featured, sort_order)`, `(category_id, status)`.

### `campaign_products`

The **needs catalogue** — the "Products" tab on the campaign page. Each row is a concrete item a donor
can fund a quantity of: `Medicine kit · ₹900 · 11 of 1500 funded`.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `campaign_id` | BIGINT FK→campaigns, INDEX | `onDelete('restrict')` — see note |
| `name` | VARCHAR(120) | "Medicine kit", "Rice 10KG" |
| `description` | VARCHAR(255) NULL | |
| `image_path` | VARCHAR(255) NULL | |
| `unit_price` | BIGINT UNSIGNED | paise |
| `units_needed` | INT UNSIGNED | |
| `units_funded` | INT UNSIGNED default 0 | **denormalised**, reconciled nightly |
| `sort_order` | SMALLINT default 0 | |
| `is_active` | BOOLEAN default 1 | |

Index: `(campaign_id, is_active, sort_order)`.

**`restrict`, not `cascade`.** A cascade from `campaigns` would try to delete products that
`donation_items` restricts, and the whole delete would fail at the database level — a confusing error
in place of a clear one. It also breaks the rule in `05-CONVENTIONS.md` that anything in the financial
chain uses `restrict`. Campaigns are soft-deleted anyway (`M08`), so a hard delete should be
impossible; `restrict` makes that explicit rather than relying on it.

`units_funded` drifts for exactly the same reasons `campaigns.raised_amount` does, and is corrected by
the same nightly reconciler. Never sum `donation_items` on render.

### `donation_items`

Line items. A donation of 2 medicine kits and 1 rice bag is **one** `donations` row and **two**
`donation_items` rows.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `donation_id` | BIGINT FK→donations, INDEX | `onDelete('restrict')` — financial record |
| `campaign_product_id` | BIGINT FK→campaign_products, INDEX | `onDelete('restrict')` |
| `quantity` | SMALLINT UNSIGNED | |
| `unit_price` | BIGINT UNSIGNED | **price at the time of donation, snapshotted** |
| `line_total` | BIGINT UNSIGNED | `quantity × unit_price` |

`unit_price` is copied, not joined. Prices change; a receipt reprinted next year must show what the
donor actually paid. Same reasoning as `issued_documents.snapshot_data`.

**`donations.amount` is the authoritative total.** It equals `items_amount + free_amount`, and both
components are recomputed server-side from `campaign_products.unit_price` — the client submits
quantities, never money.

### `campaign_stats`

The per-campaign impact counters ("5,000+ Dogs Rescued", "130+ Current Inmates", "9 Years Of Service").
Distinct from the site-wide `impact_stats` in §10 — these belong to one campaign and one beneficiary.

`id`, `campaign_id` FK→campaigns INDEX, `label`, `value` VARCHAR(20), `suffix` VARCHAR(5) NULL,
`sort_order`.

### `campaign_faqs`

The FAQ accordion at the foot of the campaign page.

`id`, `campaign_id` BIGINT FK→campaigns **NULL** INDEX, `question` VARCHAR(255), `answer` TEXT,
`sort_order`, `is_published`.

**A null `campaign_id` means a global FAQ shown on every campaign.** Most of the reference site's
questions are organisation-level ("Do I get a tax benefit?"), so the default set is seeded once and
campaigns add their own on top rather than each restating the same six answers.

### `campaign_updates`
The "live impact updates" the reference site promises donors.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | notify-donor emails link to a specific update |
| `campaign_id` | BIGINT FK→campaigns, INDEX | |
| `title` | VARCHAR(190) | |
| `body` | TEXT | |
| `image_path` | VARCHAR(255) NULL | |
| `published_at` | TIMESTAMP NULL | |
| `notify_donors` | BOOLEAN default 0 | emails everyone who gave to this campaign |
| `created_by_user_id` | BIGINT FK→users | |

### `fundraiser_requests`
The "Start a Fundraise" form. Approved requests become campaigns.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `name` | VARCHAR(150) | |
| `email` | VARCHAR(190) | |
| `phone` | VARCHAR(20) | |
| `organisation_name` | VARCHAR(190) NULL | |
| `cause_category_id` | BIGINT NULL FK→campaign_categories | |
| `title` | VARCHAR(190) | |
| `description` | TEXT | |
| `goal_amount` | BIGINT UNSIGNED | |
| `documents` | JSON NULL | uploaded proof paths |
| `status` | ENUM('new','under_review','approved','rejected') default 'new', INDEX | |
| `reviewed_by_user_id` | BIGINT NULL FK→users | |
| `reviewed_at` | TIMESTAMP NULL | |
| `review_notes` | TEXT NULL | |
| `campaign_id` | BIGINT NULL FK→campaigns | set on approval |

---

## 9. Notices & Communication (M09)

### `notices`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `title` | VARCHAR(190) | |
| `body` | LONGTEXT | |
| `attachment_path` | VARCHAR(255) NULL | |
| `audience` | ENUM('all_members','department','designation','specific','all_donors') INDEX | |
| `audience_filter` | JSON NULL | `{department_ids:[…]}` / `{user_ids:[…]}` |
| `priority` | ENUM('normal','important','urgent') default 'normal' | |
| `send_email` | BOOLEAN default 1 | |
| `published_at` | TIMESTAMP NULL INDEX | |
| `expires_at` | TIMESTAMP NULL | |
| `status` | ENUM('draft','scheduled','sending','sent','failed') default 'draft', INDEX | |
| `recipient_count` | INT UNSIGNED default 0 | |
| `read_count` | INT UNSIGNED default 0 | |
| `created_by_user_id` | BIGINT FK→users | |

### `notice_recipients`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `notice_id` | BIGINT FK→notices, INDEX | |
| `user_id` | BIGINT FK→users, INDEX | |
| `read_at` | TIMESTAMP NULL | |
| `email_status` | ENUM('pending','sent','failed','bounced') default 'pending' | |
| `emailed_at` | TIMESTAMP NULL | |

UNIQUE (`notice_id`, `user_id`).

### `email_templates`
Admin-editable copy for every automated email — the "Email Action" feature.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `key` | VARCHAR(80) UNIQUE | `donation.thank_you`, `receipt.80g`, `member.id_card_issued`… |
| `name` | VARCHAR(120) | |
| `subject` | VARCHAR(190) | supports `{{ variables }}` |
| `body_html` | LONGTEXT | |
| `available_variables` | JSON | documented for the admin UI |
| `is_active` | BOOLEAN default 1 | |
| `send_copy_to_admin` | BOOLEAN default 0 | |

### `subscribers`
Newsletter list.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `email` | VARCHAR(190) UNIQUE | |
| `name` | VARCHAR(150) NULL | |
| `token` | CHAR(40) UNIQUE | one-click unsubscribe |
| `confirmed_at` | TIMESTAMP NULL | double opt-in |
| `unsubscribed_at` | TIMESTAMP NULL | |
| `source` | VARCHAR(50) NULL | |

---

## 10. Public Site & CMS (M10)

### `pages`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `slug` | VARCHAR(190) UNIQUE | `about`, `privacy-policy`, `terms-conditions` |
| `title` | VARCHAR(190) | |
| `body` | LONGTEXT | |
| `template` | VARCHAR(50) default 'default' | |
| `meta_title` / `meta_description` | VARCHAR | |
| `is_published` | BOOLEAN default 1 | |
| `sort_order` | SMALLINT default 0 | |

### `posts` (blog)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `slug` | VARCHAR(190) UNIQUE | |
| `title` | VARCHAR(190) | |
| `excerpt` | VARCHAR(500) NULL | |
| `body` | LONGTEXT | |
| `cover_image_path` | VARCHAR(255) NULL | |
| `category` | VARCHAR(80) NULL | cast to the `PostCategory` enum — free text breaks the blog filter |
| `tags` | JSON NULL | |
| `author_user_id` | BIGINT FK→users | |
| `published_at` | TIMESTAMP NULL INDEX | |
| `view_count` | INT UNSIGNED default 0 | |
| `meta_title` / `meta_description` | VARCHAR | |
| `is_published` | BOOLEAN default 0 INDEX | |

### `testimonials`
`id`, `name`, `location`, `avatar_path`, `quote` TEXT, `rating` TINYINT, `is_published`, `sort_order`.

### `press_mentions`
`id`, `outlet_name`, `logo_path`, `url`, `published_on`, `is_published`, `sort_order`.

### `impact_stats`
The homepage counters. `id`, `label`, `value` VARCHAR(20), `suffix` (`+`, `K+`), `icon`, `sort_order`.

### `banners`

The homepage hero. Rotating, scheduled, optionally linked to a campaign. See
[`06-UI-UX-FOUNDATION.md`](06-UI-UX-FOUNDATION.md) §7.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `title` | VARCHAR(190) | |
| `subtitle` | VARCHAR(255) NULL | |
| `image_path` | VARCHAR(255) | desktop, ~1920×720 |
| `mobile_image_path` | VARCHAR(255) NULL | ~750×900; falls back to `image_path` |
| `cta_label` | VARCHAR(60) NULL | |
| `cta_url` | VARCHAR(255) NULL | |
| `campaign_id` | BIGINT FK→campaigns NULL, `onDelete('set null')` | shortcut instead of `cta_url` |
| `sort_order` | SMALLINT default 0 | |
| `is_published` | BOOLEAN default 1 INDEX | |
| `starts_at` / `ends_at` | TIMESTAMP NULL | scheduled banners |

A separate mobile image is required, not optional — the hero is the homepage's LCP element and a
1920×720 desktop image letterboxes to an unreadable strip at 360 px.

### `redirects`

Makes the immutable-slug rule survivable. Without it, editing a published campaign's slug is a
permanent 404 on every link already shared to WhatsApp or printed on a poster. See
[`07-SEO.md`](07-SEO.md) §7.

`id`, `from_path` VARCHAR(255) UNIQUE, `to_path` VARCHAR(255), `status_code` SMALLINT default 301,
`hits` INT UNSIGNED default 0, `last_hit_at` TIMESTAMP NULL, `created_at`.

Resolved in middleware **after** the router fails and before the 404 renders, so it costs one query
only on genuine misses. Rows are created automatically on a published-slug change. On insert, if
`to_path` already exists as a `from_path`, collapse to the final destination — chains and loops
otherwise accumulate silently.

### `contact_messages`

Backs `/contact`, which appears in M10's sitemap. Owned by M10.

`id`, `name`, `email`, `phone` NULL, `subject` VARCHAR(190) NULL, `message` TEXT, `ip_address`,
`user_agent`, `is_read` BOOLEAN default 0 INDEX, `read_at` TIMESTAMP NULL, `created_at`.

Honeypot field plus a 3/hour/IP rate limit — public forms attract spam. Read-only in Filament;
replies happen in the admin's own mail client.

---

## 11. Cross-cutting

### `activity_log`
Provided by `spatie/laravel-activitylog`. Audits: donation status changes, receipt generation,
document issue/revoke, member status changes, settings edits, role changes.

### `jobs`, `failed_jobs`, `job_batches`
Standard Laravel queue tables. **Monitor `failed_jobs`** — a silently failing PDF job means a donor
never gets their 80G receipt.

### `sessions`, `cache`, `cache_locks`
Database driver on shared hosting (no Redis on Hostinger shared plans).

---

## 12. Migration order

Foreign keys dictate this sequence:

```
01  users, password_reset_tokens, sessions
02  permission tables (spatie)
03  settings
04  webhook_events
05  departments
06  designations
07  document_templates
08  members                       (fk: users, departments, designations)
09  issued_documents              (fk: members, document_templates, users)
10  campaign_categories
11  campaigns                     (fk: campaign_categories, users)
12  campaign_updates              (fk: campaigns, users)
12b campaign_products, campaign_stats, campaign_faqs   (fk: campaigns)
13  fundraiser_requests           (fk: campaign_categories, campaigns, users)
14  donors                        (fk: users)
15  subscriptions                 (fk: donors, campaigns)
16  donations                     (fk: donors, campaigns, subscriptions, users)
16b donation_items                (fk: donations, campaign_products)
17  subscription_charges          (fk: subscriptions, donations)
18  payment_transactions          (fk: donations)
19  receipt_sequences
20  receipts                      (fk: donations, donors)
21  notices                       (fk: users)
22  notice_recipients             (fk: notices, users)
23  email_templates
24  subscribers
25  pages, posts, testimonials, press_mentions, impact_stats, contact_messages, redirects
26  banners                       (fk: campaigns)
27  activity_log, jobs, failed_jobs, cache
```

`designations.letter_template_id` → `document_templates` is added in step 07's follow-up migration to
avoid a circular dependency; alternatively make it nullable without a constraint.

---

## 13. Seed data

`RolePermissionSeeder` — 5 roles, ~60 granular permissions.
`AdminUserSeeder` — one `super-admin`, credentials from `.env`, **must be changed on first login**.
`SettingsSeeder` — all keys from §2 with placeholder values.
`CampaignCategorySeeder` — the 8 causes.
`EmailTemplateSeeder` — ~12 templates with sensible default copy.
`DocumentTemplateSeeder` — one default template per document type.
`HomepageContentSeeder` — one placeholder banner, the four how-to-donate steps, and the "who we
serve" copy, so a fresh install renders a complete homepage rather than five empty sections.
`DemoDataSeeder` (dev only) — 50 members, 20 campaigns with products, **10,000 donations**, 30
subscriptions. The donation count is deliberately large: `M12` and Sprint 14 both gate on "dashboard
under 2 s" and "export 10,000 rows without exhausting memory", and those criteria cannot be exercised
against a 500-row fixture. Seed it in chunks and expect it to take a minute. Essential
for testing pagination, report performance, and the dashboard before real data exists.

---

Next: [`03-ROADMAP.md`](03-ROADMAP.md)
