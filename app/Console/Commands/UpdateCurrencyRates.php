<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class UpdateCurrencyRates extends Command
{
    // This is the name you will type in the terminal
    protected $signature = 'currency:update';

    protected $description = 'Fetch latest exchange rates from API and update database';

    public function handle()
    {
        $this->info('Fetching currency rates...');
        
        (new CurrencyService())->updateRates();
        
        $this->info('Currency rates updated successfully!');
    }
}