<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Widgets;

use ClickTrail\Filament\Models\ClickTrailDiagnostic;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Suppression-reason counters from clicktrail_diagnostics plus the current
 * delivery queue depth. Queue depth is a placeholder until the Laravel
 * queued-delivery job exposes a live count.
 */
class DiagnosticsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = [];

        foreach (ClickTrailDiagnostic::query()->orderByDesc('count')->limit(4)->get() as $row) {
            $stats[] = Stat::make((string) $row->reason_key, number_format((int) $row->count))
                ->description('Last seen ' . ($row->last_seen_at?->diffForHumans() ?? '-'))
                ->color($row->count > 0 ? 'warning' : 'success');
        }

        if ($stats === []) {
            $stats[] = Stat::make('Suppressions', '0')
                ->description('No suppressed deliveries recorded')
                ->color('success');
        }

        // # DEFERRED — Phase N+1 (reason: live verification against the Laravel
        // queue backend). Placeholder until BatchClient delivery job ships.
        $stats[] = Stat::make('Queue depth', '-')
            ->description('Live queue verification deferred')
            ->color('gray');

        return $stats;
    }
}
