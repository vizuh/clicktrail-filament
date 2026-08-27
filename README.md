[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**vizuh/filament-clicktrail**

ClickTrail attribution inside your Filament 3 panel — settings, read-only attribution records, suppression diagnostics, and event mapping. Nothing to build by hand.

</div>

[![CI](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Index

- [Why](#why)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Event mapping](#event-mapping)
- [Attribution records](#attribution-records)
- [Settings and configuration](#settings-and-configuration)
- [Diagnostics](#diagnostics)
- [Consent contract](#consent-contract)
- [How it differs](#how-it-differs)
- [Testing](#testing)
- [License](#license)

## Why

Attribution data nobody can see is attribution nobody trusts. This plugin surfaces ClickTrail\'s stored first/last-touch records, consent snapshots, and suppression diagnostics directly inside an existing Filament panel — strictly read-only, because attribution state belongs to the capture pipeline, never to hand edits.

Requires PHP >= 8.1, Laravel 12.60+ or 13.10+, Filament 3.3.55+, and [`clicktrail/php-sdk`](https://github.com/vizuh/clicktrail-php).

## Installation

```bash
composer require vizuh/filament-clicktrail
php artisan vendor:publish --tag=clicktrail-filament
php artisan migrate
```

## Quick start

Register the plugin on any panel:

```php
use ClickTrail\Filament\ClickTrailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(ClickTrailPlugin::make());
}
// The panel now shows a "ClickTrail" navigation group: the Settings page,
// the read-only Attribution Records table (auto-refreshing every 60s), and
// the diagnostics stats widget. No further wiring.
```

## Event mapping

`Support\EventMap` maps Eloquent model events onto canonical `Stable::EVENT_*` names, so your lead and order models speak ClickTrail\'s vocabulary without glue code:

```php
use App\Models\Lead;
use ClickTrail\Filament\Support\EventMap;

EventMap::resolve(new Lead(), 'created');  // 'lead_created' — canonical event name
EventMap::resolve(new Lead(), 'deleted');  // 'refund' — deletion maps to refund
```

Model basenames map by default (`lead`, `appointment`, `sale`, `order`). Extend or override per model through the `clicktrail-filament.event_map` config key; a basename ending in `refund` resolves to `refund`, and events containing `attended` resolve to `booking_completed`.

## Attribution records

The `AttributionRecordResource` table shows stored first/last-touch records with channel filters and a compact consent-snapshot column:

```php
TextColumn::make('first_channel')->badge();          // paid_search | organic_search | ...
SelectFilter::make('first_channel');                 // filter by canonical channel name
TextColumn::make('consent_snapshot_summary');        // "analytics_storage=granted, ad_user_data=denied"
```

There are no create/edit/delete pages and no routes to them: `canCreate()`, `canEdit()`, `canDelete()` all return false. The table polls every 60 seconds.

## Settings and configuration

The settings page edits the published `clicktrail-filament.php` config:

```php
'site_id'           => env('CLICKTRAIL_SITE_ID', ''),   // issued by the collector
'endpoint'          => env('CLICKTRAIL_ENDPOINT', 'https://collect.clicktrail.dev/v1/events/batch'),
'consent_resolver'  => env('CLICKTRAIL_CONSENT_RESOLVER', ''), // empty => NullConsentResolver
'capability_gates'  => ['analytics' => true, 'advertising' => true, 'ad_user_data' => true],
```

A capability gate that is off means that use does not require CMP consent (gate-toggle semantics). Note: settings save currently writes back to the config repository at runtime; durable persistence lands with the settings-storage work in progress.

## Diagnostics

The stats widget reads `clicktrail_diagnostics` counters — one stat per suppression reason, colored warning when nonzero:

```php
Stat::make('adUserDataUnknownAtCapture', '12') // count of suppressed deliveries for this reason
    ->description('Last seen 2 hours ago')
```

With no suppressions recorded you get a green `Suppressions = 0` stat. Queue depth shows `-` until the queued-delivery job ships (deferred pending live verification).

## Consent contract

This plugin is a consent consumer, not a CMP. It reads a normalized `ConsentSnapshot` (granted / denied / unknown / not_applicable) through the Laravel adapter\'s `ConsentResolverInterface`. Every unresolved signal counts as **denied**: suppressed deliveries become diagnostics rows instead of being sent.

```php
if (! ConsentBehavior::can($snapshot, 'ad_user_data')) {
    // delivery suppressed; reason recorded in clicktrail_diagnostics,
    // visible in the Diagnostics widget immediately
}
```

## How it differs

| | This plugin | Generic admin CRUD |
|---|---|---|
| Attribution records | Read-only display of pipeline-owned state | Editable rows invite silent data corruption |
| Consent | Unknown = denied, enforced upstream of delivery | Often a display-only flag |
| Events | Canonical `Stable::EVENT_*` names shared with every ClickTrail adapter | Per-project invented event names |

It does not capture touches or deliver batches itself — the Laravel adapter pipeline owns that; this plugin makes its results visible and auditable.

## Testing

No PHPUnit suite ships yet. CI lints every PHP file on PHP 8.1–8.3:

```bash
composer install --prefer-dist --no-interaction || echo "no deps"
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l   # exits clean on success
```

## License

MIT.
