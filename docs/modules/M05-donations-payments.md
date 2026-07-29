# M05 — Donations & Payments

**Phase 3, Sprints 5–6** · Depends on: M01, M02

> **Read this module carefully.** Along with M06 and M07 it contains every genuinely hard problem in
> the project. Money moves here. Bugs here cost real donations and real trust.

## Purpose

Accept money — from guests, from logged-in donors, one-time, online or offline — record it correctly,
and never lose or duplicate a rupee.

## Data

`donors`, `donations`, `payment_transactions`, `webhook_events` — see `02-DATABASE-SCHEMA.md` §5.

**Three tables, not one, because they answer different questions:**
- `donations` — *what the donor intended to give.* Business record. Appears on receipts and reports.
- `payment_transactions` — *what the gateway did.* One row per attempt. Carries fees, error codes,
  method details.
- `donors` — *who gave.* Persists across donations, accumulates history.

A donation with three failed card attempts and one successful UPI attempt is **one** `donations` row
and **four** `payment_transactions` rows.

## The gateway abstraction

Donation logic never touches the Razorpay SDK.

```php
interface PaymentGateway
{
    public function createOrder(PaymentIntent $intent): OrderResult;
    public function verifyPaymentSignature(array $payload): bool;
    public function fetchPayment(string $paymentId): PaymentResult;
    public function refund(string $paymentId, Money $amount): RefundResult;

    public function createMandate(MandateRequest $request): MandateResult;   // M06
    public function cancelMandate(string $subscriptionId): bool;             // M06

    public function verifyWebhookSignature(string $body, string $signature): bool;
    public function parseWebhook(array $payload): WebhookEvent;
}
```

Three implementations: `RazorpayGateway` (production), `FakeGateway` (tests — deterministic, no
network), and later `CashfreeGateway` if needed. Bound in `PaymentServiceProvider`.

This costs about a day and means the entire test suite runs offline, plus swapping gateways is one
class instead of a refactor.

## Donation flow (online, guest)

```
1. Donor picks amount + campaign on the public form
        │
2. POST /donate  →  StoreDonationRequest validates
        │
3. InitiateDonation action:
     ├─ resolve or create Donor (dedupe by email)
     ├─ create Donation (status: pending)
     ├─ SERVER-SIDE amount validation ← never trust the client
     ├─ gateway->createOrder()
     └─ create PaymentTransaction (status: created)
        │
4. Return Razorpay order_id → Checkout.js opens
        │
5. Donor pays
        │
    ┌───┴────────────────────────────┐
    │                                │
6a. Client callback              6b. Webhook payment.captured
    POST /donate/callback            POST /webhooks/razorpay
    verify signature                 verify HMAC signature
    │                                │
    └────────► RecordSuccessfulDonation ◄────────┘
                   (idempotent — either path may arrive first,
                    both may arrive, order is not guaranteed)
        │
7. Inside DB::transaction:
     ├─ lock donation row FOR UPDATE
     ├─ if already succeeded → return (no-op)
     ├─ donation.status = succeeded, set donated_at, financial_year
     ├─ transaction.status = captured, record fee/tax/net_amount
     ├─ update donor.total_donated, donation_count
     └─ fire DonationSucceeded
        │
8. Listeners (queued):
     ├─ GenerateReceiptForDonation  → M07
     ├─ SendDonationThankYou        → M09
     └─ UpdateCampaignTotals        → M08
        │
9. Outcome page — /donate/success, /pending, or /failed
```

