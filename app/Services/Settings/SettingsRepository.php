<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Encryption\Encrypter;
use Spatie\Activitylog\ActivityLogger;

/**
 * DB-backed singleton config, cached forever under one key, cache busted on
 * write. See docs/modules/M02-organisation-settings.md.
 *
 * The whole table is loaded once and cached — settings cost one query per
 * deploy, not one per read, because org.name is read a dozen times on every
 * page render.
 *
 * Constructor-injected per docs/05-CONVENTIONS.md ("no facades inside Actions/
 * Services — DI only"), not Cache::/Crypt:: facades.
 */
final class SettingsRepository
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, mixed>|null In-memory copy for the life of this instance — see AppServiceProvider's singleton binding. */
    private ?array $memoized = null;

    public function __construct(
        private readonly Cache $cache,
        private readonly Encrypter $encrypter,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $setting = Setting::query()->where('key', $key)->first();

        if (! $setting) {
            throw new SettingKeyNotSeededException($key);
        }

        $old = $this->castValue($setting->getRawOriginal('value'), $setting->type, $setting->is_encrypted);

        $setting->value = $this->prepareRawValue($value, $setting->type, $setting->is_encrypted);
        $setting->save();

        $this->flush();

        // CauserResolver resolves the authenticated user automatically —
        // see spatie/laravel-activitylog's ActivityLogger::getActivity().
        $this->activityLogger
            ->useLog('settings')
            ->withProperties(['key' => $key, 'old' => $old, 'new' => $value])
            ->log("Setting '{$key}' updated");
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->memoized ??= $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()
                ->get()
                ->mapWithKeys(fn (Setting $setting) => [
                    $setting->key => $this->castValue($setting->getRawOriginal('value'), $setting->type, $setting->is_encrypted),
                ])
                ->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $keysInGroup = Setting::query()->where('group', $group)->pluck('key');

        return collect($this->all())
            ->only($keysInGroup)
            ->all();
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        $this->memoized = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    private function castValue(?string $raw, SettingType $type, bool $isEncrypted): mixed
    {
        if ($raw === null) {
            return null;
        }

        if ($isEncrypted) {
            $raw = $this->encrypter->decryptString($raw);
        }

        return match ($type) {
            SettingType::Int => (int) $raw,
            SettingType::Bool => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            SettingType::Json => json_decode($raw, true),
            SettingType::String, SettingType::File => $raw,
        };
    }

    private function prepareRawValue(mixed $value, SettingType $type, bool $isEncrypted): ?string
    {
        $raw = match ($type) {
            SettingType::Json => json_encode($value),
            SettingType::Bool => $value ? '1' : '0',
            default => $value === null ? null : (string) $value,
        };

        if ($raw !== null && $isEncrypted) {
            $raw = $this->encrypter->encryptString($raw);
        }

        return $raw;
    }
}
