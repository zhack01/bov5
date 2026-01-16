<?php

namespace App\Filament\Pages;

use App\Models\Operator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Models\PerPlayerReport as Report;
use Illuminate\Support\Facades\DB;

class SummaryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::PresentationChartLine;
    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected string $view = 'filament.pages.summary-report';

    public ?array $data = [];
    public bool $isReady = false;

    public function mount(): void
    {
        $this->form->fill([
            'operator' => 9,
            'dateType' => 'day',
            'date' => now()->format('Y-m-d'),
            'groupBy' => 'game',
        ]);
    }

    /* ===================== FORM ===================== */

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make('Report Filters')
                    ->columns(5)
                    ->schema([
                        Select::make('operator')
                            ->options(Operator::pluck('client_name', 'operator_id'))
                            ->searchable()
                            ->required(),

                        Select::make('dateType')
                            ->options([
                                'day'   => 'Day',
                                'month'=> 'Month',
                            ])
                            ->reactive(),

                        DatePicker::make('date')
                            ->displayFormat(fn ($get) =>
                                $get('dateType') === 'month' ? 'F Y' : 'Y-m-d'
                            )
                            ->required(),

                        Select::make('groupBy')
                            ->options([
                                'client'   => 'Per Client',
                                'provider' => 'Per Provider',
                                'game'     => 'Per Game',
                            ])
                            ->required(),

                        Actions::make([
                            Action::make('viewReport')
                                ->label('View Data')
                                ->icon('heroicon-m-magnifying-glass')
                                ->color('primary')
                                ->action(fn () => $this->loadReport()),
                        ])->alignEnd(),
                    ]),
            ]);
    }

    public function loadReport(): void
    {
        $this->isReady = true;
        $this->resetTable();
    }

    /* ===================== TABLE ===================== */

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getSummaryQuery())
            ->deferLoading()
            ->columns([
                TextColumn::make('report_date')->label('Date'),

                TextColumn::make('client_name')
                    ->label('Client')
                    ->visible(fn () => in_array($this->data['groupBy'], ['client', 'game'])),

                TextColumn::make('sub_provider_name')
                    ->label('Provider')
                    ->visible(fn () => in_array($this->data['groupBy'], ['provider', 'game'])),

                TextColumn::make('partner_name')
                    ->label('Partner')
                    ->visible(fn () => in_array($this->data['groupBy'], ['provider', 'game'])),

                TextColumn::make('game_name')
                    ->label('Game')
                    ->visible(fn () => $this->data['groupBy'] === 'game'),

                TextColumn::make('bet_usd')
                    ->label('Bet')
                    ->alignRight()
                    ->formatStateUsing(fn ($v) => number_format($v, 4)),

                TextColumn::make('win_usd')
                    ->label('Win')
                    ->alignRight()
                    ->formatStateUsing(fn ($v) => number_format($v, 4)),

                TextColumn::make('ggr_usd')
                    ->label('GGR')
                    ->alignRight()
                    ->sortable(query: fn ($q, $d) => $q->orderBy('raw_ggr', $d))
                    ->color(fn ($v) => $v < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($v) => number_format($v, 4)),

                TextColumn::make('rounds_count')
                    ->label('Rounds')
                    ->alignRight(),

                TextColumn::make('players')
                    ->label('Players')
                    ->alignRight(),
            ]);
    }

    /* ===================== QUERY ===================== */

    protected function getSummaryQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if (! $this->isReady) {
            return Report::query()->whereRaw('1 = 0');
        }

        $operatorId = $this->data['operator'];
        $dateType   = $this->data['dateType'];
        $date       = $this->data['date'];
        $groupBy    = $this->data['groupBy'];

        $query = Report::query()
            ->from('bo_aggreagate.per_player as pp')
            ->leftJoin('mwapiv2_main.clients as c', 'pp.client_id', '=', 'c.client_id')
            ->leftJoin('mwapiv2_main.sub_providers as sp', 'pp.provider_id', '=', 'sp.sub_provider_id')
            ->leftJoin('mwapiv2_main.providers as pr', 'sp.provider_id', '=', 'pr.provider_id')
            ->leftJoin('mwapiv2_main.games as g', 'pp.game_id', '=', 'g.game_id')
            ->where('pp.operator_id', $operatorId);

        if ($dateType === 'day') {
            $query->whereDate('pp.created_at', $date);
        } else {
            $query->whereMonth('pp.created_at', date('m', strtotime($date)))
                ->whereYear('pp.created_at', date('Y', strtotime($date)));
        }

        $groupColumns = ['report_date'];
        $select = [DB::raw('DATE(pp.created_at) as report_date')];

        if (in_array($groupBy, ['client', 'game'])) {
            $select[] = 'c.client_name';
            $groupColumns[] = 'c.client_name';
        }
        
        if (in_array($groupBy, ['provider', 'game'])) {
            $select[] = 'sp.sub_provider_name';
            $select[] = DB::raw('pr.provider_name as partner_name');
            $groupColumns[] = 'sp.sub_provider_name';
            $groupColumns[] = 'pr.provider_name';
        }
        
        if ($groupBy === 'game') {
            $select[] = 'g.game_name';
            $groupColumns[] = 'g.game_name';
        }
        
        $select[] = DB::raw('SUM(pp.bet) as bet_usd');
        $select[] = DB::raw('SUM(pp.win) as win_usd');
        $select[] = DB::raw('SUM(pp.bet - pp.win) as raw_ggr');
        $select[] = DB::raw('SUM(pp.bet - pp.win) as ggr_usd');
        $select[] = DB::raw('SUM(pp.total_rounds) as rounds_count');
        $select[] = DB::raw('COUNT(DISTINCT pp.player_id) as players');

        return $query->selectRaw('0 as id')->groupBy($groupColumns);
    }
}
