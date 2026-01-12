<?php

namespace App\Filament\Resources\Operators\Pages;

use App\Filament\Resources\Operators\OperatorResource;
use App\Models\Brand;
use App\Models\Client;
use App\Models\OAuthClients;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditOperator extends EditRecord
{
    protected static string $resource = OperatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->requiresConfirmation();
    }

    // This is the "Secret Sauce". We store the raw input here.
    public array $capturedBrands = [];

    /**
     * This hook runs BEFORE the database is even touched.
     * We grab the brands array while 'temp_currencies' still exists.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->capturedBrands = $data['brands'] ?? [];
        return $data;
    }

    // protected function afterSave(): void
    // {
    //     $operator = $this->record;

    //     /**
    //      * FIX: Use $this->form->getRawState() instead of the mutated data.
    //      * getRawState() bypasses Filament's "cleaning" and gives us the 
    //      * actual values currently sitting in the browser's form.
    //      */
    //     $formData = $this->form->getRawState();

    //     dd($formData);
        
    //     $brandsData = $formData['brands'] ?? [];

    //     // If it's still empty, we use a more aggressive method to grab it from the request
    //     if (empty($brandsData)) {
    //         $brandsData = request()->input('components.0.updates.data.brands') // Livewire path
    //                     ?? request()->input('serverMemo.data.brands') 
    //                     ?? [];
    //     }

    //     // DEBUG: This should now show your data
    //     // dd($brandsData);

    //     foreach ($brandsData as $brandData) {
    //         // Find brand record
    //         $brand = \App\Models\Brand::where('operator_id', $operator->operator_id)
    //             ->where('brand_name', strtoupper($brandData['brand_name'] ?? ''))
    //             ->first();

    //         if (!$brand) continue;

    //         $currencies = $brandData['temp_currencies'] ?? [];

    //         // Check for 0 clients to prevent double creation
    //         if (!empty($currencies) && $brand->clients()->count() === 0) {
                
    //             // Shared Secret Logic
    //             $sharedSecret = \Illuminate\Support\Facades\DB::table('oauth_clients')
    //                 ->join('clients', 'oauth_clients.client_id', '=', 'clients.client_id')
    //                 ->where('clients.operator_id', $operator->operator_id)
    //                 ->value('client_secret') 
    //                 ?? \Illuminate\Support\Str::random(40);

    //             // 1. Create User
    //             if (!empty($brandData['brand_email'])) {
    //                 \App\Models\User::create([
    //                     'email'           => $brandData['brand_email'],
    //                     'username'        => $brandData['brand_username'],
    //                     'password_string' => $brandData['brand_password'] ?? null,
    //                     'password'        => bcrypt($brandData['brand_password'] ?? 'password'),
    //                     'user_type'       => 'brand',
    //                     'operator_id'     => $operator->operator_id,
    //                     'brand_id'        => $brand->brand_id,
    //                 ]);
    //             }

    //             // 2. Create Clients
    //             foreach ($currencies as $code) {
    //                 $client = \App\Models\Client::create([
    //                     'operator_id'      => $operator->operator_id,
    //                     'brand_id'         => $brand->brand_id,
    //                     'client_name'      => strtoupper($brand->brand_name . '_' . $code),
    //                     'default_currency' => $code,
    //                     'status_id'        => 1,
    //                     'api_ver'          => '2.0',
    //                     'player_details_url'      => $brandData['temp_player_url'] ?? null,
    //                     'fund_transfer_url'       => $brandData['temp_fund_url'] ?? null,
    //                     'transaction_checker_url' => $brandData['temp_check_url'] ?? null,
    //                 ]);

    //                 \App\Models\OAuthClients::create([
    //                     'client_id'     => $client->client_id,
    //                     'client_secret' => $sharedSecret,
    //                 ]);
    //             }
    //         }
    //     }
    // }
}
