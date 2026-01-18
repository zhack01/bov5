<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class OperatorSummary extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Operator Performance (USD)';
    protected static ?int $sort = 2;
    protected bool $isCollapsible = true;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = $this->pageFilters['startDate'] ?? now()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();
        $today = now()->toDateString();

        $dbAggregate = config('database.connections.bo_aggreagate.database');
        $dbMain      = config('database.connections.mysql.database');

        // 1. Today's live data from per_round
        $todayQuery = DB::table("{$dbAggregate}.per_round as pr")
            ->join("{$dbMain}.operator as o", 'pr.operator_id', '=', 'o.operator_id')
            ->join("{$dbMain}.clients as c", 'pr.client_id', '=', 'c.client_id')
            ->join("{$dbMain}.currency_rates as cr", 'c.default_currency', '=', 'cr.currency_code')
            ->selectRaw("
                o.client_name as operator, 
                MAX(cr.exchange_rate) as exchange_rate,
                SUM(pr.bet / cr.exchange_rate) as total_bet_usd,
                SUM(pr.win / cr.exchange_rate) as total_win_usd,
                SUM((pr.bet - pr.win) / cr.exchange_rate) as total_ggr_usd
            ")
            ->where('pr.date', $today)
            ->whereBetween('pr.date', [$start, $end])
            ->groupBy('o.client_name');

        // 2. Historical data from per_player summary table
        $historyQuery = DB::table("{$dbAggregate}.per_player as pp")
            ->join("{$dbMain}.operator as o", 'pp.operator_id', '=', 'o.operator_id')
            ->join("{$dbMain}.clients as c", 'pp.client_id', '=', 'c.client_id')
            ->join("{$dbMain}.currency_rates as cr", 'c.default_currency', '=', 'cr.currency_code')
            ->selectRaw("
                o.client_name as operator, 
                MAX(cr.exchange_rate) as exchange_rate,
                SUM(pp.bet / cr.exchange_rate) as total_bet_usd,
                SUM(pp.win / cr.exchange_rate) as total_win_usd,
                SUM((pp.bet - pp.win) / cr.exchange_rate) as total_ggr_usd
            ")
            ->where('pp.created_at', '<', $today)
            ->whereBetween('pp.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('o.client_name');

        // 3. Union and Re-aggregate to merge operator rows from both tables
        $data = DB::query()
            ->fromSub($todayQuery->unionAll($historyQuery), 'combined_ops')
            ->selectRaw('
                operator, 
                MAX(exchange_rate) as exchange_rate,
                SUM(total_bet_usd) as total_bet_usd,
                SUM(total_win_usd) as total_win_usd,
                SUM(total_ggr_usd) as total_ggr_usd
            ')
            ->groupBy('operator')
            ->orderByDesc('total_ggr_usd')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Bet',
                    'data' => $data->pluck('total_bet_usd')->map(fn($val) => (float) $val)->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'rates' => $data->pluck('exchange_rate')->toArray(), 
                ],
                [
                    'label' => 'Win',
                    'data' => $data->pluck('total_win_usd')->map(fn($val) => (float) $val)->toArray(),
                    'backgroundColor' => '#ef4444',
                ],
                [
                    'label' => 'GGR',
                    'data' => $data->pluck('total_ggr_usd')->map(fn($val) => (float) $val)->toArray(),
                    'backgroundColor' => '#10b981',
                ],
            ],
            'labels' => $data->pluck('operator')->toArray(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            indexAxis: 'y',
            interaction: {
                mode: 'index',     // Groups all datasets for the same index (row)
                intersect: false,  // Allows hovering anywhere in the row to trigger tooltip
                axis: 'y'          // CRITICAL: Forces detection along the vertical axis (rows)
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            // context.parsed.x is used because values are on the horizontal axis
                            let value = context.parsed.x || 0;
                            return ' ' + label + ': $' + value.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        },
                        footer: function(context) {
                            // Access rate stored in the first dataset [0]
                            const i = context[0].dataIndex;
                            const rate = context[0].chart.data.datasets[0].rates[i];
                            return 'Exchange Rate: ' + Number(rate).toFixed(4);
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => '$' + value.toLocaleString()
                    }
                },
                y: {
                    // Adjusts the spacing between the rows
                    grid: {
                        display: false
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