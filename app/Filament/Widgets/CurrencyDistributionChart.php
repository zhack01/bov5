<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class CurrencyDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected bool $isCollapsible = true;
    
    public function getHeading(): string
    {
        $start = $this->pageFilters['startDate'] ?? null;
        $end = $this->pageFilters['endDate'] ?? null;

        return ($start && $end && $start === $end) 
            ? "Currency Performance for {$start} (USD)" 
            : 'Currency Performance Distribution (USD)';
    }

    protected function getData(): array
    {
        $dbAggregate = config('database.connections.bo_aggreagate.database');
        $dbMain      = config('database.connections.mysql.database');

        $start = $this->pageFilters['startDate'] ?? now()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();
        $today = now()->toDateString();

        // Query 1: Today's live data from per_round
        $todayQuery = DB::table("{$dbAggregate}.per_round as pr")
            ->join("{$dbMain}.clients as c", "pr.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", 'cr.currency_code')
            ->selectRaw("
                c.default_currency as currency, 
                MAX(cr.exchange_rate) as rate, 
                SUM(pr.bet / cr.exchange_rate) as total_bet_usd,
                SUM(pr.win / cr.exchange_rate) as total_win_usd,
                SUM((pr.bet - pr.win) / cr.exchange_rate) as total_ggr_usd
            ")
            ->where('pr.date', $today)
            ->whereBetween('pr.date', [$start, $end])
            ->groupBy('currency');

        // Query 2: Historical summary data from per_player
        $historyQuery = DB::table("{$dbAggregate}.per_player as pp")
            ->join("{$dbMain}.clients as c", "pp.client_id", "=", "c.client_id")
            ->join("{$dbMain}.currency_rates as cr", "c.default_currency", "=", 'cr.currency_code')
            ->selectRaw("
                c.default_currency as currency, 
                MAX(cr.exchange_rate) as rate, 
                SUM(pp.bet / cr.exchange_rate) as total_bet_usd,
                SUM(pp.win / cr.exchange_rate) as total_win_usd,
                SUM((pp.bet - pp.win) / cr.exchange_rate) as total_ggr_usd
            ")
            ->where('pp.created_at', '<', $today)
            ->whereBetween('pp.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('currency');

        // Combine and Re-aggregate (to handle currencies appearing in both tables)
        $combinedData = DB::query()
            ->fromSub($todayQuery->unionAll($historyQuery), 'combined')
            ->selectRaw('
                currency, 
                MAX(rate) as rate,
                SUM(total_bet_usd) as total_bet_usd,
                SUM(total_win_usd) as total_win_usd,
                SUM(total_ggr_usd) as total_ggr_usd
            ')
            ->groupBy('currency')
            ->orderByDesc('total_ggr_usd')
            ->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'Bet USD',
                    'data' => $combinedData->pluck('total_bet_usd')->map(fn($val) => (float) $val)->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'rates' => $combinedData->pluck('rate')->toArray(), 
                ],
                [
                    'label' => 'Win USD',
                    'data' => $combinedData->pluck('total_win_usd')->map(fn($val) => (float) $val)->toArray(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef4444',
                ],
                [
                    'label' => 'GGR USD',
                    'data' => $combinedData->pluck('total_ggr_usd')->map(fn($val) => (float) $val)->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => true,
                ],
            ],
            'labels' => $combinedData->pluck('currency')->toArray(),
        ];
    }
    
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                        },
                        afterLabel: function(context) {
                            if (context.datasetIndex === 0) {
                                const rate = context.chart.data.datasets[0].rates[context.dataIndex];
                                return 'Exchange Rate: ' + Number(rate).toFixed(4);
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => '$' + value.toLocaleString(),
                    },
                },
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }
}