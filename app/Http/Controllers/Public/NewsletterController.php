<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Cms\SubscribeToNewsletter;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Homepage section 11 ("Newsletter — email capture") plus the double
 * opt-in confirm/unsubscribe links that go out in NewsletterConfirmMail and
 * every non-transactional email's footer. See
 * docs/modules/M09-notices-communication.md's "Newsletter".
 */
class NewsletterController extends Controller
{
    public function store(Request $request, SubscribeToNewsletter $subscribe): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $subscribe->handle($data['email'], null, 'homepage');

        return back()->with('status', "Thanks for subscribing — you'll hear from us soon.");
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();

        if (! $subscriber->confirmed_at) {
            $subscriber->update(['confirmed_at' => now()]);
        }

        return redirect()->route('home')->with('status', "You're confirmed — thanks for subscribing.");
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();

        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect()->route('home')->with('status', "You've been unsubscribed.");
    }
}
