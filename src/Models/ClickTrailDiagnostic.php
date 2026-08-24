<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Suppression/diagnostic counter rows (e.g. why an upload was suppressed:
 * ad_user_data unknown, endpoint unreachable, queue disabled).
 *
 * @property int $id
 * @property string $reason_key
 * @property int $count
 * @property \Carbon\CarbonInterface|null $last_seen_at
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
class ClickTrailDiagnostic extends Model
{
    protected $table = 'clicktrail_diagnostics';

    protected $guarded = [];

    protected $casts = [
        'count' => 'integer',
        'last_seen_at' => 'datetime',
    ];
}
