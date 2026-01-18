<?php

namespace App\Filament\Widgets;

use App\Models\Player;
use App\Models\Round;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        // 1. Capture Filters (with fallbacks to today or month)
        $start = $this->pageFilters['startDate'] ?? now()->startOfMonth()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();

        // ---------------------------------------------------------
        // STAT 1: Total Players in Range
        // ---------------------------------------------------------
        $playersCount = Player::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->count();
        $playerChart = Player::selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // ---------------------------------------------------------
        // STAT 2: Total Rounds in Range
        // ---------------------------------------------------------
        // We use the 'date' column for performance if it's indexed/partitioned
        $roundsCount = Round::whereBetween('date', [$start, $end])->count();
        $roundsChart = Round::selectRaw('date, COUNT(*) as count')
            ->whereBetween('date', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // ---------------------------------------------------------
        // STAT 3: Total Bets (USD) in Range
        // ---------------------------------------------------------
        // Logic: Use per_round for the most recent data (today) and per_player for history
        $today = now()->toDateString();
        
        $totalBetsUsd = DB::table('bo_aggreagate.per_round')
            ->join('mwapiv2_main.clients', 'per_round.client_id', '=', 'clients.client_id')
            ->join('mwapiv2_main.currency_rates', 'clients.default_currency', '=', 'currency_rates.currency_code')
            ->whereBetween('per_round.date', [$start, $end])
            ->selectRaw('SUM(per_round.bet / FORMAT(currency_rates.exchange_rate,4)) as total_usd')
            ->value('total_usd') ?? 0;

        // Chart Data for Bets
        $betsChart = DB::table('bo_aggreagate.per_round')
            ->selectRaw('date, SUM(bet / FORMAT(exchange_rate,4)) as daily_usd')
            ->join('mwapiv2_main.clients', 'per_round.client_id', '=', 'clients.client_id')
            ->join('mwapiv2_main.currency_rates', 'clients.default_currency', '=', 'currency_rates.currency_code')
            ->whereBetween('per_round.date', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('daily_usd')
            ->toArray();

        return [
            Stat::make('Total Players', number_format($playersCount))
                ->description("New players in selected range")
                ->descriptionIcon(Heroicon::Users, IconPosition::Before)
                ->chart($playerChart)
                ->color('success'),

            Stat::make('Total Rounds', number_format($roundsCount))
                ->description("Round activity in range")
                ->descriptionIcon(Heroicon::ArrowTrendingUp, IconPosition::Before)
                ->chart($roundsChart) 
                ->color('success'),

            Stat::make('Total Bets (USD)', '$' . number_format($totalBetsUsd, 2))
                ->description('Converted volume in range')
                ->descriptionIcon(Heroicon::CurrencyDollar)
                ->chart($betsChart)
                ->color('info'),
        ];
    }
}