# M03 — Members & Volunteers

**Phase 2, Sprint 3** · Depends on: M01, M02

## Purpose

The people register: members, volunteers, and office bearers. Organised into departments with
designations, which is what makes both the manager panel (M11) and appointment letters (M04) possible.

## Data

`departments`, `designations`, `members` — see `02-DATABASE-SCHEMA.md` §3.

### Member lifecycle

```
pending ──approve──► active ──┬──suspend──► suspended ──reinstate──► active
                              ├──resign───► resigned
                              └──expire───► expired      (valid_until passed)
```

Only `active` members get documents issued. Suspending revokes portal access but preserves
already-issued documents (they show as valid until explicitly revoked — a suspension is not
necessarily permanent).

### Member code

`MemberCodeGenerator` produces `{PREFIX}-{YYYY}-{NNNNN}`, e.g. `VGWGF-2026-00123`. Prefix comes from
settings. Sequence is per calendar year, allocated under a row lock (same pattern as receipt numbers,
though the stakes are much lower here).

## Actions

| Action | Does |
|---|---|
| `CreateMember` | Creates `users` + `members` rows in a transaction, generates member code, assigns `member` role, fires `MemberCreated` |
| `UpdateMember` | Updates profile; logs changed fields to activity log |
| `AssignDesignation` | Sets `designation_id`, fires `DesignationAssigned` → triggers appointment letter |
| `DeactivateMember` | Status change + optional document revocation + portal access removal |
| `BulkImportMembers` | CSV import with dry-run, per-row validation, error report |

## Events

- `MemberCreated` → listener `IssueIdCardOnMemberCreated` (if auto-issue is enabled in settings)
- `DesignationAssigned` → listener `IssueLetterOnDesignationAssigned`

Auto-issue is a settings toggle. Some organisations want ID cards issued automatically on approval;
others want a human to check the photo first.

## UI

### Admin (`/admin`)

**`MemberResource`**
- Table: photo thumbnail, name, member code, department, designation, status badge, joined date
- Filters: status, department, designation, joined-date range, has-photo
- Search: name, member code, email, phone
- Row actions: view, edit, issue document (dropdown by type), deactivate
- Bulk actions: issue ID cards, export CSV, change department, change status
- Form tabs: Personal · Contact · Organisation · ID proof · Internal notes
- Photo upload with a 1:1 crop tool — ID cards look bad with arbitrary aspect ratios

**`DepartmentResource`** — tree view for nested departments, assign a manager user.

**`DesignationResource`** — title, rank, linked appointment-letter template.

### Member portal (`/portal`)

- Profile view and limited edit (contact details, photo; **not** department, designation, or status)
- Documents list with downloads
- Notices inbox

## CSV import

The single most-requested feature when onboarding an NGO with an existing spreadsheet of 300 members.

Flow: upload → column mapping UI → **dry run** → error report → confirm → import.

Validation per row: required fields, email format and uniqueness, phone format, date parsing
(accept `dd/mm/yyyy` and `yyyy-mm-dd`), department and designation resolution by name with
suggestions for near-misses.

The dry run must write nothing. Show a table: N rows valid, M rows with errors, each error naming
the row number and the field.

## Build checklist

- [ ] `departments`, `designations`, `members` migrations + models + factories
- [ ] Nested department support (`parent_id`) with a tree UI
- [ ] `MemberCodeGenerator` with locking and a uniqueness test
- [ ] All 5 actions implemented
- [ ] `MemberResource` with the full filter and bulk-action set
- [ ] Photo upload with 1:1 crop, resized to 400×400
- [ ] `id_proof_number` encrypted cast
- [ ] CSV import with column mapping, dry run, and per-row error reporting
- [ ] Member portal profile view/edit
- [ ] `MemberPolicy` covering all five roles
- [ ] `MemberCreated` and `DesignationAssigned` events firing
- [ ] Activity logging on status and designation changes

## Edge cases

- **Member with no photo** → ID card renders a neutral placeholder silhouette, not a broken image.
- **Member changes department after documents are issued** → old documents keep the old department
  via `snapshot_data`. This is correct behaviour, not a bug, and should be explained in the admin UI.
- **Duplicate email on import** → skip the row with an error; never overwrite an existing member
  silently.
- **`valid_until` in the past** → a nightly job flips status to `expired` and notifies the admin.
- **Deleting a department that has members** → block it. Require reassignment first.
- **A manager tries to move a member into a department they don't manage** → 403.
- **Member who is also a donor** → one `users` row, two roles, two profile records (`members` and
  `donors`). Do not merge these tables; their lifecycles are unrelated.
