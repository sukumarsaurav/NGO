# M09 — Notices & Communication

**Phase 6, Sprint 12** · Depends on: M01, M03

## Purpose

Two related capabilities from the pamphlet:

1. **Notice feature** — "सदस्यों को Notice भेजने की सुविधा". Targeted announcements to members,
   delivered in-app and by email.
2. **Email Action** — "वेबसाइट के हर एक्शन पर ईमेल की सुविधा". Every system event that should notify
   someone, driven by admin-editable templates.

## Data

`notices`, `notice_recipients`, `email_templates`, `subscribers` — see `02-DATABASE-SCHEMA.md` §9.

## Notices

### Audience targeting

| Audience | Resolves to |
|---|---|
| `all_members` | Every member with status `active` |
| `department` | Members in the selected department(s), including sub-departments |
| `designation` | Members holding the selected designation(s) |
| `specific` | Hand-picked users |
| `all_donors` | Every donor who has opted into communication |

The audience is resolved **at publish time** and materialised into `notice_recipients`. This is
deliberate: if a member joins next week, they shouldn't retroactively receive last week's notice,
and the read-tracking numbers need a fixed denominator.

Show a live recipient count in the compose UI before sending — "this will reach 247 members." Nobody
should discover the audience size after hitting send.

### Delivery

```
PublishNotice
   ├─ resolve audience → create notice_recipients rows
   ├─ status = sending
   └─ dispatch DispatchNoticeToRecipients
         └─ chunk recipients (100 at a time)
               └─ queue SendNoticeEmail per chunk
                     ├─ render from email_templates
                     ├─ send
                     └─ update recipient email_status
   └─ status = sent when all chunks complete
```

Chunking and queueing matter. Sending to 500 members in a single request will time out and will trip
your SMTP provider's rate limit. Throttle to your provider's documented limit — many shared-hosting
SMTP accounts cap at 100–300 emails/hour, which is a real constraint worth checking before launch.

### Read tracking

`notice_recipients.read_at` is set when the member opens the notice in `/portal`. Not an email open
pixel — those are unreliable, increasingly blocked, and mildly invasive. Portal reads are honest data.

Admin sees "read by 132 of 247."

## Email templates

Every automated email in the system routes through `email_templates`, so the NGO can change copy
without a developer.

### Seeded templates

| Key | Trigger |
|---|---|
| `donation.thank_you` | Successful donation |
| `donation.receipt` | Donation receipt attached |
| `donation.failed` | Payment failed |
| `receipt.80g` | 80G receipt issued |
| `subscription.activated` | Monthly mandate set up |
| `subscription.charged` | Monthly charge succeeded |
| `subscription.charge_failed` | Charge failed |
| `subscription.halted` | Halted after repeated failures |
| `subscription.cancelled` | Cancelled |
| `member.welcome` | Member account created |
| `member.id_card_issued` | ID card ready |
| `member.appointment_letter` | Appointment letter issued |
| `member.certificate_issued` | Certificate issued |
| `notice.default` | Notice email wrapper |
| `campaign.update` | Campaign update published |
| `fundraiser.received` | Fundraiser request acknowledged |
| `fundraiser.approved` / `fundraiser.rejected` | Review outcome |
| `newsletter.confirm` | Double opt-in |

### Variables

Each template declares its available variables in `available_variables`, shown as a reference in the
editor. Substitution uses a **whitelist**, not Blade compilation — admin-authored templates must
never be able to execute arbitrary code.

```
{{ donor_name }} {{ amount }} {{ amount_in_words }} {{ campaign_title }}
{{ receipt_number }} {{ member_name }} {{ member_code }} {{ document_number }}
{{ org_name }} {{ org_logo }} {{ org_phone }} {{ portal_url }} {{ unsubscribe_url }}
```

### Editor UI

Rich-text or HTML editor, subject line with variable support, variable reference sidebar, live
preview with sample data, and a **test-send to the logged-in admin**. The test send is what stops
badly-formatted emails reaching 500 donors.

## Newsletter

Double opt-in: subscribe → confirmation email → confirmed. Unconfirmed subscribers are never emailed
and are purged after 30 days.

One-click unsubscribe via a tokenised link, in the footer of every non-transactional email.
Transactional emails (receipts, document notifications) don't carry unsubscribe links — those aren't
marketing and the donor needs them.

## UI

**Admin** — `NoticeResource`: compose with a rich editor, audience picker with live recipient count,
priority, attachment, schedule, save-as-draft. Table shows status, audience, recipient count, read
count, sent date. Actions: view recipients, resend to failures.

`EmailTemplateResource`: list by key, edit, preview, test-send, activate/deactivate.

`SubscriberResource`: list, filter by confirmed/unsubscribed, export.

**Member portal** — notice inbox, unread badge, priority indicators, attachment downloads, marks read
on open.

## Build checklist

- [ ] `notices`, `notice_recipients`, `email_templates`, `subscribers` migrations + models
- [ ] `PublishNotice` + `DispatchNoticeToRecipients` actions
- [ ] All five audience resolvers, including nested departments
- [ ] Live recipient count in the compose UI
- [ ] `SendNoticeEmail` chunked and throttled
- [ ] Per-recipient `email_status` tracking
- [ ] Read tracking in the portal
- [ ] `EmailTemplateSeeder` with all templates and default copy
- [ ] Whitelist-based variable substitution (**not** Blade compilation of DB content)
- [ ] Template editor with preview and test-send
- [ ] Every transactional Mailable routed through `email_templates`
- [ ] Newsletter double opt-in
- [ ] One-click unsubscribe honoured on all subsequent sends
- [ ] `NoticeResource`, `EmailTemplateResource`, `SubscriberResource`
- [ ] Member portal notice inbox
- [ ] Scheduled notices firing via the scheduler

## Edge cases

- **500+ recipients** → chunked, queued, throttled. Test with a seeded 500-member dataset.
- **SMTP rate limit hit** → the job backs off and retries rather than failing the batch. Check the
  provider's limit during Sprint 12, not on launch day.
- **Bounced email** → record `bounced`, surface in a failures list, don't retry blindly.
- **Member has no email** → skip, record the reason, still show the notice in their portal.
- **Notice deleted after sending** → soft delete; recipients keep their portal copy.
- **Template with a malformed variable** → render the literal text, log a warning, never fatal
  mid-batch.
- **Scheduled notice whose audience changes before send time** → resolve at send time, not compose
  time, and show the compose-time count as an estimate.
- **Unsubscribed donor receives a transactional receipt** → correct and intended. Unsubscribe governs
  marketing, not statutory documents.
- **Duplicate recipient** (member in two targeted departments) → `UNIQUE (notice_id, user_id)`
  guarantees one email.
- **Very large attachment** → cap at 5 MB; above that, link to a download instead of attaching.
