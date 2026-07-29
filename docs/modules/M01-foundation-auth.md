# M01 — Foundation & Auth

**Phase 1, Sprint 1** · Depends on: nothing · Everything depends on this

## Purpose

The scaffolding: Laravel installed and configured, authentication for four audiences, a role and
permission system, and the shared value objects that the rest of the codebase leans on.

## Scope

**In:** Laravel 13 setup, package installation, `users` table, roles and permissions, public
auth flows, Filament admin panel bootstrap, shared `Support` classes, enums, CI.

**Out:** Social login, 2FA (candidates for v1.5), API authentication.

## Roles & permissions

Five roles seeded by `RolePermissionSeeder`.

| Role | Panel access | Summary |
|---|---|---|
| `super-admin` | `/admin` | Everything, including settings, roles, destructive actions |
| `admin` | `/admin` | Everything except settings, role management, permanent deletes |
| `manager` | `/manager` | Department-scoped members, documents, notices; read-only donations |
| `member` | `/portal` | Own profile, own documents, notices addressed to them |
| `donor` | `/portal` | Own donations, own receipts, own subscriptions |

A user can hold several roles — a member who also donates has both `member` and `donor`.

### Permission naming

`{action}_{resource}`, with `_any` for collection-level access:

```
view_any_member, view_member, create_member, update_member, delete_member, restore_member
view_any_donation, view_donation, create_donation, update_donation, refund_donation
view_any_campaign, create_campaign, update_campaign, publish_campaign, delete_campaign
issue_document, revoke_document, manage_document_templates
view_any_receipt, generate_receipt, cancel_receipt, export_form_10bd
view_any_notice, create_notice, publish_notice
view_any_subscription, cancel_subscription
manage_settings, manage_users, manage_roles
view_reports, export_reports
```

Roughly 60 permissions total. Filament resources read them via policies.

## Shared Support classes

These three are small, look trivial, and are load-bearing. Unit test them thoroughly in Sprint 1.

### `App\Support\Money`

Wraps an integer number of paise. Immutable.

```php
Money::fromPaise(150050)          // ₹1,500.50
Money::fromRupees('1500.50')      // string input, no float ever touches it
$m->toPaise(): int
$m->toRupees(): string            // '1500.50'
$m->format(): string              // '₹1,500.50'  (Indian digit grouping: 1,50,000)
$m->plus(Money), $m->minus(Money), $m->multiply(int), $m->percentage(float)
$m->isZero(), $m->isGreaterThan(Money)
```

Indian digit grouping matters: `₹12,34,567.00`, not `₹1,234,567.00`. Donors notice.

### `App\Support\FinancialYear`

Indian FY runs **April 1 → March 31**.

```php
FinancialYear::current(): string                  // '2026-27'
FinancialYear::for(Carbon|string $date): string   // '2025-26' for 2026-03-31
FinancialYear::startDate('2026-27'): Carbon       // 2026-04-01 00:00:00
FinancialYear::endDate('2026-27'): Carbon         // 2027-03-31 23:59:59
FinancialYear::all(int $back = 5): array
```

The boundary is the bug: 2026-03-31 is FY 2025-26; 2026-04-01 is FY 2026-27. Test both.

### `App\Support\NumberToWords`

Indian numbering (lakh, crore), used on every receipt.

```php
NumberToWords::rupees(150050);
// 'One Thousand Five Hundred Rupees and Fifty Paise Only'

NumberToWords::rupees(10000000);
// 'One Crore Rupees Only'
```

Test cases that must pass: 0, 1, 10, 100, 1000, 100000 (one lakh), 10000000 (one crore),
amounts with paise, amounts ending in zero paise (should omit the paise clause entirely).

## Enums

```php
DonationStatus:      Pending, Processing, Succeeded, Failed, Refunded, Cancelled
PaymentMode:         Upi, Card, Netbanking, Wallet, Cash, Cheque, BankTransfer, Other
SubscriptionStatus:  Created, PendingAuthentication, Active, Paused, Halted, Completed,
                     Cancelled, Expired
DocumentType:        IdCard, AppointmentLetter, Certificate
DocumentStatus:      Queued, Issued, Revoked, Superseded
CampaignStatus:      Draft, PendingReview, Active, Paused, Completed, Closed
MemberStatus:        Pending, Active, Suspended, Resigned, Expired
NoticeAudience:      AllMembers, Department, Designation, Specific, AllDonors
ReceiptSeries:       Donation, EightyG
```

Backed string enums with `label()` and `color()` methods for Filament badges.

## Auth flows

| Flow | Route | Notes |
|---|---|---|
| Register | `/register` | Assigns `donor` role by default |
| Login | `/login` | Redirects by role: admin → `/admin`, manager → `/manager`, else `/portal` |
| Forgot / reset password | `/forgot-password` | Rate limited 3/hour |
| Email verification | `/email/verify` | Required before portal access |
| Logout | `POST /logout` | |
| Admin login | `/admin/login` | Filament's own, gated to admin roles |
| Manager login | `/manager/login` | Filament's own, gated to `manager` |

Guest donors get a `users` row with `password = null` created lazily at donation time. They can
claim the account later via password reset — this is deliberate, and it means the donor's history is
already waiting for them when they do.

## Build checklist

- [ ] Laravel 13 + PHP 8.3, Git repo initialised, branch protection on
- [ ] All Composer packages from `01-ARCHITECTURE.md` §6 installed
- [ ] Tailwind + Alpine + Vite building
- [ ] `users` migration with `uuid`, `phone`, `is_active`, `last_login_at`, soft deletes
- [ ] Spatie permission tables migrated
- [ ] `RolePermissionSeeder` with 5 roles and ~60 permissions
- [ ] `AdminUserSeeder` reading credentials from `.env`
- [ ] `AdminPanelProvider` at `/admin` with role gate
- [ ] Public auth: register, login, logout, forgot/reset, verify
- [ ] Role-based post-login redirect
- [ ] `Money`, `FinancialYear`, `NumberToWords` — **with full unit test coverage**
- [ ] All enums with `label()` and `color()`
- [ ] Pint + Larastan level 5 configured and passing
- [ ] Pest configured, CI running tests on push
- [ ] `.env.example` complete

## Edge cases

- Two users registering with the same email simultaneously → unique constraint plus a friendly error.
- A guest donor later registering with the same email → link to the existing `users` row, don't
  create a second one.
- A user holding both `admin` and `manager` → admin wins for redirect purposes.
- Deactivating a user (`is_active = false`) must invalidate their existing sessions, not just block
  future logins.