> **Step 9 is not a simple redirect.** Razorpay Checkout.js is a modal, and the donor can dismiss it,
> or pay successfully while the callback POST is lost. The full client-side state machine — including
> the dismissed path, the pending-with-polling path, and the stale-pending cleanup job — is specified
> in [`06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) §6. Build against that, not against this
> diagram alone.
>
> This diagram covers **one-time** payments only. A recurring mandate (M06) leaves the site entirely
> and returns by redirect; it has its own five states in the same section.

### Why both a callback and a webhook

The client callback is fast but unreliable — the donor may close the tab, lose network, or have the
browser crash between payment and redirect. The webhook is reliable but delayed by seconds.

You need both, and therefore you need **idempotency**. This is not optional and it is not an
edge case; it happens on a meaningful fraction of real donations.

## Idempotency

Two layers.

**Layer 1 — webhook deduplication.** `webhook_events` has `UNIQUE (provider, event_id)`. Every
webhook writes here first. `wasRecentlyCreated === false` means we've seen it; return 200 and stop.

Razorpay retries webhooks on non-2xx responses and occasionally delivers duplicates even on success.
This is documented behaviour, not a bug in their system.

**Layer 2 — donation row locking.**

```php
DB::transaction(function () use ($donationId, $result) {
    $donation = Donation::whereKey($donationId)->lockForUpdate()->firstOrFail();

    if ($donation->status === DonationStatus::Succeeded) {
        return $donation;   // callback already handled it
    }

    // ... mutate, fire event
});
```

Test explicitly: fire the same webhook five times, assert exactly one succeeded donation, one
receipt, one thank-you email.

## Server-side amount validation

```php
// If the donation is campaign-scoped, the amount must be within bounds.
// The client-submitted amount is a suggestion, not a fact.
$amount = Money::fromPaise((int) $request->integer('amount'));

if ($amount->toPaise() < Settings::get('donation.min_amount')) {
    throw ValidationException::withMessages([...]);
}

if ($campaign && $campaign->status !== CampaignStatus::Active) {
    throw new CampaignNotAcceptingDonations();
}
```

The order amount sent to Razorpay comes from this validated server-side value. A tampered hidden
field produces a rejected request, not a ₹1 donation recorded as ₹10,000.

**The same rule extends to catalogue line items** (`M08 §Products`). The client submits
`[{product_id, quantity}, …]`; `InitiateDonation` re-reads every `unit_price` from the database,
computes `line_total` and `items_amount` itself, and sets
`donations.amount = items_amount + free_amount`. Quantities come from the client. Money never does.
`donation_items.unit_price` is snapshotted so a reprinted receipt shows what was actually paid.

## Fees and net amount

Razorpay's `payment.captured` webhook includes `fee` and `tax`. Store both, compute
`net_amount = amount - fee - tax`.

The donor's receipt shows the gross ₹1,000. The bank receives ₹976.40. Without recording this, the
accountant cannot reconcile the bank statement against the donation ledger, and that reconciliation
is the first thing an auditor asks for.

## Offline donations

Cash, cheque, and bank transfers recorded by an admin. Same `donations` table, `is_offline = true`,
no `payment_transactions` row, `recorded_by_user_id` set.

**Cash over ₹2,000 is not eligible for 80G deduction** under Indian tax law. `CreateOfflineDonation`
sets `eligible_for_80g = false` automatically when `payment_mode = cash` and the amount exceeds
`donation.cash_80g_limit`, and the admin UI explains why rather than silently doing it.

## Donor deduplication

On each donation, resolve the donor by email (case-insensitive, trimmed). If found, reuse and update
their details where the new submission is more complete. If not, create.

Do **not** dedupe by phone alone — shared family phone numbers are common and merging two people's
donation histories is worse than having two records.

## UI

**Public donation page** — amount presets from settings plus a custom field, campaign selector or
pre-selected, donor name/email/phone, PAN and address behind an "I want an 80G tax-exemption receipt"
checkbox (**default on**), anonymous checkbox, optional message, "make this monthly" toggle (→ M06),
trust signals (80G badge, secure-payment mark) placed next to the submit button.

Keep the default path fast and short. Every extra field costs conversions — which is why the 80G
fields are revealed rather than always shown.

> **The full field layout, disclosure rules, and the 80G-versus-anonymous conflict handling are
> specified in [`06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) §5, which supersedes this
> paragraph.** That section resolves the contradiction between "keep it short" here, M07's hard
> requirement for PAN + address, and M07's rule that an anonymous donation can never receive an 80G
> receipt. Do not design this form from this module alone.

**Success page** — thank you, amount, receipt-on-its-way note, share buttons, campaign link, and a
"make this monthly" nudge.

**Pending page** — for the case where payment succeeded but the callback did not reach us. Polls
donation status for 20 s, then reassures: "Payment is being confirmed. We'll email your receipt
within a few minutes." Never show a paid donor an error.

**Failure page** — plain explanation, retry button pre-filled with the same amount, support contact.
Do not blame the donor.

**Admin** — `DonationResource` (filters: status, mode, campaign, FY, date range, offline/online;
actions: view, refund, regenerate receipt, mark eligible/ineligible for 80G; export).
`DonorResource` (donation history, lifetime total, PAN status, receipts).

