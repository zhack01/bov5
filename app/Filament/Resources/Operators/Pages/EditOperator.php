<?php

namespace App\Filament\Resources\Operators\Pages;

use App\Filament\Resources\Operators\OperatorResource;
use App\Models\Client;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
        $data = $this->form->getRawState();
        $brands = $data['brands'] ?? [];

        foreach ($brands as $brandData) {
            // Skip if user didn't select currencies
            $currencies = $brandData['selected_currencies'] ?? [];
            if (empty($currencies)) continue;

            foreach ($currencies as $code) {
                Client::updateOrCreate(
                    [
                        'brand_id' => $brandData['brand_id'],
                        'default_currency' => $code,
                    ],
                    [
                        'operator_id' => $this->record->operator_id,
                        'client_name' => ($brandData['brand_name'] ?? 'Brand') . '_' . $code,
                        'player_details_url' => $brandData['player_details_url'] ?? null,
                        'fund_transfer_url' => $brandData['fund_transfer_url'] ?? null,
                        'transaction_checker_url' => $brandData['transaction_checker_url'] ?? null,
                        'api_ver' => 5.0,
                    ]
                );
            }
        }
    }
}
