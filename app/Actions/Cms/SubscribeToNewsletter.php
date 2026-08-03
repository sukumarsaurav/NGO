<?php

declare(strict_types=1);

namespace App\Actions\Cms;

use App\Mail\NewsletterConfirmMail;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Homepage newsletter capture. Double opt-in: a fresh row (or a
 * previously-unconfirmed one) gets a confirmation email; an already
 * confirmed address just has `unsubscribed_at` cleared, no re-confirmation
 * needed. See docs/modules/M09-notices-communication.md's "Newsletter".
 */
final class SubscribeToNewsletter
{
    public function handle(string $email, ?string $name = null, ?string $source = null): Subscriber
    {
        $email = trim(mb_strtolower($email));

        $subscriber = Subscriber::query()->where('email', $email)->first();

        if ($subscriber) {
            $subscriber->update([
                'name' => $name ?: $subscriber->name,
                'unsubscribed_at' => null,
            ]);
            $subscriber = $subscriber->fresh();
        } else {
            $subscriber = Subscriber::query()->create([
                'email' => $email,
                'name' => $name,
                'token' => Str::random(40),
                'source' => $source,
            ]);
        }

        if (! $subscriber->confirmed_at) {
            Mail::to($subscriber->email)->queue(new NewsletterConfirmMail($subscriber));
        }

        return $subscriber;
    }
}
