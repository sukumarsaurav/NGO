<?php

declare(strict_types=1);

namespace App\Services\Settings;

use RuntimeException;

/**
 * Settings are never created ad hoc by a write — every key must exist first,
 * via SettingsSeeder. This is what stops a typo'd key from silently creating a
 * new, unseeded setting instead of failing loudly.
 */
final class SettingKeyNotSeededException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Setting key '{$key}' has not been seeded. Add it to SettingsSeeder first.");
    }
}
