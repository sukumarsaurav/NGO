<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Settings\SettingsRepository;

/**
 * Which ways a donor can actually pay right now — one answer for the form, the pages and
 * the footer, so a payment method is never advertised that cannot complete.
 *
 * Razorpay: in production it needs real keys. Without them PaymentServiceProvider still
 * constructs the real gateway and lets it fail loudly, so offering the card/netbanking
 * flow would put a donor through a form that errors at the last step. Outside production
 * the provider falls back to FakeGateway, so it stays on for local work and the test
 * suite — the same condition the provider itself uses.
 *
 * Direct UPI: available whenever a UPI ID is set (Organisation Settings → Donations).
 */
final class PaymentMethods
{
    public static function razorpayEnabled(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        return filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret'));
    }

    public static function upiEnabled(): bool
    {
        return filled(self::upiId());
    }

    public static function anyEnabled(): bool
    {
        return self::razorpayEnabled() || self::upiEnabled();
    }

    public static function upiId(): ?string
    {
        $id = app(SettingsRepository::class)->get('donation.upi_id');

        return filled($id) ? trim((string) $id) : null;
    }

    /**
     * The name UPI apps show as the payee. Matches what the bank has registered, so a donor
     * sees the same name on the payment screen as on the bank's own printed QR.
     */
    public static function upiPayeeName(): string
    {
        $settings = app(SettingsRepository::class);

        return (string) ($settings->get('org.legal_name') ?: $settings->get('org.name') ?: config('app.name'));
    }

    /**
     * A `upi://pay` deep link. On a phone it opens the donor's UPI apps with payee, amount
     * and note already filled; elsewhere it is encoded into the QR.
     *
     * Deliberately only the parameters the bank's own QR uses plus amount and note: `pa`
     * (payee), `pn` (name), `am` (amount), `cu` (currency), `tn` (note). No `tr` or `mc` —
     * those make some apps refuse a payment to a merchant ID they cannot verify.
     *
     * `rawurlencode`, not `urlencode`: UPI apps do not decode `+` as a space.
     */
    public static function upiLink(Money $amount, string $note): ?string
    {
        $vpa = self::upiId();

        if ($vpa === null) {
            return null;
        }

        $params = [
            'pa' => $vpa,
            'pn' => self::upiPayeeName(),
            'am' => number_format($amount->toPaise() / 100, 2, '.', ''),
            'cu' => 'INR',
            'tn' => $note,
        ];

        // `@` stays literal in the payee address, exactly as in the bank's own QR; encoding it
        // as %40 is valid but not every UPI app decodes it.
        return 'upi://pay?'.implode('&', array_map(
            fn (string $key, string $value) => $key.'='.str_replace('%40', '@', rawurlencode($value)),
            array_keys($params),
            $params,
        ));
    }
}
