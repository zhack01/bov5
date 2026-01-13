<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Operator;
use App\Models\Round;
use BackedEnum;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class GameReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected string $view = 'filament.pages.game-report';

    public ?array $data = [
        'operator_id' => null,
        'client_id' => null,
        'provider_id' => null, // This is Partner
        'sub_provider_id' => null, // This is Provider
        'game_id' => null,
        'player_id' => null,
        'date_start' => null,
        'date_end' => null,
        'outcome' => 'all',
        'currency' => null,
    ];

    public bool $hasRunReport = false;

    public function mount(): void
    {
        $this->form->fill([
            'date_start' => now()->format('Y-m-d'),
            'date_end' => now()->format('Y-m-d'),
            'outcome' => 'all',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Transaction Filters')
                    ->schema([
                        Select::make('operator_id')
                            ->label('Operator')
                            ->options(Operator::pluck('client_name', 'operator_id'))
                            ->required()
                            ->searchable()
                            ->live(),

                        Select::make('client_id')
                            ->label('Client')
                            ->options(Client::pluck('client_name', 'client_id'))
                            ->searchable()
                            ->placeholder('All Clients'),

                       // PARTNER FILTER (Providers Table)
                        Select::make('provider_id')
                        ->label('Partner')
                        ->options(fn() => DB::connection('bo_aggreagate')->table('mwapiv2_main.providers')->pluck('provider_name', 'provider_id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('sub_provider_id', null))
                        ->placeholder('All Partners'),

                        // SUB PROVIDER FILTER (Sub Providers Table)
                        Select::make('sub_provider_id')
                        ->label('Provider')
                        ->options(function (Get $get) {
                            $partnerId = $get('provider_id');
                            $query = DB::connection('bo_aggreagate')->table('mwapiv2_main.sub_providers');
                            
                            // If partner selected, filter. Otherwise show all.
                            if ($partnerId) {
                                $query->where('provider_id', $partnerId);
                            }
                            
                            return $query->pluck('sub_provider_name', 'sub_provider_id');
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('game_id', null))
                        ->placeholder('All Providers'),

                        // GAME FILTER
                        Select::make('game_id')
                        ->label('Game')
                        ->options(function (Get $get) {
                            $subProviderId = $get('sub_provider_id');
                            $query = DB::connection('bo_aggreagate')->table('mwapiv2_main.games');
                            
                            if ($subProviderId) {
                                $query->where('sub_provider_id', $subProviderId);
                            }
                            
                            return $query->limit(100)->pluck('game_name', 'game_id');
                        })
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, Get $get) {
                            $subProviderId = $get('sub_provider_id');
                            return DB::connection('bo_aggreagate')->table('mwapiv2_main.games')
                                ->where('game_name', 'like', "%{$search}%")
                                ->when($subProviderId, fn($q) => $q->where('sub_provider_id', $subProviderId))
                                ->limit(50)
                                ->pluck('game_name', 'game_id')
                                ->toArray();
                        })
                        ->placeholder('Search Game...'),

                        // PLAYER FILTER (Multi-column search)
                        Select::make('player_id')
                        ->label('Player')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return DB::connection('bo_aggreagate')->table('mwapiv2_main.players')
                                ->where(function ($q) use ($search) {
                                    $q->where('username', 'like', "%{$search}%")
                                    ->orWhere('player_id', 'like', "%{$search}%")
                                    ->orWhere('client_player_id', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->player_id => "{$p->username} ({$p->client_player_id})"])
                                ->toArray();
                        })
                        ->placeholder('Search by Name, ID, or Client ID...'),

                        DatePicker::make('date_start')->label('From')->required(),
                        DatePicker::make('date_end')->label('To')->required(),

                        Select::make('outcome')
                            ->options([
                                'all' => 'All',
                                '1' => 'Lose',
                                '2' => 'Win',
                                '3' => 'Progressing',
                                '4' => 'Refund',
                                '5' => 'Failed',
                            ])->default('all'),

                        Select::make('currency')
                            ->label('Convert To')
                            ->options(Currency::pluck('code', 'code'))
                            ->placeholder('Default Currency'),
                    ])->columns(4)
                    ->footerActions([
                        Action::make('viewReport')
                            ->label('View Report')
                            ->icon('heroicon-m-magnifying-glass')
                            ->action(fn() => $this->hasRunReport = true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $f = $this->data;

                if (!$this->hasRunReport || empty($f['operator_id'])) {
                    return Round::query()->whereRaw('1=0');
                }

                $targetCurrency = $f['currency'] ? "'" . $f['currency'] . "'" : "clients.default_currency";

                $query = Round::query()
                    ->select([
                        'per_round.created_at',
                        'per_round.round_id',
                        'per_round.player_id',
                        'per_round.game_id',
                        'per_round.outcome',
                        'per_round.bet',
                        'per_round.win',
                        'per_round.client_id',
                        'clients.client_name',
                        'clients.default_currency as currency_code',
                        // CORRECTED SELECTS:
                        'providers.provider_name as partner_name', 
                        'sub_providers.sub_provider_name as sub_provider_display_name',
                        'games.game_name',
                        'players.username',
                        DB::raw("(
                            SELECT SUBSTR(convert_list, instr(convert_list, $targetCurrency) + 16, instr(substr(convert_list, instr(convert_list, $targetCurrency) + 16, 15), '\"') - 1) 
                            FROM mwapiv2_main.currencies_convert_list 
                            WHERE currency_code = clients.default_currency
                            LIMIT 1
                        ) as rate")
                    ])
                    ->join('mwapiv2_main.clients', 'per_round.client_id', '=', 'clients.client_id')
                    ->leftJoin('mwapiv2_main.operator', 'per_round.operator_id', '=', 'operator.operator_id')
                    // 1. Join per_round to SubProviders (Your 'Provider' column)
                    ->leftJoin('mwapiv2_main.sub_providers', 'per_round.provider_id', '=', 'sub_providers.sub_provider_id')
                    // 2. Join SubProviders to Providers (Your 'Partner' column)
                    ->leftJoin('mwapiv2_main.providers', 'sub_providers.provider_id', '=', 'providers.provider_id')
                    ->leftJoin('mwapiv2_main.games', 'per_round.game_id', '=', 'games.game_id')
                    ->leftJoin('mwapiv2_main.players', 'per_round.player_id', '=', 'players.player_id')
                    ->where('per_round.operator_id', $f['operator_id'])
                    ->whereBetween('per_round.date', [$f['date_start'], $f['date_end']]);

                // HIERARCHICAL FILTER LOGIC
                if (!empty($f['game_id'])) {
                    $query->where('per_round.game_id', $f['game_id']);
                } elseif (!empty($f['sub_provider_id'])) {
                    // If specific provider selected
                    $query->where('per_round.provider_id', $f['sub_provider_id']);
                } elseif (!empty($f['provider_id'])) {
                    // If Partner selected, filter by all their sub-providers
                    $query->whereIn('per_round.provider_id', function ($q) use ($f) {
                        $q->select('sub_provider_id')
                            ->from('mwapiv2_main.sub_providers')
                            ->where('provider_id', $f['provider_id']);
                    });
                }

                return $query
                    ->when($f['client_id'], fn($q) => $q->where('per_round.client_id', $f['client_id']))
                    ->when($f['player_id'], fn($q) => $q->where('per_round.player_id', $f['player_id']))
                    ->when($f['outcome'] !== 'all', fn($q) => $q->where('per_round.outcome', $f['outcome']));
            })
            ->columns([
                TextColumn::make('created_at')->label('Time')->dateTime()->sortable(),
                TextColumn::make('round_id')->label('Round ID')->searchable(),
                TextColumn::make('client_name')->label('Client'),
                
                // USE THE ALIASES FROM THE SELECT
                TextColumn::make('partner_name')
                    ->label('Partner'),
                    
                TextColumn::make('sub_provider_display_name')
                    ->label('Provider'),
                    
                TextColumn::make('game_name')->label('Game'),
                TextColumn::make('username')
                    ->label('Player')
                    ->description(fn ($record) => "ID: {$record->player_id}"),

                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('bet')
                    ->label('Bet')
                    ->alignEnd()
                    ->formatStateUsing(fn ($record) => number_format((float)$record->bet * (float)($record->rate ?: 1), 2)),

                TextColumn::make('win')
                    ->label('Win')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($record) => number_format((float)$record->win * (float)($record->rate ?: 1), 2)),

                TextColumn::make('outcome')
                    ->label('Outcome')
                    ->badge()
                    ->formatStateUsing(fn($state) => match((int)$state) {
                        1 => 'Lose', 2 => 'Win', 3 => 'Progressing', 4 => 'Refund', 5 => 'Failed', default => 'Unknown'
                    })
                    ->color(fn ($state): string => match ((int)$state) {
                        2 => 'success', 1 => 'danger', 3 => 'warning', default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}