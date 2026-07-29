<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Model;

/**
 * The raw row. All reads and writes go through App\Services\Settings\SettingsRepository
 * (cache + casting + encryption + activity logging) — never query this model directly
 * outside that service. See docs/modules/M02-organisation-settings.md.
 *
 * @property SettingType $type Larastan doesn't yet infer enum casts declared via
 *                             the `casts()` method (rather than the legacy `$casts` property), so this is
 *                             declared explicitly. Verified correct at runtime — see SettingsRepositoryTest.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'is_encrypted'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_encrypted' => 'boolean',
        ];
    }
}
