# M06 — Recurring Donations / Auto-Pay

**Phase 4, Sprint 7** · Depends on: M05

## Purpose

The "Auto Pay Donation — हर महीने Automatic Donation प्राप्त करें" feature from the pamphlet, and the
"Monthly Campaign" section of the reference site: a donor authorises a mandate once, and the NGO
collects automatically every month.

## Why this is harder than one-time payments

A one-time payment resolves in seconds. A mandate is a long-lived relationship that:

- takes a two-step authorisation (create, then donor authenticates with their bank or UPI app)
- can be rejected by the bank days after the donor thought they'd set it up
- charges monthly for years, and any individual charge can fail
- can be cancelled by the donor, by us, **or by the bank**, without warning
- has a state machine we don't control and must mirror faithfully

Treating this like "a payment, but repeated" is the standard way to get it wrong.

## Razorpay's state machine

We mirror it exactly in `subscriptions.status`:

```
created
   │  donor completes authentication
   ▼
authenticated ──► active ──┬──► completed        (all cycles done)
   │                       │
   │                       ├──► paused           (by donor or admin)
   │                       │      └──► active    (resumed)
   │                       │
   │                       ├──► halted           (repeated charge failures)
   │                       │      └──► active    (donor fixes payment method)
   │                       │
   │                       └──► cancelled        (donor, admin, or bank)
   │
   └──► expired            (authentication never completed)
```

Do **not** invent a simplified status set. When Razorpay says `halted` and our DB says `active`, the
admin sees phantom revenue and the donor gets emails about charges that aren't happening.

## Mandate types

| Type | Instrument | Notes |
|---|---|---|
| `upi_autopay` | UPI | Most popular in India. Donor approves in their UPI app. Limit ₹15,000/txn without additional auth. |
| `emandate` | Bank account (NACH) | Higher limits. Slower setup — bank approval can take 2–3 days. |
| `card` | Debit/credit card | Requires RBI-mandated e-mandate registration. Additional friction. |

Start with UPI Autopay — it's what most Indian retail donors will use and it has the smoothest UX.

> **Plan ahead:** Razorpay requires separate account approval for recurring payments. Apply in
> **week 1** of the project, not week 7. Approval commonly takes 2–3 weeks and it is the single most
> likely cause of a launch slip.

## Data

`subscriptions`, `subscription_charges` — see `02-DATABASE-SCHEMA.md` §6.

Each successful charge creates a **linked `donations` row** (`type = recurring`,
`subscription_id` set). This matters: it means all reporting, 80G receipts, and campaign totals work
identically for one-time and recurring gifts, with no special-casing anywhere downstream.

## Setup flow

```
1. Donor toggles "Make this monthly" on the donation form
        │
2. CreateSubscriptionMandate action:
     ├─ resolve/create Donor
     ├─ gateway->createMandate()  (creates Razorpay plan + subscription)
     └─ store subscription (status: created)
        │
3. Redirect donor to Razorpay's authentication page
        │
4. Donor authorises in their UPI app / bank
        │
5. Webhook: subscription.activated
     └─ ActivateSubscription: status = active, set started_at, next_charge_at
        │
6. Send "Your monthly donation is set up" email
        │
7. Monthly: webhook subscription.charged
     └─ RecordSubscriptionCharge:
          ├─ create subscription_charges row
          ├─ create linked donations row (succeeded)
          ├─ fire DonationSucceeded → receipt + email + campaign totals
          └─ increment completed_cycles, update next_charge_at
```

Steps 5 and 7 are webhook-driven and must be idempotent, exactly as in M05.

