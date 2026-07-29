# M07 — Receipts & 80G Compliance

**Phase 4, Sprint 8** · Depends on: M02, M05

> The most compliance-sensitive module in the system. An 80G receipt is a document a donor submits to
> the Income Tax Department. Errors here have consequences for the donor and for the NGO's
> registration.
>
> Nothing in this document is legal advice. Have the NGO's chartered accountant review the receipt
> format and the Form 10BD export before launch.

## Purpose

Two receipt series:

1. **Donation receipt** — an immediate acknowledgement for every successful donation. No statutory
   requirements. Issued instantly, always.
2. **80G receipt** — the tax-deduction certificate. Strict content requirements, strict numbering
   requirements, only issued when the donation and donor qualify.

## Why two series

Not every donation qualifies for 80G — cash above ₹2,000 doesn't, donors without PAN can't be
reported, and the NGO's 80G registration may have lapsed. But every donor deserves an
acknowledgement.

Separate series means the 80G sequence stays clean and auditable, and a donor who can't get an 80G
receipt still gets something.

## Receipt numbering — the hard part

**Requirements:**
- Unique across the system
- **Sequential with no gaps** within a financial year
- Resets to 1 on April 1
- Never reused — not even for a cancelled receipt
- Correct under concurrent load

Auto-increment IDs don't satisfy this (they don't reset per FY and they leave gaps on rollback).
`MAX(number) + 1` is a textbook race condition. The answer is a dedicated sequence table with a row
lock.

### `ReceiptNumberGenerator`

**This is the canonical implementation.** `01-ARCHITECTURE.md` §5 and `05-CONVENTIONS.md` §3 refer to
it rather than restating it — three copies of a concurrency-critical routine is three copies to drift.

```php
public function next(ReceiptSeries $series, string $financialYear): ReceiptNumber
{
    // STEP 1 — outside the transaction, guarantee the row exists.
    $this->ensureSequenceExists($series, $financialYear);

    // STEP 2 — now the lock has something to hold.
    return DB::transaction(function () use ($series, $financialYear) {
        $sequence = ReceiptSequence::query()
            ->where('series', $series)
            ->where('financial_year', $financialYear)
            ->lockForUpdate()                    // ← the entire point of this class
            ->firstOrFail();

        $sequence->increment('last_number');

        $padding = (int) Settings::get('receipt.number_padding', 5);

        return new ReceiptNumber(
            sequence: $sequence->last_number,
            formatted: $sequence->prefix
                . str_pad((string) $sequence->last_number, $padding, '0', STR_PAD_LEFT),
        );
    });
}

private function ensureSequenceExists(ReceiptSeries $series, string $fy): void
{
    try {
        ReceiptSequence::firstOrCreate(
            ['series' => $series, 'financial_year' => $fy],
            ['prefix' => $this->prefixFor($series, $fy), 'last_number' => 0],
        );
    } catch (UniqueConstraintViolationException) {
        // Another request created it between our SELECT and our INSERT.
        // That is the outcome we wanted anyway — carry on.
    }
}
```

Produces `VGWGF/80G/2026-27/00042`, and returns the raw integer alongside it (see below).

**`lockForUpdate()` is not optional.** Without it, two donations landing in the same millisecond both
read `last_number = 41` and both write 42. Two donors then hold receipts with the same number, which
is exactly the kind of thing that surfaces during an audit.

**Step 1 is not optional either, and it is the subtler bug.** `lockForUpdate()` on a row that *does
not exist* locks nothing — there is no row to lock. Create-if-missing inside the transaction means
that on **1 April**, when the first two donations of a new financial year arrive together, both find
no sequence row, both attempt `create()`, and the second violates `UNIQUE (series, financial_year)`
and throws. The same happens on a brand-new installation's first concurrent donations.

> **The Sprint 6 concurrency test will not catch this**, because by the time it runs the sequence row
> already exists. Add a second test that starts from an **empty `receipt_sequences` table** and fires
> concurrent requests — that is the case that actually breaks.

**Store the raw integer, not just the string.** `receipts.sequence_number` holds `42` while
`receipt_number` holds `VGWGF/80G/2026-27/00042`. Gap detection (M12) has to find breaks in the
sequence, and doing that by string-parsing an unindexed `VARCHAR(50)` is both fragile and unindexable.
Index `(series, financial_year, sequence_number)` and the gap report becomes a single fast query.

