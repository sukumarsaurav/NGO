<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Settings\SettingKeyNotSeededException;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->settings = app(SettingsRepository::class);
});

it('reads a seeded string value', function () {
    expect($this->settings->get('org.name'))->toBe('Vision Good Work Global Foundation');
});

it('returns the default when a key has a null value', function () {
    expect($this->settings->get('org.tagline', 'fallback'))->toBe('fallback');
});

it('casts an int setting to a real int, not a string', function () {
    expect($this->settings->get('receipt.number_padding'))->toBeInt()->toBe(5);
});

it('casts a bool setting to a real bool', function () {
    expect($this->settings->get('donation.allow_anonymous'))->toBeBool()->toBeTrue();
});

it('casts a json setting to an array', function () {
    expect($this->settings->get('donation.preset_amounts'))
        ->toBeArray()
        ->toBe([50000, 100000, 250000, 500000]);
});

it('updates a value and every subsequent read reflects it immediately', function () {
    $this->settings->set('org.name', 'Updated Name');

    expect($this->settings->get('org.name'))->toBe('Updated Name');

    // A second, independently-resolved instance must see it too — proves the
    // change isn't just sitting in this instance's in-memory memoization.
    expect(app(SettingsRepository::class)->get('org.name'))->toBe('Updated Name');
});

it('refuses to write a key that was never seeded', function () {
    $this->settings->set('org.made_up_key', 'value');
})->throws(SettingKeyNotSeededException::class);

it('encrypts an encrypted-flagged setting at rest but returns plaintext on read', function () {
    $this->settings->set('org.pan', 'ABCDE1234F');

    expect($this->settings->get('org.pan'))->toBe('ABCDE1234F');

    $raw = Setting::query()->where('key', 'org.pan')->value('value');

    // Laravel's encrypter payload is base64(json({iv, value, mac, tag})) —
    // confirm it's genuinely that shape, not just "different from plaintext".
    expect($raw)->not->toBe('ABCDE1234F');
    expect(base64_decode($raw, strict: true))->toContain('"iv":')->toContain('"mac":');
});

it('writes an entry to activity_log with the old and new value', function () {
    $this->settings->set('org.tagline', 'A new tagline');

    $activity = Activity::query()->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('settings')
        ->and($activity->properties['key'])->toBe('org.tagline')
        ->and($activity->properties['old'])->toBeNull()
        ->and($activity->properties['new'])->toBe('A new tagline');
});

it('filters settings by group', function () {
    $donationSettings = $this->settings->group('donation');

    expect($donationSettings)->toHaveKeys([
        'donation.min_amount', 'donation.preset_amounts', 'donation.currency',
        'donation.allow_anonymous', 'donation.require_pan_above', 'donation.cash_80g_limit',
    ])->not->toHaveKey('org.name');
});

it('serves reads from cache, not repeated settings-table queries, once warm', function () {
    $this->settings->get('org.name'); // warm the cache

    DB::enableQueryLog();

    for ($i = 0; $i < 10; $i++) {
        $this->settings->get('org.name');
        $this->settings->get('donation.currency');
    }

    $settingsTableQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'select * from "settings"')
            || str_contains($query['query'], 'select * from `settings`'))
        ->count();

    DB::disableQueryLog();

    expect($settingsTableQueries)->toBe(0);
});

it('forgetting a key removes it and busts the cache', function () {
    $this->settings->get('org.tagline'); // warm

    $this->settings->forget('org.tagline');

    expect(Setting::query()->where('key', 'org.tagline')->exists())->toBeFalse()
        ->and($this->settings->get('org.tagline', 'gone'))->toBe('gone');
});
