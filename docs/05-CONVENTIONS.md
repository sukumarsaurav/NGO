# 05 — Conventions

## 1. Naming

| Thing | Convention | Example |
|---|---|---|
| Model | Singular StudlyCase | `Donation`, `IssuedDocument` |
| Table | Plural snake_case | `donations`, `issued_documents` |
| Pivot table | Singular models, alphabetical | `campaign_tag` |
| Foreign key | `{singular}_id` | `campaign_id`, `donor_id` |
| Controller | Plural + `Controller` | `CampaignController` |
| Action | Verb phrase, no suffix | `RecordSuccessfulDonation` |
| Service | Noun + capability | `PdfRenderer`, `RazorpayGateway` |
| Job | Verb phrase | `GeneratePdfDocument` |
| Event | Past tense | `DonationSucceeded` |
| Listener | Verb phrase | `SendDonationThankYou` |
| Mailable | Noun + `Mail` | `Receipt80GMail` |
| Enum | Singular noun | `DonationStatus` |
| Blade view | kebab-case | `campaign-card.blade.php` |
| Route name | dot notation | `campaigns.show`, `donations.store` |
| Config key | snake_case | `ngo.donation.min_amount` |
| Setting key | dot namespaced | `org.pan`, `receipt.80g_prefix` |

## 2. Code style

- **PSR-12**, enforced by Laravel Pint. `composer pint` before every commit.
- **Strict types** at the top of every PHP file: `declare(strict_types=1);`
- **Typed everything** — parameters, return types, properties. Larastan level 5 minimum.
- **Constructor property promotion** for dependencies.
- **Final by default** for Actions and Services. Not final for Models (Eloquent needs to extend).
- **No facades inside Actions/Services** — inject dependencies. Facades are fine in controllers and
  Blade.

```php
<?php

declare(strict_types=1);

namespace App\Actions\Donations;

final readonly class RecordSuccessfulDonation
{
    public function __construct(
        private ReceiptNumberGenerator $numbers,
        private Dispatcher $events,
    ) {}

    public function handle(Donation $donation, PaymentResult $result): Donation
    {
        // ...
    }
}
```

## 3. The rules that matter most

**Money is `BIGINT` paise. Always.**

```php
// wrong — will eventually produce ₹999.9999999 on a legal document
$total = $donation->amount + $fee;

// right
$total = Money::fromPaise($donation->amount)->plus(Money::fromPaise($fee));
```

Never `float`. Never `round()` on rupees. Format only at the Blade layer.

**Every payment write is idempotent and transactional.**

```php
DB::transaction(function () use ($eventId, $payload) {
    $event = WebhookEvent::firstOrCreate(
        ['provider' => 'razorpay', 'event_id' => $eventId],
        ['event_type' => $payload['event'], 'payload' => $payload, 'status' => 'received'],
    );

    if (! $event->wasRecentlyCreated) {
        return; // already handled — this is normal, not an error
    }

    $donation = Donation::where('id', $donationId)->lockForUpdate()->firstOrFail();
    // ... mutate
});
```

**Receipt numbers are allocated under a row lock — and the row must already exist before you lock it.**

`lockForUpdate()` on a row that does not exist locks nothing, so create-if-missing *inside* the
transaction races on the first receipt of every financial year. The sequence row is created first,
outside the transaction; only then does the locked read happen.

**Do not reimplement this from memory.** The canonical implementation, with both failure modes
explained, lives in [`modules/M07-receipts-80g.md`](modules/M07-receipts-80g.md)
§`ReceiptNumberGenerator`. It used to be restated here and in `01-ARCHITECTURE.md`, and the three
copies had drifted into two different bugs.

**Never trust client-supplied amounts.** Read from the campaign or a server-side preset list. This
extends to **catalogue line items**: the client submits `{product_id, quantity}` pairs, and the server
re-reads every `unit_price` from the database and computes the totals itself. A price rendered in HTML
is a suggestion.

**Never log PAN, card data, or webhook payloads containing PII.** Redact before logging.

**Query with eager loading.** Every `foreach` over a relation is an N+1 until proven otherwise.
Run Debugbar on every page before shipping it.

## 4. Git workflow

```
main       ← production. Protected. Only merges from release/*.
develop    ← integration. Protected. Only merges from feature/*.
feature/*  ← feature/M05-razorpay-integration
fix/*      ← fix/receipt-number-race
release/*  ← release/v1.0.0
hotfix/*   ← branches from main, merges to main AND develop
```

