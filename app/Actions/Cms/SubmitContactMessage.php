<?php

declare(strict_types=1);

namespace App\Actions\Cms;

use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;

/**
 * `/contact` form submission. Honeypot and rate limiting happen at the
 * controller/route level — this action only persists and notifies. See
 * docs/modules/M10-public-site-cms.md's "Contact messages".
 */
final class SubmitContactMessage
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, subject?: string|null,
     *     message: string, ip_address?: string|null, user_agent?: string|null}  $attributes
     */
    public function handle(array $attributes): ContactMessage
    {
        $message = ContactMessage::query()->forceCreate([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'] ?? null,
            'subject' => $attributes['subject'] ?? null,
            'message' => $attributes['message'],
            'ip_address' => $attributes['ip_address'] ?? null,
            'user_agent' => $attributes['user_agent'] ?? null,
            'is_read' => false,
        ]);

        $adminEmail = $this->settings->get('org.email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new NewContactMessageMail($message));
        }

        return $message;
    }
}