**Padding comes from `receipt.number_padding`**, not a literal — the setting exists and must actually
be read.

**Test it under real concurrency.** A sequential loop in a test will always pass. Use `ab`, `k6`, or
parallel processes to fire 100 simultaneous donations and assert 100 unique, gap-free numbers. This
is the single most valuable test in the codebase.

**Keep the transaction tiny.** Only the sequence read and increment belong inside it. Never generate
a PDF inside a lock — you'll hold the row for seconds and serialise every concurrent donation behind it.

## 80G receipt contents

Every field must be present. A missing field can invalidate the donor's deduction claim.

| Field | Source |
|---|---|
| NGO name and full address | `settings` |
| NGO PAN | `settings` (encrypted) |
| 80G registration number | `settings` |
| 80G validity period | `settings` |
| 12A registration number | `settings` |
| Receipt number (unique, sequential) | `ReceiptNumberGenerator` |
| Date of receipt | issue date |
| Donor full name | `donors` |
| Donor full address | `donors` |
| Donor PAN | `donors` (encrypted) |
| Amount in figures | `donations.amount` |
| **Amount in words** | `NumberToWords` |
| Mode of payment | `donations.payment_mode` |
| Date of donation | `donations.donated_at` |
| Financial year | `donations.financial_year` |
| Purpose / campaign | `campaigns.title` or "General Fund" |
| Deduction statement | boilerplate from settings |
| Authorised signatory name and designation | `settings` |
| Signature image, seal image | `settings` |
| QR code → verification URL | generated |

Everything is frozen into `receipts.snapshot_data` at generation time. If the NGO changes address in
2028, a receipt issued in 2026 must still show the 2026 address — that's what the donor filed with
their return.

## Eligibility rules

`Generate80GReceipt` refuses, with a specific named reason, when:

| Condition | Reason surfaced |
|---|---|
| `donation.status !== succeeded` | Donation not completed |
| `donation.eligible_for_80g === false` | Not eligible (e.g. cash > ₹2,000) |
| Donor PAN missing | PAN required — donor prompted to add it |
| Donor address incomplete | Address required for 80G |
| `org.80g_valid_to` in the past | **NGO's 80G registration has expired** |
| Donation date outside 80G validity | Donation predates or postdates the registration |
| A non-cancelled 80G receipt already exists | Already issued |

Each reason is shown clearly in the admin UI and, where the donor can fix it, in the donor portal
with a direct link to the relevant form.

## Cash above ₹2,000

Under Section 80G, cash donations exceeding ₹2,000 are not deductible. The system enforces this at
donation-recording time (`eligible_for_80g = false`) and again at receipt generation, and explains
why rather than failing opaquely. Non-cash modes are unaffected regardless of amount.

## Form 10BD export

The annual statement of donations the NGO files with the Income Tax Department. The system exports a
CSV in the prescribed shape:

```
Sl. No. | Pre Acknowledgement Number | ID Type | ID Number | Name of donor |
Address of donor | Donation Type | Mode of receipt | Amount | Section code
```

- **ID Type / ID Number** — PAN (or Aadhaar where PAN is unavailable)
- **Donation Type** — Corpus / Specific grant / Others
- **Mode of receipt** — Cash / Kind / Electronic modes including account payee cheque or draft / Others
- **Section code** — Section 80G

The exporter groups **multiple donations from the same donor in the same FY into a single row** with
the summed amount — that's how the form works, and getting it wrong means a rejected filing.

Export UI: pick financial year → preview counts and totals → validate (list donors missing PAN or
address) → download CSV.

Form 10BE (the certificate issued to donors after filing) is generated by the IT portal, not by us.

## Annual consolidated statement

Optional but appreciated: one summary PDF per donor per financial year listing every donation and the
total, useful for donors filing returns. Generated on demand or in bulk after year-end.

## Cancellation and reissue

Receipts are cancelled, never deleted. `is_cancelled = true` with a reason. **The number is retained
and never reused** — a gap-free sequence with a cancelled entry is correct; a reused number is not.

Triggers: donation refunded, donation cancelled, data correction requiring reissue.

Reissuing after cancellation allocates a **new** number and writes a **new row**.

### Why `receipts` needs a `revision` column

