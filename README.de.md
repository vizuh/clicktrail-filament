[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**vizuh/filament-clicktrail**

Filament-3-Oberflächen für ClickTrail-Einstellungen, schreibgeschützte
Attributionsdatensätze, Unterdrückungsdiagnostik und Event-Mapping.

</div>

[![CI](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Index

- [Warum](#warum)
- [Installation](#installation)
- [Schnellstart](#schnellstart)
- [Event-Mapping](#event-mapping)
- [Attributionsdatensätze](#attributionsdatensätze)
- [Einstellungen und Konfiguration](#einstellungen-und-konfiguration)
- [Diagnostik](#diagnostik)
- [Consent-Vertrag](#consent-vertrag)
- [Worin es sich unterscheidet](#worin-es-sich-unterscheidet)
- [Tests](#tests)
- [Lizenz](#lizenz)

## Warum

Verwenden Sie dieses Plugin, wenn Operatoren ClickTrail-Datensätze in einem
bestehenden Filament-Panel prüfen müssen. Es zeigt gespeicherte First- und
Last-Touch-Datensätze, Consent-Snapshots und Unterdrückungsdiagnostik. Die
Attributionsdatensätze bleiben schreibgeschützt, weil die Capture-Pipeline
diesen Zustand besitzt.

Benötigt PHP >= 8.1, Laravel 12.60+ oder 13.10+, Filament 3.3.55+ und [`clicktrail/php-sdk`](https://github.com/vizuh/clicktrail-php).

## Installation

```bash
composer require vizuh/filament-clicktrail
php artisan vendor:publish --tag=clicktrail-filament
php artisan migrate
```

## Schnellstart

Registrieren Sie das Plugin an einem beliebigen Panel:

```php
use ClickTrail\Filament\ClickTrailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(ClickTrailPlugin::make());
}
// Das Panel zeigt nun die Navigationsgruppe "ClickTrail": die Einstellungsseite,
// die schreibgeschützte Tabelle Attribution Records (aktualisiert alle 60 Sekunden)
// und das Diagnostik-Statistik-Widget. Keine weitere Verdrahtung nötig.
```

## Event-Mapping

`Support\EventMap` übersetzt Eloquent-Modell-Events in die kanonischen `Stable::EVENT_*`-Namen, sodass Ihre Lead- und Order-Models die ClickTrail-Vokabel sprechen, ohne Klebecode:

```php
use App\Models\Lead;
use ClickTrail\Filament\Support\EventMap;

EventMap::resolve(new Lead(), 'created');  // 'lead_created'; kanonischer Eventname
EventMap::resolve(new Lead(), 'deleted');  // 'refund'; Löschung wird als Refund abgebildet
```

Modell-Basenames haben eine Standardzuordnung (`lead`, `appointment`, `sale`, `order`). Erweitern oder überschreiben Sie pro Modell über den Config-Schlüssel `clicktrail-filament.event_map`; ein Basename mit Endung `refund` löst zu `refund` auf, Events mit `attended` zu `booking_completed`.

## Attributionsdatensätze

Die Tabelle `AttributionRecordResource` zeigt gespeicherte First-/Last-Touch-Datensätze mit Kanalfiltern und einer kompakten Consent-Snapshot-Spalte:

```php
TextColumn::make('first_channel')->badge();          // paid_search | organic_search | ...
SelectFilter::make('first_channel');                 // Filter nach kanonischem Kanalnamen
TextColumn::make('consent_snapshot_summary');        // "analytics_storage=granted, ad_user_data=denied"
```

Es gibt keine Create/Edit/Delete-Seiten und keine Routen dorthin: `canCreate()`, `canEdit()` und `canDelete()` geben false zurück. Die Tabelle aktualisiert sich alle 60 Sekunden.

## Einstellungen und Konfiguration

Die Einstellungsseite bearbeitet die veröffentlichte Datei `clicktrail-filament.php`:

```php
'site_id'           => env('CLICKTRAIL_SITE_ID', ''),   // vom Collector vergeben
'endpoint'          => env('CLICKTRAIL_ENDPOINT', 'https://collect.clicktrail.dev/v1/events/batch'),
'consent_resolver'  => env('CLICKTRAIL_CONSENT_RESOLVER', ''), // leer => NullConsentResolver
'capability_gates'  => ['analytics' => true, 'advertising' => true, 'ad_user_data' => true],
```

Ein ausgeschalteter Capability-Gate bedeutet, dass dieser Einsatz keine CMP-Zustimmung erfordert (Gate-Toggle-Semantik). Hinweis: Das Speichern schreibt derzeit zur Laufzeit ins Config-Repository; dauerhafte Persistenz folgt mit der laufenden Settings-Storage-Arbeit.

## Diagnostik

Das Statistik-Widget liest die Zähler aus `clicktrail_diagnostics`; eine Kennzahl pro Unterdrückungsgrund, gelb wenn ungleich null:

```php
Stat::make('adUserDataUnknownAtCapture', '12') // unterdrückte Auslieferungen für diesen Grund
    ->description('Zuletzt vor 2 Stunden gesehen')
```

Ohne aufgezeichnete Unterdrückungen erhalten Sie eine grüne Kennzahl `Suppressions = 0`. Die Queue-Tiefe zeigt `-`, bis der Queued-Delivery-Job ausgeliefert wird (zurückgestellt bis zur Live-Verifikation).

## Consent-Vertrag

Dieses Plugin ist ein Consent-Konsument, kein CMP. Es liest einen normalisierten `ConsentSnapshot` (granted / denied / unknown / not_applicable) über das `ConsentResolverInterface` des Laravel-Adapters. Jedes unaufgelöste Signal gilt als **verweigert**: unterdrückte Auslieferungen werden als Diagnostik-Zeilen festgehalten statt gesendet.

```php
if (! ConsentBehavior::can($snapshot, 'ad_user_data')) {
    // Auslieferung unterdrückt; Grund in clicktrail_diagnostics protokolliert,
    // sofort im Diagnostik-Widget sichtbar
}
```

## Worin es sich unterscheidet

| | Dieses Plugin | Generisches Admin-CRUD |
|---|---|---|
| Attributionsdatensätze | Schreibgeschützte Anzeige pipeline-eigener Daten | Bearbeitbare Zeilen laden zu stiller Datenkorruption ein |
| Consent | Unknown = denied, durchgesetzt vor der Auslieferung | Oft nur ein Anzeige-Flag |
| Events | Kanonische `Stable::EVENT_*`-Namen, geteilt mit jedem ClickTrail-Adapter | Pro Projekt erfundene Eventnamen |

Es selbst erfasst keine Touches und liefert keine Batches aus; das übernimmt die Laravel-Adapter-Pipeline; dieses Plugin macht ihre Ergebnisse sichtbar und prüfbar.

## Tests

Eine PHPUnit-Suite liegt noch nicht vor. CI lintet jede PHP-Datei unter PHP 8.1–8.3:

```bash
composer install --prefer-dist --no-interaction || echo "no deps"
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l   # endet sauber bei Erfolg
```

## Lizenz

MIT.
