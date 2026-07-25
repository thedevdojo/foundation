<?php

namespace Devdojo\Foundation;

use Devdojo\Foundation\Commands\InstallCommand;
use Devdojo\Foundation\Http\Middleware\ViewFoundationSetup;
use Devdojo\Foundation\Livewire\PruneObjectHtmlAttributes;
use Devdojo\Foundation\Livewire\Setup;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Volt\FragmentAlias;

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
        $this->configureVoltForSymlinkedPackages();

        Route::middlewareGroup('view-foundation-setup', [ViewFoundationSetup::class]);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'foundation');
        $this->loadRoutesFrom(__DIR__.'/../routes/foundation.php');

        // The onboarding/setup wizard rendered by the host app's welcome view.
        Livewire::component('foundation.setup', Setup::class);

        // Registered after the framework has fully booted so the listener runs
        // after Livewire's own component hooks — see the class for why this exists.
        $this->app->booted(function () {
            \Livewire\on('dehydrate', new PruneObjectHtmlAttributes);
        });

        // The foundation_settings table is foundation infrastructure — always loaded
        // so the feature-flag store exists wherever the foundation is installed.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/foundation.php' => config_path('foundation.php'),
            ], 'foundation-config');

            $this->publishes([
                __DIR__.'/../resources/images' => public_path('vendor/foundation/images'),
            ], 'foundation-assets');
        }
    }

    /**
     * Make Volt fragment aliases survive symlinked path-repo installs.
     *
     * Folio resolves mounted page paths with realpath(), so when the foundation
     * packages are symlinked from outside the app (composer path repositories in
     * local development), page paths live outside base_path(). Volt encodes
     * fragment aliases relative to base_path(), which round-trips those pages to
     * a nonexistent path ("Path must not be empty"). Using the filesystem root
     * as the alias base keeps the encoded paths absolute and valid. Real vendor
     * installs live inside base_path(), so production behavior is unchanged.
     */
    private function configureVoltForSymlinkedPackages(): void
    {
        if (class_exists(FragmentAlias::class)
            && ! str_starts_with(dirname(__DIR__), base_path().DIRECTORY_SEPARATOR)) {
            FragmentAlias::useBasePath('/');
        }
    }
}
