<?php

declare(strict_types=1);

use App\Models\MemberCodeSequence;
use App\Services\Numbering\MemberCodeGenerator;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->generator = app(MemberCodeGenerator::class);
});

it('produces the documented format on the very first call — the cold-start case', function () {
    expect(MemberCodeSequence::query()->count())->toBe(0);

    $code = $this->generator->next();

    expect($code)->toMatch('/^VGWGF-\d{4}-\d{5}$/')
        ->and($code)->toBe('VGWGF-'.now()->year.'-00001');
});

it('increments sequentially and never repeats', function () {
    $codes = collect(range(1, 20))->map(fn () => $this->generator->next());

    expect($codes->unique())->toHaveCount(20);

    $numbers = $codes->map(fn (string $code) => (int) substr($code, -5));
    expect($numbers->all())->toBe(range(1, 20));
});

it('uses the configured prefix from settings, not a hard-coded one', function () {
    app(SettingsRepository::class)->set('org.member_code_prefix', 'TESTORG');

    expect($this->generator->next())->toStartWith('TESTORG-');
});

it('does not collide when the sequence row already exists from a prior call', function () {
    $this->generator->next();
    $second = $this->generator->next();

    expect($second)->toEndWith('-00002');
});
