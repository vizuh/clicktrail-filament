<?php

declare(strict_types=1);

namespace ClickTrail\Filament;

use Filament\Panel;
use Filament\PluginServiceProvider;

/**
 * Filament panel plugin. Register on a panel:
 *
 *   ->plugin(\ClickTrail\Filament\ClickTrailPlugin::make())
 *
 * Adds the ClickTrail settings page, the read-only attribution records
 * resource, and the diagnostics widget to the panel.
 */
final class ClickTrailPlugin extends PluginServiceProvider
{
    public static string $name = 'clicktrail';

    protected array $pages = [
        Pages\ClickTrailSettings::class,
    ];

    protected array $resources = [
        Resources\AttributionRecordResource::class,
    ];

    protected array $widgets = [
        Widgets\DiagnosticsWidget::class,
    ];

    public function getId(): string
    {
        return 'clicktrail';
    }

    public function register(Panel $panel): void
    {
        // Filament 3 auto-discovers $pages/$resources/$widgets from
        // PluginServiceProvider; nothing panel-specific to mutate yet.
    }
}
