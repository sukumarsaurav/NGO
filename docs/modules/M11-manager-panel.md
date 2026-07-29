# M11 — Manager Panel & Permissions

**Phase 7, Sprint 13** · Depends on: M03, M04, M09

## Purpose

The "Manager Panel — Work Manage करने के लिए मैनेजर की सुविधा" from the pamphlet: a separate,
department-scoped workspace for mid-level staff, plus the complete authorisation layer across the
whole application.

## Why a separate panel

Permission-gating half of `/admin` produces a confusing UI full of things a manager can see but not
click, and — more seriously — it leaks. Every new resource added to `/admin` is visible to managers
by default until someone remembers to gate it. That's a failure mode that gets worse over time.

A second Filament panel means the manager's navigation, dashboard, and default query scopes are
defined once, explicitly, in one place. Adding a resource to `/admin` never accidentally exposes it
at `/manager`.

## What a manager can do

| Capability | Scope |
|---|---|
| View members | Own department only, including sub-departments |
| Create / edit members | Own department only |
| Issue documents | To own-department members only |
| Revoke documents | Only ones they issued |
| Publish notices | To own department only |
| View donations | Read-only, all — no edit, no refund |
| View campaigns | Read-only |
| Reports | Own department only |
| Settings, users, roles, receipts, templates | **No access at all** |

The department comes from `departments.manager_user_id`. A manager can head more than one department.

## Scoping

Applied in two independent layers, because one is never enough.

**Layer 1 — query scope.** The Manager panel's resources override `getEloquentQuery()`.

**There is no single scope that works for every resource**, because only `members` carries a
`department_id`. Writing one generic `whereIn('department_id', …)` and applying it everywhere produces
an SQL error on two of the three scoped resources — and the fix a developer reaches for under time
pressure is deleting the scope, which reintroduces exactly the IDOR this section exists to prevent.

One scope per resource, each written against that table's real shape:

```php
// MemberResource — members.department_id exists.
return parent::getEloquentQuery()
    ->whereIn('department_id', auth()->user()->managedDepartmentIds());

// IssuedDocumentResource — issued_documents has member_id, NOT department_id.
return parent::getEloquentQuery()
    ->whereHas('member', fn (Builder $q) => $q
        ->whereIn('department_id', auth()->user()->managedDepartmentIds()));

// NoticeResource — notices has no department column at all; it has
// audience + audience_filter JSON. A manager sees notices they created,
// plus department-targeted notices aimed at a department they manage.
$ids = auth()->user()->managedDepartmentIds();

return parent::getEloquentQuery()
    ->where(fn (Builder $q) => $q
        ->where('created_by_user_id', auth()->id())
        ->orWhere(fn (Builder $q2) => $q2
            ->where('audience', NoticeAudience::Department)
            ->whereJsonOverlaps('audience_filter->department_ids', $ids)));
```

This controls what appears in lists. It is **not** access control on its own — that is Layer 2.

**Layer 2 — policy.** Every model has a policy checking department membership on `view`, `update`,
and `delete`:

```php
public function update(User $user, Member $member): bool
{
    if ($user->hasRole(['super-admin', 'admin'])) {
        return true;
    }

    return $user->hasRole('manager')
        && in_array($member->department_id, $user->managedDepartmentIds(), true);
}
```

**Both layers are required.** The query scope hides records from lists. The policy stops a manager
who types another department's record ID directly into the URL. Relying on the scope alone means an
IDOR vulnerability, and it's a trivially discoverable one — increment the ID and see what loads.

Write an automated test for exactly that attack: authenticate as a manager, request another
department's member by ID, assert 403.

`managedDepartmentIds()` resolves nested departments — heading "North Zone" includes "North Zone →
Delhi" and "North Zone → Punjab".

## Manager dashboard

Scoped widgets:
- Member count by status in their departments
- Documents issued this month
- Recent notices they published
- Members with expiring ID cards (next 30 days)
- Pending member approvals

No donation figures beyond an org-wide read-only view — managers manage people, not money.

## Permission management UI

For `super-admin` only: a role/permission matrix, per-user role assignment, and a per-user
permission override for exceptions. Every change is written to `activity_log`.

Changing a user's roles should force a session refresh so the new permissions apply immediately
rather than at next login.

## Policy coverage

Every model gets a policy. No exceptions — a resource without one is a resource whose access rules
live only in the navigation, which is not access control.

**People & documents** — `MemberPolicy` · `DepartmentPolicy` · `DesignationPolicy` ·
`IssuedDocumentPolicy` · `DocumentTemplatePolicy` · `UserPolicy`

**Money** — `DonationPolicy` · `DonorPolicy` · `SubscriptionPolicy` · `ReceiptPolicy`

**Campaigns** — `CampaignPolicy` · `CampaignUpdatePolicy` · `CampaignProductPolicy` ·
`CampaignStatPolicy` · `CampaignFaqPolicy` · `FundraiserRequestPolicy`

**Content & comms** — `PagePolicy` · `PostPolicy` · `BannerPolicy` · `TestimonialPolicy` ·
`PressMentionPolicy` · `ImpactStatPolicy` · `NoticePolicy` · `EmailTemplatePolicy` ·
`SubscriberPolicy` · `ContactMessagePolicy` · `RedirectPolicy`

**Configuration** — `SettingPolicy`

> The earlier version of this list named 17 policies and silently omitted every content model plus
> everything added after it was written. Grouping them by domain is not cosmetic — it makes an
> omission visible. When a model is added, its policy goes in this list in the same commit.

A registration test is cheaper than vigilance: iterate every class in `app/Models` and assert
`Gate::getPolicyFor($model)` is non-null. That test fails the moment someone adds a model without a
policy, which is the only reliable way to keep "no exceptions" true.

## Build checklist

- [ ] `ManagerPanelProvider` registered at `/manager`
- [ ] Panel access gated to the `manager` role
- [ ] `managedDepartmentIds()` on User, resolving nested departments
- [ ] Scoped `MemberResource`, `NoticeResource`, `IssuedDocumentResource` — **one scope per resource**,
      written against that table's real columns, not one generic `department_id` filter
- [ ] Read-only donation and campaign views
- [ ] `DepartmentOverview` dashboard widget
- [ ] Policies for **every** model listed above
- [ ] Test: every class in `app/Models` has a registered policy
- [ ] **Test: manager requesting another department's member by ID gets 403**
- [ ] Test: manager cannot reach `/admin`
- [ ] Test: manager cannot edit donations, settings, or receipts
- [ ] Permission matrix UI for `super-admin`
- [ ] Role changes force a session refresh
- [ ] Activity logging on all role and permission changes

## Edge cases

- **Manager of multiple departments** → union of all their department IDs.
- **Nested departments** → recursive resolution, cached per request (this query runs on every page).
- **Manager removed from a department** → immediate loss of access; kill their active sessions.
- **Member moved out of a manager's department** → the manager immediately loses access, including to
  documents they personally issued. Correct, and worth explaining in the UI.
- **Manager is also a member** → they can see their own record through the portal regardless of
  department.
- **Department with no manager** → members are visible only to admins. Flag it in the admin UI so it
  doesn't go unnoticed.
- **Manager tries to assign a member to a department they don't manage** → the department dropdown
  only offers their own departments, and the server validates it again on submit.
- **Circular department nesting** → validate on save; a cycle will hang `managedDepartmentIds()`.
- **Deleting a department with a manager and members** → block; require reassignment first.