**Step 3 is a full navigation, not a modal.** The one-time flow's Checkout.js state machine does not
apply here — the donor leaves the site and returns by redirect. The five UI states that flow needs
(interstitial before the jump, authorised, declined, never-returned, authorised-but-webhook-late) are
specified in [`06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) §6.

## Failure handling

Charges fail for ordinary reasons: insufficient balance, expired card, closed account, revoked
mandate. This is normal and needs a considered policy rather than an error log.

```
Charge fails
   │
   ├─ increment failed_charge_count
   ├─ record subscription_charges row (status: failed, with reason)
   ├─ email the donor: "We couldn't process your monthly donation"
   │
   ├─ count < 3  → Razorpay retries per its own schedule; we wait
   └─ count >= 3 → status = halted
                    ├─ email donor: "Your monthly donation has been paused"
                    └─ notify admin
```

Reset `failed_charge_count` to zero on any successful charge.

Tone matters in these emails. A failed charge is usually a bank issue, not negligence. Write them
warmly — "we couldn't process this month's donation, here's how to fix it" — not as a dunning notice.
These are donors, not delinquent customers.

## Daily reconciliation

`SyncSubscriptionStatus` runs at 02:00 and fetches the current state of every non-terminal
subscription from Razorpay, correcting local drift.

Necessary because webhooks get missed — server downtime, deploys, network failures. Without this,
a subscription cancelled at the bank six weeks ago still shows as active revenue on the dashboard.

## Donor self-service

Non-negotiable, both for trust and because RBI e-mandate rules require an accessible cancellation
path.

In `/portal`, a donor can:

- see all mandates with amount, frequency, next charge date, total given so far
- see full charge history including failures
- **pause** (resumable)
- **cancel** (permanent — cancels at the gateway too)
- download receipts for each charge

Cancellation must be genuinely easy. Making it hard is both unkind and, for e-mandates, a compliance
problem.

## UI

**Public** — a "Make this a monthly donation" toggle on the donation form, showing "₹1,000 every
month, cancel anytime." A "Monthly Giving" page (`/monthly-giving`) listing campaigns with `allows_recurring = true`.

**Admin** — `SubscriptionResource`: donor, amount, status badge, cycles completed, total collected,
next charge, started date. Filters by status and campaign. Actions: view charge history, cancel,
resend the setup email. Dashboard widgets: active count, monthly recurring revenue (MRR), halted
count needing attention, churn this month.

MRR is the number the NGO's leadership will care about most. Make it prominent.

## Build checklist

- [ ] `subscriptions`, `subscription_charges` migrations + models + factories
- [ ] Razorpay Subscriptions / UPI Autopay integration in `RazorpayGateway`
- [ ] `FakeGateway` mandate support for tests
- [ ] Actions: `CreateSubscriptionMandate`, `ActivateSubscription`, `RecordSubscriptionCharge`,
      `PauseSubscription`, `CancelSubscription`
- [ ] Webhook handlers for all six subscription events
- [ ] Full status state machine with guarded transitions
- [ ] Failure policy: 3 strikes → halt + emails
- [ ] `SyncSubscriptionStatus` daily job
- [ ] Linked `donations` row per successful charge
- [ ] 80G receipt per charge
- [ ] Mandate redirect states per `06-UI-UX-FOUNDATION.md` §6 — interstitial, authorised, declined,
      abandoned (48 h expiry + 24 h reminder), authorised-but-webhook-late
- [ ] Per-cycle amount validated against the mandate ceiling **before** the donor leaves the site
- [ ] Donor portal: view, pause, cancel, charge history
- [ ] `SubscriptionResource` + MRR/churn widgets
- [ ] Mailables: activated, charged, charge-failed, halted, cancelled
- [ ] Monthly campaigns public page
- [ ] **100% test coverage on this module**

## Edge cases

- **Donor abandons authentication** → subscription sits in `created`. Expire after 48h, send one
  gentle reminder at 24h.
- **Bank cancels the mandate without a webhook** → caught by the daily sync.
- **Donor's card expires mid-subscription** → charges fail, 3 strikes, halted, donor emailed with an
  update-payment-method link.
- **Amount change** → Razorpay generally requires cancel + recreate. Present it to the donor as
  "update your monthly amount" and handle the two-step transparently.
- **Campaign ends while subscriptions to it are active** → keep charging but reassign new donations
  to the general fund, and notify the donor that the campaign has completed. Do not silently keep
  crediting a finished campaign.
- **Duplicate `subscription.charged` webhook** → `UNIQUE (subscription_id, cycle_number)` prevents
  double-recording.
- **Charge succeeds at the gateway but our handler crashes** → the daily sync detects the missing
  local charge and backfills it, including the receipt.
- **Donor cancels then re-subscribes** → a new subscription row. Never reuse the old one; the charge
  history must stay attached to the mandate it belongs to.
- **UPI Autopay ₹15,000 limit** → validate the per-cycle amount against the mandate type's ceiling and
  suggest e-mandate for larger recurring gifts.
- **Timezone** → all `next_charge_at` values in `Asia/Kolkata`. A UTC/IST mix-up puts charges on the
  wrong calendar day and produces confusing donor emails.
