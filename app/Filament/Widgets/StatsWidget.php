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
        $today = now()->toDateString();

        // Database Names from Config
        $dbAggregate = config('database.connections.bo_aggreagate.database');
        $dbMain      = config('database.connections.mysql.database');

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
        // STAT 3: Total Bets (Split Logic: per_round vs per_player)
        // ---------------------------------------------------------
        
        // Query Part A: Today's data from per_round
        $todayQuery = DB::table("{$dbAggregate}.per_round as pr")
            ->selectRaw("DATE(pr.date) as date, SUM(pr.bet / cr.exchange_rate) as daily_usd")
            ->join("{$dbMain}.clients as c", 'pr.client_id', '=', 'c.client_id')
            ->join("{$dbMain}.currency_rates as cr", 'c.default_currency', '=', 'cr.currency_code')
            ->where('pr.date', $today)
            ->whereBetween('pr.date', [$start, $end]) // Respect user filter if today is included
            ->groupBy('date');

        // Query Part B: Historical data from per_player
        $historyQuery = DB::table("{$dbAggregate}.per_player as pp")
            ->selectRaw("DATE(pp.created_at) as date, SUM(pp.bet / cr.exchange_rate) as daily_usd")
            ->join("{$dbMain}.clients as c", 'pp.client_id', '=', 'c.client_id')
            ->join("{$dbMain}.currency_rates as cr", 'c.default_currency', '=', 'cr.currency_code')
            ->where('pp.created_at', '<', $today) // Strictly before today
            ->whereBetween('pp.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('date');

        // Combine for Chart and Total
        $combinedResults = $todayQuery->unionAll($historyQuery)
            ->orderBy('date')
            ->get();

        $totalBetsUsd = $combinedResults->sum('daily_usd');
        $betsChart = $combinedResults->pluck('daily_usd')->toArray();

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