A constraint of `UNIQUE (donation_id, series)` would make reissue impossible: the cancelled row still
holds that pair, so the replacement cannot be inserted, and every partial refund would fail on a
constraint violation.

The constraint is therefore `UNIQUE (donation_id, series, revision)`, with `revision` incrementing
per reissue. The "one 80G receipt per donation" rule is real but it is an **application** invariant,
not a column-pair uniqueness one:

> At most one **non-cancelled** receipt may exist per `(donation_id, series)`.

`Generate80GReceipt` enforces it inside the same transaction that allocates the number: lock the
donation row, assert no live receipt exists for that series, then insert at
`revision = MAX(revision) + 1`. MySQL 8 has no partial indexes, so this cannot be pushed into the
schema — it has to be a guarded write, and it needs a test that hammers it concurrently.

## UI

**Admin** — `ReceiptResource`: number, series, donor, amount, FY, issued date, email status. Filters
by series, FY, email status, cancelled. Actions: download, re-email, cancel, regenerate. Bulk:
download ZIP, re-email failed.

Reports section: FY receipt register with totals, a **gap-detection report** (should always be
empty — if it isn't, something is seriously wrong), donors missing PAN, Form 10BD export.

**Donor portal** — all receipts, download individually, download all for an FY as a ZIP, prompt to
add PAN if missing with an explanation of why it matters.

## Build checklist

- [ ] `receipts` (with `revision` and `sequence_number`), `receipt_sequences` migrations + models
- [ ] `ReceiptNumberGenerator` with `ensureSequenceExists` **outside** the lock, then `lockForUpdate`
- [ ] Padding read from `receipt.number_padding`, not hard-coded
- [ ] **Concurrency test: 100 parallel donations → 100 unique, gap-free numbers**
- [ ] **Cold-start concurrency test: empty `receipt_sequences`, concurrent requests → no unique-key
      violation** (the Apr 1 / fresh-install case the test above cannot reach)
- [ ] Cancel-then-reissue produces a second row at `revision = 2` with a new number
- [ ] Concurrent `Generate80GReceipt` for one donation yields exactly one live receipt
- [ ] FY reset verified across the Mar 31 / Apr 1 boundary
- [ ] `GenerateDonationReceipt` action + PDF template
- [ ] `Generate80GReceipt` action with all seven eligibility gates
- [ ] 80G PDF template with every field from the table above
- [ ] `snapshot_data` frozen; verified by a test that mutates settings afterwards
- [ ] Amount in words on every receipt, correct for lakhs and crores
- [ ] QR verification for receipts
- [ ] Cancellation preserving numbers
- [ ] `Form10BDExporter` with per-donor FY aggregation
- [ ] Validation report: donors missing PAN or address
- [ ] Gap-detection report
- [ ] Annual consolidated statement
- [ ] `ReceiptResource` with bulk actions
- [ ] Donor portal receipt access + PAN prompt
- [ ] Mailables with PDF attached
- [ ] **100% test coverage on this module**

## Edge cases

- **Concurrent generation** → row lock. Load-tested, not assumed.
- **FY boundary** → a donation at 2026-03-31 23:59 belongs to FY 2025-26; at 2026-04-01 00:01 to
  2026-27. Test both, in `Asia/Kolkata`.
- **80G expires mid-year** → block issuance for donations dated after `80g_valid_to`, alert the admin
  60 days before expiry.
- **Donor adds PAN after donating** → allow retroactive 80G generation for donations in the current
  and previous FY.
- **Refund after receipt issued** → cancel the receipt, keep the number, notify the donor.
- **Partial refund** → cancel and reissue for the net amount with a new number.
- **Email bounces** → record `email_status = bounced`, surface in a "failed deliveries" list, let the
  admin correct the address and resend.
- **Two live receipts for one donation** → prevented by the guarded write described above (lock the
  donation, assert no non-cancelled receipt for that series, then insert). *Not* by a column-pair
  unique index — that would block legitimate reissues.
- **PDF generation fails** → the receipt **record** with its allocated number still exists (the number
  is consumed, correctly). Retry generates the file only, keeping the same number.
- **Corporate donor** → `donor_type = company`, receipt shows the company name and its PAN.
- **Anonymous donor requesting 80G** → impossible; 80G requires identification. The UI must explain
  this at donation time, before they choose anonymity, not afterwards.
