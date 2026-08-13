<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Read/write access to runtime settings (§29).
 *
 * Every read is served from a single cached map rather than a query per key,
 * because these are consulted on hot paths like the upload form (§40). Any
 * write busts the cache.
 */
final class Settings
{
    private const CACHE_KEY = 'docflow.settings';

    /**
     * The settings an administrator may change, with their type and the
     * fallback used when nothing is stored yet.
     *
     * Defaults deliberately read from config, so an untouched installation
     * behaves exactly as the config files describe.
     *
     * @return array<string, array{type: string, default: mixed}>
     */
    public static function schema(): array
    {
        /*
        | Keys use underscores, not dots. A dotted key would be read as a
        | nested path by both Livewire's wire:model and Laravel's translator,
        | so `documents.max_size_kb` would silently become
        | ['documents']['max_size_kb'] instead of one setting.
        */
        return [
            'documents_max_size_kb' => ['type' => 'int', 'default' => (int) config('documents.max_size_kb')],
            'reviews_default_turnaround_days' => ['type' => 'int', 'default' => 5],
            'approvals_default_turnaround_days' => ['type' => 'int', 'default' => 3],
            'notifications_email_enabled' => ['type' => 'bool', 'default' => true],
            'documents_require_version_notes' => ['type' => 'bool', 'default' => false],
        ];
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        $schema = self::schema()[$key] ?? null;

        return self::all()[$key]
            ?? $fallback
            ?? $schema['default']
            ?? null;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()
                ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->typedValue()])
                ->all();
        });
    }

    /** Settings merged over their defaults — what the settings form binds to. */
    public static function withDefaults(): array
    {
        $stored = self::all();

        $resolved = [];

        foreach (self::schema() as $key => $definition) {
            $resolved[$key] = $stored[$key] ?? $definition['default'];
        }

        return $resolved;
    }

    public static function set(string $key, mixed $value): void
    {
        $type = self::schema()[$key]['type'] ?? 'string';

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value' => match ($type) {
                    'bool' => $value ? '1' : '0',
                    'array' => json_encode($value),
                    default => (string) $value,
                },
            ],
        );

        self::flush();
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            // Ignore anything not in the schema, so a crafted form post
            // cannot introduce arbitrary keys (§39).
            if (! array_key_exists($key, self::schema())) {
                continue;
            }

            self::set($key, $value);
        }
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
