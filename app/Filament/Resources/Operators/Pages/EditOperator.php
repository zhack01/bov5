<?php

namespace App\Filament\Resources\Operators\Pages;

use App\Filament\Resources\Operators\OperatorResource;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
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
        return parent::getSaveFormAction()
            ->requiresConfirmation(); // This will pop up a modal even if you hit Ctrl+S
    }

    protected function afterSave(): void
    {
        $operator = $this->record;
        $data = $this->form->getRawState();

        // Look for an existing secret from the first available client of this operator
        $existingSecret = \App\Models\OAuthClients::whereHas('client', function ($query) use ($operator) {
            $query->where('operator_id', $operator->operator_id);
        })->value('client_secret');

        // Use existing secret if found, otherwise generate a new one
        $sharedSecret = $existingSecret ?? \Illuminate\Support\Str::random(40);

        if (!empty($data['brands'])) {
            foreach ($data['brands'] as $brandData) {
                $brand = \App\Models\Brand::where('operator_id', $operator->operator_id)
                    ->where('brand_name', $brandData['brand_name'])
                    ->first();

                if (!$brand) continue;

                // Automation for new brands (checking if clients exist)
                $clientCount = \App\Models\Client::where('brand_id', $brand->brand_id)->count();

                if ($clientCount === 0) {
                    // ... (Create Brand User logic) ...

                    $currencies = $brandData['temp_currencies'] ?? [];
                    foreach ($currencies as $code) {
                        $client = \App\Models\Client::create([
                            'operator_id'      => $operator->operator_id,
                            'brand_id'         => $brand->brand_id,
                            'client_name'      => strtoupper($brand->brand_name . '_' . $code),
                            'default_currency' => $code,
                            'status_id'        => 1,
                            'api_ver'          => '2.0',
                            // ... (URL fields) ...
                        ]);

                        // Apply the shared secret (either reused or newly generated)
                        \App\Models\OAuthClients::create([
                            'client_id'     => $client->client_id,
                            'client_secret' => $sharedSecret,
                        ]);
                    }
                }
            }
        }
    }
    protected function createRelatedUser($model, $type, $formData): void
    {
        $email = $formData[$type . '_email'] ?? null;
        $username = $formData[$type . '_username'] ?? null;
        $password = $formData[$type . '_password'] ?? null;

        // Skip if the user data wasn't filled out in the form
        if (!$email || !$username) return;

        User::create([
            'email'           => $email,
            'username'        => $username,
            'password_string' => $password, // Raw string as per your system
            'password'        => bcrypt($password),
            'user_type'       => $type,
            'operator_id'     => ($type === 'operator') ? $model->operator_id : $model->operator_id,
            'brand_id'        => ($type === 'brand') ? $model->brand_id : null,
            // If you have a client_id column in Users:
            'client_id'       => ($type === 'agent') ? $model->client_id : null,
        ]);
    }
}
