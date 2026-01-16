<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function updateRates()
    {
        $base = 'USD';
        $apiKey = config('services.currency_api_key');
        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");

        if ($response->successful()) {
            $rates = $response->json()['conversion_rates'];

            foreach ($rates as $code => $rate) {
                CurrencyRate::updateOrCreate(
                    ['currency_code' => $code],
                    [
                        'exchange_rate' => $rate,
                        'base_currency' => $base,
                        'last_updated_at' => now(),
                    ]
                );
            }
        }
    }
}