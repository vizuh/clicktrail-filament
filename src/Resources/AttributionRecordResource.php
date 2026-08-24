<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Resources;

use ClickTrail\Conventions\Stable;
use ClickTrail\Filament\Models\ClickTrailAttribution;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only table of stored attribution records. No create/edit/delete pages:
 * attribution state is owned by the capture pipeline, never hand-edited.
 */
class AttributionRecordResource extends Resource
{
    protected static ?string $model = ClickTrailAttribution::class;

    protected static ?string $navigationIcon = 'heroicon-m-link';

    protected static ?string $navigationGroup = 'ClickTrail';

    protected static ?string $navigationLabel = 'Attribution Records';

    protected static ?string $slug = 'clicktrail-attribution-records';

    public static bool $shouldRegisterNavigation = true;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor_id')
                    ->label('Visitor ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('first_source')
                    ->label('First source'),
                TextColumn::make('first_medium')
                    ->label('First medium'),
                TextColumn::make('first_channel')
                    ->label('First channel')
                    ->badge(),
                TextColumn::make('last_touch_at')
                    ->label('Last touch')
                    ->since()
                    ->sortable(),
                TextColumn::make('consent_snapshot_summary')
                    ->label('Consent snapshot')
                    ->state(static fn (ClickTrailAttribution $record): string => self::summarizeConsent($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('first_channel')
                    ->label('Channel')
                    ->options([
                        Stable::CHANNEL_PAID_SEARCH => 'Paid search',
                        Stable::CHANNEL_PAID_OTHER => 'Paid other',
                        Stable::CHANNEL_ORGANIC_SEARCH => 'Organic search',
                        Stable::CHANNEL_ORGANIC_SOCIAL => 'Organic social',
                        Stable::CHANNEL_EMAIL => 'Email',
                        Stable::CHANNEL_UNKNOWN => 'Unknown',
                    ]),
            ])
            ->defaultSort('last_touch_at', 'desc')
            ->poll('60s');
    }

    /** Compact granted/denied summary of the stored consent snapshot. */
    private static function summarizeConsent(ClickTrailAttribution $record): string
    {
        $snapshot = $record->consent_snapshot;

        if (!is_array($snapshot) || $snapshot === []) {
            return '-';
        }

        $signals = ['analytics_storage', 'advertising_storage', 'ad_user_data', 'ad_personalization'];
        $parts = [];
        foreach ($signals as $signal) {
            if (array_key_exists($signal, $snapshot)) {
                $parts[] = sprintf('%s=%s', $signal, (string) $snapshot[$signal]);
            }
        }

        return implode(', ', $parts) ?: '-';
    }

    public static function getPages(): array
    {
        // Index only: the resource is strictly read-only.
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }
}
