<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    // Add this section:
    protected $fillable = [
        'currency_code',
        'exchange_rate',
        'base_currency',
        'last_updated_at',
    ];

    // Optional: ensures dates are treated as Carbon objects
    protected $casts = [
        'last_updated_at' => 'datetime',
    ];
}