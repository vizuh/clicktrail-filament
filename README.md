# vizuh/filament-clicktrail

ClickTrail settings, diagnostics, event mapping, and attribution dashboard for Laravel applications.

Part of [ClickTrail](https://github.com/vizuh) deterministic first-party attribution.

## What it provides

- **Settings page** (`ClickTrailSettings`): site ID, collector endpoint, consent resolver class-string, and capability gates (analytics / advertising / ad_user_data), mirroring the ClickTrail consent contract — **unknown = denied**.
- **Attribution Records resource** (`AttributionRecordResource`): strictly read-only table of stored first/last-touch attribution records with channel filters and consent-snapshot summaries.
- **Diagnostics widget** (`DiagnosticsWidget`): suppression-reason counters from `clicktrail_diagnostics`; delivery queue depth is a placeholder pending live verification.
- **Event map** (`Support\EventMap`): maps Eloquent/Filament form events onto canonical `ClickTrail\Conventions\Stable::EVENT_*` names.

## Requirements

- PHP >= 8.1
- Laravel 10 or 11
- Filament 3
- clicktrail/php-sdk (dev-main)

## Install

```bash
composer require vizuh/filament-clicktrail
php artisan vendor:publish --tag=clicktrail-filament
php artisan migrate
```

Register the plugin on a panel:

```php
use ClickTrail\Filament\ClickTrailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ClickTrailPlugin::make());
}
```

## Consent contract

This plugin is a consent consumer, not a CMP. It reads a normalized
`ConsentSnapshot` (granted / denied / unknown / not_applicable) through the
Laravel adapter's `ConsentResolverInterface`. Every unresolved signal counts
as **denied**: suppressed deliveries are recorded as diagnostics instead of
being sent.

## License

MIT
