<?php

namespace Devdojo\Foundation;

use Devdojo\Foundation\Models\FoundationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Foundation
{
    /**
     * The fully-resolved feature map: config defaults, overlaid with the
     * per-app database overrides, then expanded so every enabled feature's
     * dependencies are also enabled.
     *
     * @return array<string, bool>
     */
    public static function features(): array
    {
        $features = array_map(
            fn ($value) => (bool) $value,
            config('foundation.features', [])
        );

        foreach (static::storedOverrides() as $feature => $value) {
            if (array_key_exists($feature, $features)) {
                $features[$feature] = $value;
            }
        }

        return static::resolveDependencies($features);
    }

    /**
     * Whether a single feature is enabled (dependencies considered).
     */
    public static function enabled(string $feature): bool
    {
        return static::features()[$feature] ?? false;
    }

    /**
     * Persist a feature flag override to the database.
     */
    public static function setFeature(string $feature, bool $enabled): void
    {
        FoundationSetting::updateOrCreate(
            ['key' => 'features.'.$feature],
            ['value' => $enabled ? '1' : '0'],
        );
    }

    /**
     * Read feature overrides from the foundation_settings table.
     * Guarded so it is safe before the table exists (install / discovery).
     *
     * @return array<string, bool>
     */
    protected static function storedOverrides(): array
    {
        $overrides = [];

        try {
            if (Schema::hasTable('foundation_settings')) {
                // Use the query builder (not Eloquent) so this works during the
                // early boot phase, before Eloquent's connection resolver is set.
                $rows = DB::table('foundation_settings')
                    ->where('key', 'like', 'features.%')
                    ->pluck('value', 'key');

                foreach ($rows as $key => $value) {
                    $overrides[substr($key, strlen('features.'))] = (bool) $value;
                }
            }
        } catch (Throwable $e) {
            // Database unavailable — fall back to config defaults.
        }

        return $overrides;
    }

    /**
     * Force-enable the prerequisites of any enabled feature.
     *
     * @param  array<string, bool>  $features
     * @return array<string, bool>
     */
    protected static function resolveDependencies(array $features): array
    {
        $depends = config('foundation.depends', []);

        foreach ($features as $feature => $enabled) {
            if (! $enabled) {
                continue;
            }

            foreach ($depends[$feature] ?? [] as $prerequisite) {
                $features[$prerequisite] = true;
            }
        }

        return $features;
    }
}
