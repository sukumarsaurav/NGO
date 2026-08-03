<?php

declare(strict_types=1);

use App\Mail\MonthlySummaryMail;
use App\Models\Donation;
use App\Models\User;
use App\Services\Reports\MonthlySummaryBuilder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('builds a summary for last month matching a raw DB total', function () {
    $lastMonth = now()->subMonthNoOverflow();

    Donation::factory()->create([
        'status' => 'succeeded',
        'donated_at' => $lastMonth->copy()->startOfMonth()->addDays(2),
        'amount' => 250000,
    ]);

    $summary = app(MonthlySummaryBuilder::class)->build();

    expect($summary['total'])->toBe(250000)
        ->and($summary['month'])->toBe($lastMonth->format('F Y'));
});

it('queues the monthly summary email to every admin and super-admin', function () {
    Mail::fake();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->artisan('reports:monthly-summary')->assertSuccessful();

    Mail::assertQueued(MonthlySummaryMail::class, fn ($mail) => $mail->hasTo($superAdmin->email));
    Mail::assertQueued(MonthlySummaryMail::class, fn ($mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(MonthlySummaryMail::class, fn ($mail) => $mail->hasTo($manager->email));
});
