<?php

namespace Devdojo\Foundation;

use Devdojo\Foundation\Commands\InstallCommand;
use Devdojo\Foundation\Http\Middleware\ViewFoundationSetup;
use Devdojo\Foundation\Livewire\Setup;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/foundation.php', 'foundation');

        // Overlay the per-app database overrides + resolved dependencies onto the
        // config so every feature package's boot() gate sees the effective state.
        //
        // This runs in an app "booting" callback rather than register() because
        // the database is not available during register() (the 'db' service may
        // not be bound yet). Booting callbacks fire at the start of the boot
        // phase — after the DB is ready, but before any provider's boot() — so
        // the feature gates read the correct, override-aware config.
        $this->app->booting(function () {
            config()->set('foundation.features', Foundation::features());
        });
    }

    public function boot(): void
    {
        Route::middlewareGroup('view-foundation-setup', [ViewFoundationSetup::class]);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'foundation');
        $this->loadRoutesFrom(__DIR__.'/../routes/foundation.php');

        // The onboarding/setup wizard rendered by the host app's welcome view.
        Livewire::component('foundation.setup', Setup::class);

        // The foundation_settings table is foundation infrastructure — always loaded
        // so the feature-flag store exists wherever the foundation is installed.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/foundation.php' => config_path('foundation.php'),
            ], 'foundation-config');
        }
    }
}
