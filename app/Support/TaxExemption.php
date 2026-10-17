<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Settings\SettingsRepository;

/**
 * Whether the organisation can truthfully tell donors that gifts are tax-deductible.
 *
 * One answer, derived from the registration numbers an admin has actually entered, so the
 * claims on the public site ("12A & 80G Registered", "instant 80G receipt", "Tax Benefit")
 * and the machinery that issues 80G receipts can never disagree with each other.
 *
 * Section 8 incorporation does NOT confer 12A or 80G — those are separate Income Tax
 * registrations applied for after incorporation. Until the numbers are entered under
 * Organisation Settings, every tax claim hides itself; once they are, they return with no
 * code change. A claim a donor relies on to reduce their tax bill must not be made before
 * the registration exists.
 */
final class TaxExemption
{
    public static function is12aRegistered(): bool
    {
        return filled(app(SettingsRepository::class)->get('org.12a_number'));
    }

    /**
     * 80G is active only with a registration number AND an unexpired validity window.
     * A number alone is not enough: the registration lapses, and a receipt issued after
     * `org.80g_valid_to` is invalid.
     */
    public static function is80gActive(): bool
    {
        $settings = app(SettingsRepository::class);

        if (blank($settings->get('org.80g_number'))) {
            return false;
        }

        $validTo = $settings->get('org.80g_valid_to');

        return blank($validTo) || now()->toDateString() <= $validTo;
    }
}
