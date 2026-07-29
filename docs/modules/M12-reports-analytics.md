# M12 — Reports & Analytics

**Phase 7, Sprint 14** · Depends on: M05, M06, M07, M08

## Purpose

Turn the data into the answers the NGO's leadership, accountant, and auditor actually ask for.

The audience is not analysts. It's a founder who wants to know if this month is better than last, an
accountant who needs to reconcile against a bank statement, and an auditor who wants a gap-free
receipt register. Design for those three.

## Dashboard widgets

Admin landing page.

| Widget | Shows |
|---|---|
| Donation stats | Today / this month / this FY, each with a change vs the previous period |
| Monthly trend | 12-month line chart of donation totals |
| MRR | Active recurring revenue, plus new and churned this month |
| Campaign progress | Top 5 campaigns by raised amount, with progress bars |
| Recent donations | Last 10, with donor, amount, campaign |
| Member growth | New members per month, 12-month bar chart |
| Attention needed | Halted subscriptions, failed PDF jobs, bounced receipt emails, donors missing PAN |
| Receipt summary | Issued this FY, 80G vs general |

The "attention needed" widget is the most valuable one on the page. It surfaces the failures that
otherwise sit silently in a log until a donor complains.

Widgets must be fast — cache aggregates with a 5-minute TTL. A dashboard that takes six seconds is a
dashboard nobody opens.

## Reports

Each supports date-range or FY filtering, on-screen viewing with pagination, and CSV/XLSX export.

| Report | Columns | Used by |
|---|---|---|
| **Donation ledger** | Date, receipt no, donor, PAN, amount, mode, campaign, status, fee, net | Accountant |
| **Donor summary** | Donor, total donated, count, first/last donation, PAN status | Fundraising |
| **Receipt register (FY)** | Receipt no, date, donor, amount, series, cancelled | Auditor |
| **Gap detection** | Any break in receipt numbering per series per FY | Auditor — **should always be empty** |
| **Form 10BD export** | Prescribed IT format, per-donor FY aggregation | Statutory filing |
| **Subscription health** | Donor, amount, status, cycles, collected, next charge, failures | Fundraising |
| **Campaign performance** | Campaign, goal, raised, %, donors, avg gift, days active | Leadership |
| **Member roster** | Code, name, department, designation, status, joined, ID card validity | Operations |
| **Document issue log** | Doc no, type, member, issued by, date, status | Compliance |
| **Payment reconciliation** | Gross, gateway fee, tax, net, expected bank credit, by date | Accountant |
| **Failed payments** | Date, donor, amount, error code, description | Ops / gateway support |

### Payment reconciliation deserves emphasis

The bank statement shows ₹9,764 credited. The donation ledger shows ₹10,000 received. Without a
report that reconciles gross to net through gateway fees and GST, the accountant spends hours every
month and eventually asks you to build this anyway. Build it in Sprint 14.

### Gap detection

Runs across `receipts` per series per FY, checking for breaks in the sequence. Should always return
zero rows.

It queries `receipts.sequence_number` — the raw integer — **not** `receipt_number`. Parsing
`VGWGF/80G/2026-27/00042` out of a `VARCHAR(50)` to find gaps is fragile and cannot use an index; the
integer column plus `(series, financial_year, sequence_number)` makes it one fast query. Cancelled
receipts count as present: they keep their number, so the sequence is still unbroken. If it ever doesn't, something has gone wrong with the numbering logic and it needs
immediate attention — this report is an alarm, not an analysis.

## Financial year handling

Every financial report defaults to the Indian FY (April 1 – March 31), not the calendar year, and
the FY picker is the primary control. Getting this wrong makes every report subtly useless to the
accountant.

## Exports

Via `maatwebsite/excel`. Both CSV and XLSX for every report.

**Stream large exports** using `FromQuery` with chunking. Loading 50,000 donations into memory to
build a spreadsheet will exhaust PHP's memory limit on shared hosting. Test with the 10,000-row
demo dataset.

Every export carries a header block: NGO name, report title, date range, generated-at timestamp,
generated-by user. An exported file that lands in an inbox with no context is a file someone will
misread.

## Scheduled reports

A monthly summary email to admins on the 1st: last month's total, donor count, new recurring donors,
churn, top campaign, and anything in "attention needed."

This is the report the founder will actually read, because it arrives without being asked for.

## Build checklist

- [ ] All 8 dashboard widgets, with 5-minute aggregate caching
- [ ] Dashboard loads under 2 s with 10,000 seeded donations
- [ ] All 11 reports with filters and pagination
- [ ] CSV + XLSX export for each
- [ ] Streamed exports; 10,000 rows without memory exhaustion
- [ ] Export header block on every file
- [ ] FY-aware date filtering everywhere
- [ ] Gap-detection report
- [ ] Payment reconciliation report
- [ ] Form 10BD export wired in (from M07)
- [ ] Monthly summary email scheduled
- [ ] Report totals reconcile exactly against raw DB aggregates — verified by test
- [ ] Manager-scoped versions of member and document reports

## Edge cases

- **Empty date range** → friendly empty state with the applied filters echoed back, not a blank page.
- **Very large export** → stream, and warn above 50,000 rows that it may take a while.
- **Report totals disagreeing with the dashboard** → both must read the same query methods. Never
  write the aggregation logic twice; extract it and share it.
- **Timezone** → all reports in `Asia/Kolkata`. A UTC-stored `donated_at` rendered in UTC will push
  late-evening donations into the previous day and make daily totals wrong.
- **Cancelled receipts in the register** → included, clearly marked. Excluding them creates apparent
  gaps and defeats the point of the register.
- **Refunded donations** → shown separately, excluded from totals, never silently dropped.
- **Donor with no PAN in Form 10BD** → excluded from the export and listed in the pre-export
  validation report so someone can chase it before filing.
- **FY with no donations** → zero-state report, not an error.
- **Manager running a report** → automatically scoped to their departments; the scope is stated
  visibly on the report header so they know what they're looking at.
