<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Support;

use ClickTrail\Conventions\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Maps Laravel/Filament form events (Eloquent lifecycle events on lead/form/
 * order models) to canonical Stable::EVENT_* names. Adapters may extend the
 * map via the `clicktrail-filament.event_map` config or by subclassing.
 */
final class EventMap
{
    /** Default model-class basename => canonical event name. */
    private const DEFAULT_MAP = [
        'lead' => Stable::EVENT_LEAD_CREATED,
        'appointment' => Stable::EVENT_BOOKING_CREATED,
        'sale' => Stable::EVENT_SALE,
        'order' => Stable::EVENT_SALE,
    ];

    private const REFUND_SUFFIXES = ['refund'];

    private const ATTENDED_MARKERS = ['attended'];

    /** Resolve a canonical event name for a model instance. */
    public static function resolve(Model $model, ?string $lifecycleEvent = null): ?string
    {
        $custom = (array) config('clicktrail-filament.event_map', []);
        $basename = Str::of(class_basename($model))->snake()->toString();

        if (isset($custom[$basename])) {
            return self::normalize((string) $custom[$basename]);
        }

        if ($lifecycleEvent !== null && str_contains($lifecycleEvent, 'deleted')) {
            return Stable::EVENT_REFUND;
        }

        if (isset(self::DEFAULT_MAP[$basename])) {
            return self::normalize(self::DEFAULT_MAP[$basename], $basename);
        }

        return null;
    }

    private static function normalize(string $event, string $basename = ''): string
    {
        foreach (self::REFUND_SUFFIXES as $suffix) {
            if (str_ends_with($basename, $suffix) || str_contains($event, 'refund')) {
                return Stable::EVENT_REFUND;
            }
        }

        foreach (self::ATTENDED_MARKERS as $marker) {
            if (str_contains($event, $marker)) {
                return Stable::EVENT_BOOKING_COMPLETED;
            }
        }

        return $event;
    }

    private function __construct()
    {
    }
}
