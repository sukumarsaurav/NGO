# M04 — Document Engine (PDF + QR)

**Phase 2, Sprint 4** · Depends on: M02, M03

## Purpose

One engine that produces all three member documents from the pamphlet — ID cards, appointment
letters, achievement certificates — each carrying a QR code that resolves to a public verification
page. Later reused unchanged by M07 for donation and 80G receipts.

Building one templated engine rather than three bespoke PDF generators is the difference between a
week and three weeks, and it means a new document type in v2 costs an afternoon.

## Data

`document_templates`, `issued_documents` — see `02-DATABASE-SCHEMA.md` §4.

## The snapshot principle

`issued_documents.snapshot_data` freezes every field the PDF displays at the moment of issue.

Why: a member's appointment letter from March 2026 says "Regional Coordinator, North Zone." In 2027
they're promoted. If the PDF were regenerated from live relations, the 2026 letter would retroactively
claim they were something they weren't. That's a document-integrity failure, and for an appointment
letter it's a serious one.

The PDF renders **only** from `snapshot_data`, never from `$member->department->name`.

## Services

### `PdfRenderer`

```php
public function render(string $templateHtml, array $data, PdfOptions $options): string;
public function renderToFile(string $html, array $data, PdfOptions $o, string $path): string;
```

Wraps dompdf. `PdfOptions` carries page size, orientation, margins, and DPI.

**Page sizes:** ID card = `CR80` (85.6 × 54 mm, landscape) — define it as a custom size in points:
`[0, 0, 242.65, 153.07]`. Letters and certificates = A4.

### `QrCodeGenerator`

```php
public function forDocument(IssuedDocument $doc): string;   // returns base64 PNG data URI
public function verifyUrl(string $uuid): string;            // https://site/verify/{uuid}
```

QR encodes the **verification URL**, not member data. Encoding personal data directly into a QR is a
privacy leak — anyone who photographs a card gets the payload. A URL means the server decides what to
reveal, and can revoke.

Error correction level **M**, minimum 20 mm printed size for reliable phone scanning.

### `DocumentNumberGenerator`

Type-prefixed, year-scoped: `IDC-2026-00042`, `APL-2026-00013`, `CRT-2026-00087`.

## Templates

`document_templates.body_html` holds Blade-compatible HTML. Admins edit it in a code editor field
with a live preview against sample data.

Available variables, documented in the UI:

```
{{ $member.name }} {{ $member.code }} {{ $member.photo }} {{ $member.blood_group }}
{{ $member.department }} {{ $member.designation }} {{ $member.joined_on }}
{{ $member.valid_until }} {{ $member.phone }} {{ $member.email }}
{{ $document.number }} {{ $document.issued_on }} {{ $document.title }}
{{ $qr }}
{{ $org.name }} {{ $org.logo }} {{ $org.address }} {{ $org.signature }} {{ $org.seal }}
```

Rendering uses Blade's string renderer with a **restricted** variable set — never `eval`, never raw
PHP from the database. Template HTML is admin-authored, but admin accounts get compromised.

## Actions

| Action | Trigger | Does |
|---|---|---|
| `IssueIdCard` | Manual, or `MemberCreated` if auto-issue is on | Creates `issued_documents` row (`queued`), dispatches `GeneratePdfDocument` |
| `IssueAppointmentLetter` | Manual, or `DesignationAssigned` | Same, using the designation's linked template |
| `IssueCertificate` | Manual, with a title and body | Same |
| `RevokeDocument` | Manual | Sets `revoked`, records reason and timestamp |
| `RegenerateDocument` | Manual | Issues a new document, marks the old `superseded`, links `superseded_by_id` |

All PDF generation happens in the queued `GeneratePdfDocument` job. dompdf takes 2–5 seconds on
shared hosting; that cannot sit in a web request.

## Verification page

Public route `GET /verify/{uuid}` — no auth, this is the whole point of the QR.

| Document state | Page shows |
|---|---|
| `issued`, not expired | Green. Photo, name, member code, designation, department, issued date, valid until, document number. |
| `issued`, past `valid_until` | Amber. "This document has expired." Same details. |
| `revoked` | Red. "This document has been revoked." Revocation date and reason. No member details. |
| `superseded` | Amber. "A newer version of this document exists." |
| Not found | Neutral. "No document found with this code." |

Deliberately **not** shown: phone, email, address, ID proof number, date of birth. Anyone who
photographs a card can load this page.

Rate limited (30/min/IP) to prevent UUID enumeration. Each load increments `verified_count`.

## UI

**`DocumentTemplateResource`** — HTML editor with syntax highlighting, CSS field, variable reference
sidebar, page-size and orientation pickers, background image upload, QR position, live PDF preview
against a sample member.

**`IssuedDocumentResource`** — table of everything issued, filtered by type, status, member,
department, issue-date range. Actions: download, revoke, regenerate, re-email. Bulk: download as ZIP.

**On `MemberResource`** — an "Issue Document" action with a type picker, and a bulk "Issue ID cards"
action for selected members.

**Member portal** — list of own documents with downloads. Revoked documents show as revoked rather
than disappearing, so the member knows.

## Build checklist

- [ ] `document_templates`, `issued_documents` migrations + models
- [ ] `PdfRenderer` with A4 and CR80 support
- [ ] `QrCodeGenerator` producing scannable 20 mm codes
- [ ] `DocumentNumberGenerator`
- [ ] All 5 actions
- [ ] `GeneratePdfDocument` queued job with retry and failure logging
- [ ] Three default Blade templates seeded
- [ ] **Noto Sans Devanagari embedded; Hindi renders correctly** — verify visually
- [ ] `snapshot_data` frozen at issue; verified by a test that mutates the member afterwards
- [ ] Public `/verify/{uuid}` with all five states
- [ ] Rate limiting on the verify route
- [ ] `DocumentTemplateResource` with live preview
- [ ] `IssuedDocumentResource` with bulk ZIP download
- [ ] Mailables for all three document types
- [ ] Bulk-issue 50 ID cards without timeout or OOM

## Edge cases

- **Hindi/Devanagari text.** dompdf renders unembedded Unicode as blank boxes, silently. Test this
  in week 4, not week 15. If dompdf proves inadequate for the design, the fallback is
  `spatie/browsershot` — but that needs headless Chrome, which needs a VPS. Know this early.
- **Member photo missing or corrupt** → placeholder silhouette, log a warning, don't fail the job.
- **Template references a variable that doesn't exist** → render empty, log it, don't fatal. A
  half-rendered card is better than a failed batch of 50.
- **PDF job fails** → document stays `queued`, admin sees a "generation failed" badge with a retry
  action. Never leave a document silently stuck.
- **Bulk issue of 200 documents** → chunk into batches of 20, use `Bus::batch()` with progress
  reporting.
- **QR scanned after the member is deleted** → verify page shows "not found", never a 500.
- **Two admins issue an ID card to the same member simultaneously** → both succeed, second supersedes
  first. Acceptable; log both.
- **Storage full** → job fails loudly and alerts, rather than writing a truncated PDF.
