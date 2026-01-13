<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Operator;
use App\Models\SubProvider;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use App\Filament\Pages\BulkSubscribe;

class ManageSubscriptions extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum |null $navigationIcon = Heroicon::Star;
    protected static string | UnitEnum | null $navigationGroup = 'Client Management';
    protected string $view = 'filament.pages.manage-subscriptions';

    public ?array $data = [];
    public $selectedProviderId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('goToBulk')
                ->label('Go to Bulk Subscribe')
                ->icon('heroicon-m-plus')
                ->color('gray')
                ->url(fn () => BulkSubscribe::getUrl()),
        ];
    }

    protected function getTables(): array
    {
        return [
            'table',
            'gameTable',
        ];
    }

    public function updatedSelectedProviderId()
    {
        $this->resetTablePagination(table: 'gameTable');
    }
    /**
     * FORM SCHEMA (Matches your GameResource example)
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('operator_id')
                    ->label('Operator')
                    ->options(Operator::pluck('client_name', 'operator_id'))
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('client_id', null)),

                Select::make('client_id')
                    ->label('Client')
                    ->options(function ($get) {
                        $opId = $get('operator_id');
                        if (!$opId) return [];
                        return Client::where('operator_id', $opId)->pluck('client_name', 'client_id');
                    })
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn() => $this->selectedProviderId = null),
            ]);
    }

    /**
     * TABLE 1: Providers
     */
    public function table(Table $table): Table
    {
        return $table
            /** * By using a closure inside query(), Filament re-evaluates 
             * this every time the table is drawn.
             */
            ->query(function () {
                $clientId = $this->data['client_id'] ?? 0;
    
                return SubProvider::query()
                    ->where('on_maintenance', 0)
                    ->with(['provider'])
                    // 1. Check if record exists (Subscribed)
                    ->withExists(['subscriptions as is_subscribed_exists' => function ($query) use ($clientId) {
                        $query->whereHas('cgs', fn ($q) => $q->where('client_id', $clientId));
                    }])
                    // 2. Check if status_id is 0 (Legacy Maintenance Logic)
                    ->withExists(['subscriptions as is_status_zero' => function ($query) use ($clientId) {
                        $query->whereHas('cgs', fn ($q) => $q->where('client_id', $clientId))
                            ->where('status_id', 0);
                    }]);
            })
            ->defaultSort('sub_provider_name', 'asc')
            ->columns([
                TextColumn::make('provider.provider_name')
                    ->label('Partner')
                    ->sortable(),
    
                TextColumn::make('sub_provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sub_provider_id')
                    ->label('ProviderId')
                    ->sortable(),
    
                ToggleColumn::make('is_subscribed_exists')
                    ->label('Subscribed')
                    ->onColor('success')
                    ->offColor('gray')
                    ->sortable()
                    ->updateStateUsing(function ($record, $state) {
                        $clientId = $this->data['client_id'] ?? null;
                        if (!$clientId) return;
    
                        $cgs = \App\Models\Client::find($clientId)->subscription()->firstOrCreate(['client_id' => $clientId]);
    
                        if ($state) {
                            // MATCHING LEGACY: Insert with status_id = 0
                            \App\Models\SubscribeProvider::firstOrCreate([
                                'cgs_id' => $cgs->cgs_id,
                                'provider_id' => $record->sub_provider_id,
                            ], ['status_id' => 0]);
                        } else {
                            \App\Models\SubscribeProvider::where('cgs_id', $cgs->cgs_id)
                                ->where('provider_id', $record->sub_provider_id)
                                ->delete();
                        }
                    }),
    
                // --- MAINTENANCE TOGGLE ---
                ToggleColumn::make('is_status_zero')
                    ->label('Maintenance')
                    ->onColor('success') // Green = Maintenance ON
                    ->offColor('danger')  // Red = Maintenance OFF (Live)
                    ->getStateUsing(function ($record) {
                        // CASE: If NOT in table -> 'on' (Green)
                        if (!$record->is_subscribed_exists) return true;
    
                        // CASE: If in table AND status_id = 0 -> 'off' (Red)
                        if ($record->is_status_zero) return false;
    
                        // Otherwise -> 'on' (Green)
                        return true;
                    })
                    ->updateStateUsing(function ($record, $state) {
                        $clientId = $this->data['client_id'] ?? null;
                        if (!$clientId) return;
    
                        $cgs = \App\Models\Client::find($clientId)->subscription()->first();
                        if ($cgs) {
                            // If toggled to Green (true) -> set status_id to 1
                            // If toggled to Red (false) -> set status_id to 0
                            \App\Models\SubscribeProvider::where('cgs_id', $cgs->cgs_id)
                                ->where('provider_id', $record->sub_provider_id)
                                ->update(['status_id' => $state ? 1 : 0]);
                        }
                    })
                    ->disabled(fn($record) => !$record->is_subscribed_exists),
            ])
            ->actions([
                Action::make('manageGames')
                    ->label('Manage Games')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->slideOver()
                    ->modalHeading(fn ($record) => "Manage Exclusions: {$record->sub_provider_name}")
                    ->modalSubmitAction(false)
                    ->modalContent(fn ($record) => new HtmlString(
                        \Illuminate\Support\Facades\Blade::render(
                            "@livewire('game-exclusion-manager', ['providerId' => \$providerId, 'clientId' => \$clientId])",
                            [
                                'providerId' => $record->sub_provider_id,
                                'clientId' => $this->data['client_id'],
                            ]
                        )
                    )),
            ]);
    }
   
}