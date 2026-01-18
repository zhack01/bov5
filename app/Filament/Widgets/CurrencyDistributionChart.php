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
    
    // We remove the static property and use a method for a dynamic title
    public function getHeading(): string
    {
        $start = $this->pageFilters['startDate'] ?? null;
        $end = $this->pageFilters['endDate'] ?? null;

        if ($start && $end && $start === $end) {
            return "GGR Distribution for {$start} (USD)";
        }

        return 'GGR Distribution by Currency (USD)';
    }

    protected function getData(): array
    {
        // 1. Get filter values (fallback to today if empty)
        $start = $this->pageFilters['startDate'] ?? now()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->toDateString();

        // 2. Query using the date range
        $data = DB::table('bo_aggreagate.per_round as pr')
            ->join('mwapiv2_main.clients as c', 'pr.client_id', '=', 'c.client_id')
            ->join('mwapiv2_main.currency_rates as cr', 'c.default_currency', '=', 'cr.currency_code')
            ->selectRaw('c.default_currency as currency, FORMAT(cr.exchange_rate,4) rate, SUM(pr.bet / FORMAT(cr.exchange_rate,4)) as total_bet_usd')
            // Apply the filter range here
            ->whereBetween('pr.date', [$start, $end]) 
            ->groupBy('currency')
            ->orderByDesc('total_bet_usd')
            ->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'Bet USD',
                    'data' => $data->pluck('total_bet_usd')->map(fn($val) => (float) $val)->toArray(),
                    'rates' => $data->pluck('rate')->toArray(),
                    // Expanding colors in case you have more than 5 currencies
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
                        '#06b6d4', '#f472b6', '#fb923c', '#94a3b8'
                    ],
                ],
            ],
            'labels' => $data->pluck('currency')->toArray(),
        ];
    }
    
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            plugins: {
                tooltip: {
                    callbacks: {
                        // This adds the Rate info to the hover box
                        afterLabel: function(context) {
                            const rate = context.dataset.rates[context.dataIndex];
                            return 'Exchange Rate: ' + Number(rate).toFixed(4);
                        }
                    }
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