<?php

declare(strict_types=1);

namespace App\Actions\Cms;

use App\Mail\NewInternshipApplicationMail;
use App\Models\InternshipApplication;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;

/**
 * `/internship` form submission. Honeypot and rate limiting happen at the
 * controller/route level — this action only persists and notifies.
 */
final class SubmitInternshipApplication
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array{name: string, email: string, phone: string, track?: string|null,
     *     message?: string|null, resume_path?: string|null, ip_address?: string|null}  $attributes
     */
    public function handle(array $attributes): InternshipApplication
    {
        $application = InternshipApplication::query()->forceCreate([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'track' => $attributes['track'] ?? null,
            'message' => $attributes['message'] ?? null,
            'resume_path' => $attributes['resume_path'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? null,
            'status' => 'new',
        ]);

        $adminEmail = $this->settings->get('org.email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new NewInternshipApplicationMail($application));
        }

        return $application;
    }
}
