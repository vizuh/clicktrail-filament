<?php

declare(strict_types=1);

namespace ClickTrail\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

/**
 * ClickTrail settings form page: site identity, delivery endpoint, consent
 * resolver wiring, and per-capability gates. Values persist to the published
 * clicktrail-filament.php config via the standard config repository.
 *
 * Consent contract note (mirrors ClickTrail\Consent): unknown is DENIED.
 * Any capability whose CMP signal resolves to unknown/not_applicable is
 * treated as suppressed until explicitly granted.
 */
class ClickTrailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'clicktrail-filament::settings';

    protected static ?string $navigationGroup = 'ClickTrail';

    protected static ?string $title = 'ClickTrail Settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_id' => (string) config('clicktrail-filament.site_id'),
            'endpoint' => (string) config('clicktrail-filament.endpoint'),
            'consent_resolver' => (string) config('clicktrail-filament.consent_resolver'),
            'gates' => (array) config('clicktrail-filament.capability_gates', []),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Site')->schema([
                TextInput::make('site_id')
                    ->label('Site ID')
                    ->required()
                    ->maxLength(64),
                TextInput::make('endpoint')
                    ->label('Collector endpoint')
                    ->url()
                    ->required(),
            ]),
            Section::make('Consent')->schema([
                TextInput::make('consent_resolver')
                    ->label('Consent resolver class')
                    ->placeholder('App\Support\CmpConsentResolver')
                    ->helperText(
                        'Must implement ClickTrail\Laravel\Consent\ConsentResolverInterface. ' .
                        'Empty uses NullConsentResolver: every unknown signal counts as denied.'
                    ),
                Toggle::make('gates.analytics')
                    ->label('Analytics gate')
                    ->helperText('Off = analytics use does not require CMP consent.'),
                Toggle::make('gates.advertising')
                    ->label('Advertising storage gate'),
                Toggle::make('gates.ad_user_data')
                    ->label('Ad user data gate'),
            ])->description(
                'Unknown consent values are always treated as DENIED (unknown=denied default). ' .
                'Suppressed deliveries are logged with a suppression reason.'
            ),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Config write-back placeholder; runtime persistence lands with the
        // settings-storage task (config repo is read-only at request time in
        // some hosts). DEFERRED — Phase N+1 (reason: storage backend choice).
        foreach (['site_id', 'endpoint', 'consent_resolver'] as $key) {
            config(["clicktrail-filament.{$key}" => $state[$key] ?? '']);
        }
        config(['clicktrail-filament.capability_gates' => $state['gates'] ?? []]);
    }
}
