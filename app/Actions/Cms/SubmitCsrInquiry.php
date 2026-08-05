<?php

declare(strict_types=1);

namespace App\Actions\Cms;

use App\Mail\NewCsrInquiryMail;
use App\Models\CsrInquiry;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;

/**
 * `/csr-partnership` form submission. Honeypot and rate limiting happen at
 * the controller/route level — this action only persists and notifies.
 */
final class SubmitCsrInquiry
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array{organisation_name: string, contact_name: string, email: string, phone: string,
     *     message: string, ip_address?: string|null}  $attributes
     */
    public function handle(array $attributes): CsrInquiry
    {
        $inquiry = CsrInquiry::query()->forceCreate([
            'organisation_name' => $attributes['organisation_name'],
            'contact_name' => $attributes['contact_name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'message' => $attributes['message'],
            'ip_address' => $attributes['ip_address'] ?? null,
            'status' => 'new',
        ]);

        $adminEmail = $this->settings->get('org.email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new NewCsrInquiryMail($inquiry));
        }

        return $inquiry;
    }
}
