<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class ProviderPerformanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;
    protected ?string $heading = 'Top 20 Providers Performance (USD)';
    protected bool $isCollapsible = true;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $dbAggregate = config('database.connections.bo_aggreagate.database');
        $dbMain      = config('database.connections.mysql.database');

        $start = $this->pageFilters['startDate'] ?? now()->subDays(6)->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();
        $today = now()->toDateString();

        // 1. Today's Live Data (per_round)
        $todayQuery = DB::table("{$dbAggregate}.per_round as pr")
            ->join("{$dbMain}.sub_providers as sp", "pr.provider_id", "=", "sp.sub_provider_id")
            ->join("{$dbMain}.providers as p", "sp.provider_id", "=", "p.provider_id")
            ->join("{$dbMain}.clients as c", "pr.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", "cr.currency_code")
            ->selectRaw("
                p.provider_name as partner_name,
                sp.sub_provider_name as provider_name,
                MAX(cr.exchange_rate) as rate,
                SUM(pr.bet / cr.exchange_rate) as total_bet,
                SUM(pr.win / cr.exchange_rate) as total_win,
                SUM((pr.bet - pr.win) / cr.exchange_rate) as total_ggr,
                COUNT(pr.round_id) as total_rounds,
                COUNT(DISTINCT pr.player_id) as total_players
            ")
            ->where('pr.date', $today)
            ->whereBetween('pr.date', [$start, $end])
            ->groupBy('sp.sub_provider_id', 'p.provider_name', 'sp.sub_provider_name');

        // 2. Historical Data (per_player)
        $historyQuery = DB::table("{$dbAggregate}.per_player as pp")
            ->join("{$dbMain}.sub_providers as sp", "pp.provider_id", "=", "sp.sub_provider_id")
            ->join("{$dbMain}.providers as p", "sp.provider_id", "=", "p.provider_id")
            ->join("{$dbMain}.clients as c", "pp.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", "cr.currency_code")
            ->selectRaw("
                p.provider_name as partner_name,
                sp.sub_provider_name as provider_name,
                MAX(cr.exchange_rate) as rate,
                SUM(pp.bet / cr.exchange_rate) as total_bet,
                SUM(pp.win / cr.exchange_rate) as total_win,
                SUM((pp.bet - pp.win) / cr.exchange_rate) as total_ggr,
                SUM(pp.total_rounds) as total_rounds,
                COUNT(DISTINCT pp.player_id) as total_players
            ")
            ->where('pp.created_at', '<', $today)
            ->whereBetween('pp.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('sp.sub_provider_id', 'p.provider_name', 'sp.sub_provider_name');

        // 3. Union & Final Top 20
        $data = DB::query()
            ->fromSub($todayQuery->unionAll($historyQuery), 'provider_union')
            ->selectRaw('
                partner_name,
                provider_name,
                MAX(rate) as exchange_rate,
                SUM(total_bet) as bet,
                SUM(total_win) as win,
                SUM(total_ggr) as ggr,
                SUM(total_rounds) as rounds,
                SUM(total_players) as players
            ')
            ->groupBy('partner_name', 'provider_name')
            ->orderByDesc('ggr')
            ->limit(20)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Bet (USD)',
                    'data' => $data->pluck('bet')->map(fn($v) => (float) $v)->toArray(),
                    'backgroundColor' => '#3b82f6', // Blue
                    // Store metadata in the first dataset for the tooltip to access
                    'partners' => $data->pluck('partner_name')->toArray(),
                    'rates' => $data->pluck('exchange_rate')->toArray(),
                    'players' => $data->pluck('players')->toArray(),
                    'rounds' => $data->pluck('rounds')->toArray(),
                ],
                [
                    'label' => 'Win (USD)',
                    'data' => $data->pluck('win')->map(fn($v) => (float) $v)->toArray(),
                    'backgroundColor' => '#ef4444', // Red
                ],
                [
                    'label' => 'GGR (USD)',
                    'data' => $data->pluck('ggr')->map(fn($v) => (float) $v)->toArray(),
                    'backgroundColor' => '#10b981', // Green
                ],
            ],
            'labels' => $data->pluck('provider_name')->toArray(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            indexAxis: 'y',
            interaction: {
                mode: 'index',     // Groups all bars in the same ROW
                intersect: false,   // Triggers tooltip even if you hover between bars
                axis: 'y'          // Tells it to look for items at the same Y-position (row)
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            // context[0].label is the Provider Name for that ROW
                            const i = context[0].dataIndex;
                            const partner = context[0].chart.data.datasets[0].partners[i];
                            return partner + ' (' + context[0].label + ')';
                        },
                        label: function(context) {
                            // Use context.parsed.x because the value is on the horizontal axis
                            let val = context.parsed.x || 0;
                            return ' ' + context.dataset.label + ': $' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: (value) => '$' + value.toLocaleString()
                    }
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}