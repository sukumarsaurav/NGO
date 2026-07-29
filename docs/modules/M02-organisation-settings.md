# M02 — Organisation Settings

**Phase 1, Sprint 2** · Depends on: M01

## Purpose

A single, cached, admin-editable source of truth for everything about the NGO. Nothing about the
organisation is ever hard-coded — not in `config/`, not in Blade, not in a PDF template.

This is the module that makes the whole system reusable for a second NGO later, and it costs almost
nothing to do properly on day one.

## Why DB-backed instead of `config/`

Config files require a developer and a deploy to change. The NGO's 80G registration expires and gets
renewed with a new validity period. Their address changes. Their authorised signatory retires. Every
one of those facts appears on a legal PDF. The client must be able to change them without calling you.

## Data

`settings` table — see `02-DATABASE-SCHEMA.md` §2. Key/value with a `type` for casting, a `group`
for UI organisation, and an `is_encrypted` flag.

### Seeded keys

**Group `organisation`**

| Key | Type | Notes |
|---|---|---|
| `org.name` | string | Display name, used everywhere |
| `org.legal_name` | string | Full registered name for legal documents |
| `org.tagline` | string | |
| `org.logo` | file | |
| `org.logo_dark` | file | |
| `org.favicon` | file | |
| `org.address_line1` | string | |
| `org.address_line2` | string | |
| `org.city`, `org.state`, `org.pincode` | string | |
| `org.email`, `org.phone`, `org.whatsapp` | string | |
| `org.registration_number` | string | Society / Trust / Sec 8 registration |
| `org.registration_date` | string | |
| `org.pan` | string | **encrypted** |
| `org.12a_number` | string | |
| `org.80g_number` | string | Appears on every 80G receipt |
| `org.80g_valid_from` | string | |
| `org.80g_valid_to` | string | Used to block 80G issuance after expiry |
| `org.csr_number` | string | CSR-1 registration, if applicable |
| `org.authorised_signatory_name` | string | Printed on receipts |
| `org.authorised_signatory_designation` | string | |
| `org.signature_image` | file | |
| `org.seal_image` | file | |

**Group `donation`**

`donation.min_amount` (int, paise) · `donation.preset_amounts` (json, e.g. `[50000, 100000, 250000,
500000]`) · `donation.currency` · `donation.allow_anonymous` (bool) · `donation.require_pan_above`
(int, paise) · `donation.cash_80g_limit` (int, default 200000 = ₹2,000)

**Group `receipt`**

`receipt.prefix` (e.g. `VGWGF/RCP/`) · `receipt.80g_prefix` (e.g. `VGWGF/80G/`) ·
`receipt.number_padding` (int, default 5) · `receipt.footer_note` (text) ·
`receipt.auto_email` (bool)

**Group `social`**

`social.facebook`, `social.instagram`, `social.twitter`, `social.linkedin`, `social.youtube`

**Group `homepage`**

Backs homepage sections 6–8 (see `M10` and `06-UI-UX-FOUNDATION.md` §7). Using the settings
singleton here rather than a generic page builder is deliberate — this copy changes twice a year.

`homepage.serve_heading` · `homepage.serve_body` (text) · `homepage.serve_image` (file) ·
`homepage.monthly_heading` · `homepage.monthly_body` · `homepage.steps` (json — four
`{title, body, icon}` objects) · `homepage.newsletter_heading`

**Group `seo`**

`seo.meta_title`, `seo.meta_description`, `seo.og_image`, `seo.google_analytics_id`,
`seo.google_site_verification`, `seo.facebook_pixel_id`

## SettingsRepository

```php
final class SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function all(): array;
    public function group(string $group): array;
    public function forget(string $key): void;
    public function flush(): void;
}
```

Backed by a `Settings` facade so Blade can do `{{ Settings::get('org.name') }}`.

**Caching:** the whole table is loaded once and cached forever under a single key. Any write flushes
the cache. This means settings cost one query per deploy, not one per read — important, because
`org.name` is read a dozen times on every page render.

**Casting** is driven by the `type` column: `int` → int, `bool` → bool, `json` → array,
`file` → the storage path, `string` → string. Encrypted values are decrypted transparently on read.

## UI

A single Filament page (`OrganisationSettings`) with tabs matching the groups. Not a resource —
there's only ever one settings record set, so a resource's list view would be nonsense.

Fields:
- Text, textarea, and file-upload inputs per key
- The 80G section shows a **live warning** when `org.80g_valid_to` is within 60 days, and a **hard
  error banner** once it has passed
- PAN field is masked in the UI (`XXXXX1234F`) with a "reveal" toggle gated on `super-admin`
- A "preview receipt header" button rendering how the org block will look on a PDF

Only `super-admin` and users with `manage_settings` can access it. Every change is written to
`activity_log` with old and new values.

## Storage namespacing

All uploads go to `storage/app/public/org/{slug}/...` where `{slug}` derives from `org.name`. This
costs nothing now and means a future multi-tenant migration doesn't need to move any files.

## Build checklist

- [ ] `settings` migration with `key`, `value`, `type`, `group`, `is_encrypted`
- [ ] `SettingsRepository` with forever-cache and flush-on-write
- [ ] `Settings` facade registered
- [ ] `SettingsSeeder` with every key above. Real values where known — `org.name`
      "Vision Good Work Global Foundation", `org.email` `info@visiongoodworkglobalfoundation.org`,
      `receipt.prefix` `VGWGF/RCP/`, `receipt.80g_prefix` `VGWGF/80G/`. Placeholders only for what the
      client still has to supply (PAN, 80G number and validity, registration numbers, signatory).
- [ ] `OrganisationSettings` Filament page, tabbed by group
- [ ] File uploads namespaced to `org/{slug}/`
- [ ] PAN encrypted at rest, masked in UI
- [ ] 80G expiry warning (60 days) and error (expired) banners
- [ ] Activity logging on every write
- [ ] Blade helper tested: `{{ Settings::get('org.name') }}`
- [ ] Verified with Debugbar: settings reads produce zero queries after warm cache

## Edge cases

- **80G expired.** `Generate80GReceipt` must refuse to run and surface a clear message. Silently
  issuing invalid 80G receipts is a compliance failure that surfaces a year later at audit time.
- **Missing required setting.** `org.pan` empty when generating an 80G receipt → fail loudly with a
  named error, never render a receipt with a blank field.
- **Cache staleness across processes.** The queue worker is a separate process. Flushing the cache
  on write must use a shared cache store (database or Redis), not the array driver.
- **File deleted from storage but path still in settings** → the renderer should fall back to a
  placeholder, not fatal.
