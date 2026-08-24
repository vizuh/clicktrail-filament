<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stored first/last-touch attribution record. Written by the Laravel capture
 * middleware pipeline when persistence is permitted by consent; read here by
 * AttributionRecordResource (read-only).
 *
 * @property int $id
 * @property string $visitor_id
 * @property string|null $first_source
 * @property string|null $first_medium
 * @property string|null $first_channel
 * @property string|null $last_source
 * @property string|null $last_medium
 * @property string|null $last_channel
 * @property \Carbon\CarbonInterface|null $last_touch_at
 * @property array|null $consent_snapshot
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
class ClickTrailAttribution extends Model
{
    protected $table = 'clicktrail_attribution_records';

    protected $guarded = [];

    protected $casts = [
        'consent_snapshot' => 'array',
        'last_touch_at' => 'datetime',
    ];
}
