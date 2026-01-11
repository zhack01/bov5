<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Support\Str;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        // 1. Grab the raw form data from the current request
        // Filament sends repeater data in the request under the 'brands' key
        $data = request()->input('components.0.updates.data.brands') 
                ?? request()->all(); 
        
        // Note: In newer Filament versions, it's safer to use the lifecycle hooks 
        // in the Resource if you don't want to change the DB. 
        // But here is how you handle it if the data IS available:
        
        $currencies = $brand->temp_currencies ?? [];
        $purl = $brand->temp_player_details_url;
        $furl = $brand->temp_fund_transfer_url;
        $turl = $brand->temp_transaction_checker_url;

        if (empty($currencies)) return;

        foreach ($currencies as $code) {
            Client::updateOrCreate(
                [
                    'brand_id' => $brand->brand_id,
                    'default_currency' => $code,
                ],
                [
                    'operator_id' => $brand->operator_id,
                    'client_name' => "{$brand->brand_name}_{$code}",
                    'player_details_url' => $purl,
                    'fund_transfer_url' => $furl,
                    'transaction_checker_url' => $turl,
                    'api_ver' => 5.0,
                ]
            );
        }
    }



    /**
     * Handle the Brand "created" event.
     */
    public function created(Brand $brand): void
    {
        //
    }

    /**
     * Handle the Brand "updated" event.
     */
    public function updated(Brand $brand): void
    {
        //
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        //
    }

    /**
     * Handle the Brand "restored" event.
     */
    public function restored(Brand $brand): void
    {
        //
    }

    /**
     * Handle the Brand "force deleted" event.
     */
    public function forceDeleted(Brand $brand): void
    {
        //
    }
}