**Commit format** (Conventional Commits):

```
feat(donations): add Razorpay webhook signature verification
fix(receipts): prevent duplicate numbers under concurrent load
docs(readme): document queue cron setup
test(subscriptions): cover halted-after-3-failures path
refactor(members): extract MemberCodeGenerator from CreateMember
chore(deps): bump filament to 3.2.4
```

Scope = module or domain area. Subject in imperative mood, lower case, no trailing period.

**PR checklist:**

- [ ] Branch is up to date with `develop`
- [ ] Pint clean, Larastan clean
- [ ] Tests written and passing
- [ ] Acceptance criteria from the sprint are met
- [ ] Migrations run on a fresh DB
- [ ] New `.env` keys added to `.env.example`
- [ ] Module doc updated if the design changed
- [ ] No debug statements, no commented-out code, no `dd()`
- [ ] Screenshots for UI changes

## 5. Testing

**Pest**, not PHPUnit syntax. Feature tests over unit tests, except for pure logic.

**Coverage targets:**

| Area | Target |
|---|---|
| Payments, webhooks, receipt numbering, subscriptions | **100%** — non-negotiable |
| Actions | ≥ 90% |
| Overall | ≥ 70% |
| Blade views, Filament resources | Smoke tests only |

```php
it('generates gap-free receipt numbers under concurrency', function () {
    $donations = Donation::factory()->count(50)->create();

    $numbers = collect($donations)->map(
        fn ($d) => app(Generate80GReceipt::class)->handle($d)->receipt_number
    );

    expect($numbers->unique())->toHaveCount(50);
});

it('ignores duplicate webhook deliveries', function () {
    $payload = razorpayPaymentCapturedPayload();

    postWebhook($payload);
    postWebhook($payload);
    postWebhook($payload);

    expect(Donation::where('status', 'succeeded')->count())->toBe(1);
});
```

Use `FakeGateway` for all payment tests. Never hit Razorpay's API from the test suite.

## 6. Database

- Every migration is reversible — write `down()` properly.
- Never edit a migration that has run in production. Write a new one.
- Foreign keys always have an explicit `onDelete` behaviour. For financial tables that is
  `restrict`, never `cascade` — you do not want a deleted donor to silently erase donation history.
- Index every column used in a `where`, `join`, or `order by` on a hot path.
- Composite indexes are column-order sensitive: most selective first.
- Financial records are never deleted. Status changes only.

## 7. Frontend

- Tailwind utility classes in Blade. Extract to `@apply` components only when a pattern appears
  four or more times.
- Alpine.js for local interactivity. Livewire for anything touching the server.
- Blade components for anything reused: `<x-campaign-card>`, `<x-progress-bar>`.
- Mobile-first breakpoints. Design at 360 px, then scale up.
- Every image lazy-loaded, WebP with fallback, explicit `width`/`height` to prevent layout shift.
- Every interactive element keyboard-accessible with a visible focus ring.
- Alt text on every meaningful image. Campaign photos especially — screen-reader users donate too.

## 8. Security baseline

- Rate limit: donation endpoint (10/min/IP), login (5/min), password reset (3/hour), webhook (60/min),
  document verification `/verify/{uuid}` (30/min/IP — prevents UUID enumeration), fundraiser requests
  and contact form (3/hour/IP).
- CSRF on every form. Webhooks excluded, but signature-verified instead.
- Escape all output. `{!! !!}` only on admin-authored rich text that has been purified first.
- File uploads: validate MIME by content, not extension. Cap size. Store outside the web root and
  serve through a controller when access needs authorisation.
- Encrypt PAN and ID proof numbers with the `encrypted` cast.
- Mask sensitive values in the UI: `XXXXX1234F`.
- Every Filament resource has a Policy. Hiding navigation is not authorisation.
- `APP_DEBUG=false` in production, always.

## 9. Documentation

- Update the module doc in `docs/modules/` whenever the design changes. A stale spec is worse than
  no spec.
- PHPDoc only where types can't express the intent — no `@param string $name` noise.
- Comment the *why*, never the *what*. `// row lock prevents duplicate receipt numbers under
  concurrent donations` is useful. `// increment the counter` is not.
- Any non-obvious business rule gets a comment citing its source (compliance requirement, client
  decision, gateway limitation).

---

Back to [`README`](../README.md) · Module specs in [`modules/`](modules/)