## Build checklist

- [ ] `donors`, `donations`, `payment_transactions`, `webhook_events` migrations + models + factories
- [ ] `PaymentGateway` interface + all DTOs
- [ ] `RazorpayGateway` — create order, verify signature, fetch payment, refund
- [ ] `FakeGateway` for tests
- [ ] `PaymentServiceProvider` binding
- [ ] `RazorpayWebhookController` + HMAC signature middleware
- [ ] CSRF exemption for the webhook route
- [ ] Actions: `InitiateDonation`, `RecordSuccessfulDonation`, `RecordFailedDonation`, `RefundDonation`
- [ ] `CreateOfflineDonation` with the cash-₹2,000 rule
- [ ] Idempotency via `webhook_events` unique constraint + row locking
- [ ] Server-side amount validation
- [ ] Fee, tax, and `net_amount` recorded
- [ ] Donation form as one Livewire component, rendered both on `/donate` and as the campaign-page
      checkout modal, built per `06-UI-UX-FOUNDATION.md` §5
- [ ] `donation_items` written inside the same transaction as the donation
- [ ] Server-side line-total recomputation; price-changed-since-load confirmation step
- [ ] UTM capture into `donations.utm_data`; terms checkbox required and never pre-ticked
- [ ] Exit-intent guard on the modal — once per session, dismissible, never blocking
- [ ] Checkout.js `ondismiss` handler — stay on page, retain fields, re-enable the button
- [ ] Success / failure / pending pages; pending polls donation status for 20 s
- [ ] `AbandonStalePendingDonations` scheduled job (pending > 30 min → `abandoned`)
- [ ] Browser tests per `06-UI-UX-FOUNDATION.md` §11 (happy path, dismissal, 80G/anonymous conflict)
- [ ] Instrumentation on the 80G checkbox (checked/unchecked, abandonment) — the default-on decision is
      confirmed but should be re-evaluated against real data after ~500 donations
- [ ] `DonationResource`, `DonorResource`
- [ ] Donor portal: history + receipt downloads
- [ ] Events: `DonationSucceeded`, `DonationFailed`
- [ ] Rate limiting: 10 donation attempts/min/IP
- [ ] **100% test coverage on this module**

## Edge cases

- **Donor dismisses the Checkout modal without paying** → no navigation, fields retained, neutral
  message. The donation stays `pending` and is swept to `abandoned` after 30 minutes. They hesitated;
  do not treat it as a failure.
- **Checkout.js fails to load** (ad blocker, flaky network) → detect the script-load failure and show
  the support phone number plus a "we've saved your details" note.
- **Payment succeeds but the callback POST is lost** → `/donate/pending` polls, then reassures. The
  webhook completes the donation regardless.
- **Duplicate webhook** → handled by `webhook_events` unique constraint.
- **Callback and webhook race** → handled by `lockForUpdate` + status check.
- **Donor closes the tab after paying** → webhook completes the donation. They still get the receipt
  by email. This is the single most common real-world scenario and it must work.
- **Payment succeeds, our server is down** → Razorpay retries the webhook for 24 hours. Additionally
  run a daily reconciliation job that fetches gateway payments for the last 48h and finds any not
  recorded locally.
- **Partial refund** → `refund_amount < amount`; donation stays `succeeded` but the 80G receipt must
  be cancelled and reissued for the net amount.
- **Full refund** → status `refunded`, 80G receipt cancelled (number retained, never reused),
  campaign totals decremented.
- **Amount tampering** → server-side validation rejects it.
- **Donation to a campaign that closes mid-payment** → accept it (the money has moved) but flag it
  for admin review. Refusing money that has already left the donor's account is worse.
- **Same email, different name** → reuse the donor record, keep the most recent name, log the change.
- **Currency other than INR** → out of scope for v1. Reject with a clear message.
- **Gateway returns `authorized` but not `captured`** → auto-capture is enabled in Razorpay settings;
  if a manual-capture payment appears, alert the admin. An uncaptured authorisation expires and the
  donor's money is silently returned.
- **Webhook arrives for an unknown `order_id`** → log it, return 200 (so Razorpay stops retrying),
  alert the admin. Never 500 on an unrecognised webhook.
