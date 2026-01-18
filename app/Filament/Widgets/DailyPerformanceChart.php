<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyPerformanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected bool $isCollapsible = true;

    // 1. Dynamic Heading based on selected date range
    public function getHeading(): string
    {
        $start = $this->pageFilters['startDate'] ?? now()->subDays(6)->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();

        $formattedStart = Carbon::parse($start)->format('M d');
        $formattedEnd = Carbon::parse($end)->format('M d, Y');

        return "Daily Performance: {$formattedStart} - {$formattedEnd}";
    }

    protected function getData(): array
    {
        $dbAggregate = config('database.connections.bo_aggreagate.database');
        $dbMain      = config('database.connections.mysql.database');

        // 2. Default logic: Last 7 days including today
        $start = $this->pageFilters['startDate'] ?? now()->subDays(6)->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();
        $today = now()->toDateString();

        // Part A: Today's Data (per_round)
        $todayQuery = DB::table("{$dbAggregate}.per_round as pr")
            ->join("{$dbMain}.clients as c", "pr.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", 'cr.currency_code')
            ->selectRaw("
                DATE(pr.date) as log_date,
                SUM(pr.bet / cr.exchange_rate) as total_bet,
                SUM(pr.win / cr.exchange_rate) as total_win,
                SUM((pr.bet - pr.win) / cr.exchange_rate) as total_ggr,
                COUNT(pr.round_id) as total_rounds,
                COUNT(DISTINCT pr.player_id) as total_players
            ")
            ->where('pr.date', $today)
            ->whereBetween('pr.date', [$start, $end])
            ->groupBy('log_date');

        // Part B: Historical Data (per_player)
        $historyQuery = DB::table("{$dbAggregate}.per_player as pp")
            ->join("{$dbMain}.clients as c", "pp.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", 'cr.currency_code')
            ->selectRaw("
                DATE(pp.created_at) as log_date,
                SUM(pp.bet / cr.exchange_rate) as total_bet,
                SUM(pp.win / cr.exchange_rate) as total_win,
                SUM((pp.bet - pp.win) / cr.exchange_rate) as total_ggr,
                SUM(pp.total_rounds) as total_rounds,
                COUNT(DISTINCT pp.player_id) as total_players
            ")
            ->where('pp.created_at', '<', $today)
            ->whereBetween('pp.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('log_date');

        // 3. Union and Aggregate
        $data = DB::query()
            ->fromSub($todayQuery->unionAll($historyQuery), 'daily_union')
            ->selectRaw('
                log_date,
                SUM(total_bet) as bet,
                SUM(total_win) as win,
                SUM(total_ggr) as ggr,
                SUM(total_rounds) as rounds,
                SUM(total_players) as players
            ')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'GGR (USD)',
                    'data' => $data->pluck('ggr')->map(fn($v) => (float) $v)->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Players',
                    'data' => $data->pluck('players')->map(fn($v) => (int) $v)->toArray(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Rounds',
                    'data' => $data->pluck('rounds')->map(fn($v) => (int) $v)->toArray(),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => '#8b5cf6',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data->pluck('log_date')->map(fn($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: { callback: (value) => '$' + value.toLocaleString() },
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }
}