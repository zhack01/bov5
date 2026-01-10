<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $primaryKey = 'brand_id';

    protected $fillable = ['brand_name', 'operator_id', 'status_id'];

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'operator_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'brand_id', 'brand_id');
    }

    protected static function booted()
    {
        static::saved(function ($brand) {
            // Ensure we have an array of currencies
            $currencies = is_array($brand->currencies) ? $brand->currencies : [];

            // 1. Get existing currencies for this brand to see what needs to be removed (optional)
            $existingClients = $brand->clients()->pluck('default_currency')->toArray();

            foreach ($currencies as $currency) {
                $currency = strtoupper($currency);
                $generatedName = strtoupper($brand->brand_name) . "_{$currency}";

                // 2. The updateOrCreate logic handles both Create and Edit
                $brand->clients()->updateOrCreate(
                    ['default_currency' => $currency], // Unique lookup key
                    [
                        'operator_id' => $brand->operator_id,
                        'client_name' => $generatedName,
                        'player_details_url' => $brand->player_details_url,
                        'fund_transfer_url' => $brand->fund_transfer_url,
                        'transaction_checker_url' => $brand->transaction_checker_url,
                        // If you have a User/Agent link:
                        'username' => $brand->brand_username . "_" . strtolower($currency),
                    ]
                );
            }

            // 3. Optional: Delete clients if the currency was removed from the list
            $brand->clients()->whereNotIn('default_currency', $currencies)->delete();
        });
    }
}