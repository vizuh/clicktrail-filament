<?php

declare(strict_types=1);

namespace ClickTrail\Filament;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider. Loads package migrations; register the plugin
 * itself on a Filament panel via ->plugin(ClickTrailPlugin::make()).
 */
final class ClickTrailFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/clicktrail-filament.php', 'clicktrail-filament');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->publishes([
            __DIR__ . '/../config/clicktrail-filament.php' => config_path('clicktrail-filament.php'),
            __DIR__ . '/../migrations' => database_path('migrations'),
        ], 'clicktrail-filament');
    }
}
