<?php

namespace Devdojo\Foundation\Commands;

use Devdojo\Foundation\Models\FoundationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'foundation:install {--force : Overwrite existing published files}';

    protected $description = 'Install the DevDojo Foundation: publish config, migrate, and seed feature flags.';

    public function handle(): int
    {
        $this->info('Installing the DevDojo Foundation...');

        // 1. Publish the foundation config.
        $this->call('vendor:publish', [
            '--tag' => 'foundation-config',
            '--force' => (bool) $this->option('force'),
        ]);

        // 2. Run migrations for the full stack (foundation + every feature package).
        $this->call('migrate', ['--force' => true]);

        // 3. Seed the settings table with the default feature flags.
        $this->seedFeatureFlags();

        // 4. Link storage (best-effort).
        $this->callSilent('storage:link');

        // 5. Next steps.
        $this->newLine();
        $this->info('✔ DevDojo Foundation installed.');
        $this->line('  Visit <comment>/foundation/setup</comment> to choose which features are active.');

        return self::SUCCESS;
    }

    protected function seedFeatureFlags(): void
    {
        if (! Schema::hasTable('foundation_settings')) {
            $this->warn('foundation_settings table not found — skipping flag seed.');

            return;
        }

        foreach (config('foundation.features', []) as $feature => $enabled) {
            FoundationSetting::firstOrCreate(
                ['key' => 'features.'.$feature],
                ['value' => $enabled ? '1' : '0'],
            );
        }

        $this->line('  Seeded default feature flags.');
    }
}
