<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\ClientGameSubscribe;
use App\Models\Operator;
use App\Models\SubProvider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class BulkSubscribe extends Page
{
    use InteractsWithForms;

    // These ones SHOULD be static
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string | UnitEnum | null $navigationGroup = 'Client Management';
    protected static ?string $navigationLabel = 'Bulk Subscriptions';

    // THIS ONE MUST NOT BE STATIC (Remove 'static')
    protected string $view = 'filament.pages.bulk-subscribe';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Bulk Subscription Configuration')
                    ->description('Select an operator and the providers you wish to subscribe to all clients under that operator.')
                    ->schema([
                        Select::make('operator_id')
                            ->label('Select Operator')
                            ->options(Operator::pluck('client_name', 'operator_id'))
                            ->searchable()
                            ->required(),

                        Select::make('provider_ids')
                            ->label('Select Providers')
                            ->multiple()
                            ->options(SubProvider::pluck('sub_provider_name', 'sub_provider_id'))
                            ->searchable()
                            ->required()
                            ->hint('All games for these providers will be subscribed with status_id 0 (Live).'),
                    ])->columns(2),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('process')
                ->label('Process Bulk Subscription')
                ->submit('process')
                ->color('success')
                ->requiresConfirmation()
                // This adds the loading indicator to the button automatically
                ->keyBindings(['mod+s']),
        ];
    }

    public function process(): void
    {
        $formData = $this->form->getState();
        $operatorId = $formData['operator_id'];
        $providerIds = $formData['provider_ids'];

        $clientIds = Client::where('operator_id', $operatorId)->pluck('client_id');

        if ($clientIds->isEmpty()) {
            Notification::make()->title('No clients found for this operator')->danger()->send();
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($clientIds as $clientId) {
                // Bridge table
                $cgs = ClientGameSubscribe::firstOrCreate(
                    ['client_id' => $clientId],
                    ['status_id' => 1]
                );

                foreach ($providerIds as $providerId) {
                    // Subscribe Provider
                    DB::statement("
                        INSERT IGNORE INTO subscribe_provider (cgs_id, provider_id, status_id, created_at, updated_at)
                        VALUES (?, ?, 0, NOW(), NOW())
                    ", [$cgs->cgs_id, $providerId]);

                    // Subscribe Games (Fast SQL)
                    DB::statement("
                        INSERT INTO subscribe_games (cgs_id, game_id, status_id, created_at, updated_at)
                        SELECT ?, game_id, 0, NOW(), NOW()
                        FROM games
                        WHERE sub_provider_id = ?
                        AND game_id NOT IN (
                            SELECT game_id FROM subscribe_games WHERE cgs_id = ?
                        )
                    ", [$cgs->cgs_id, $providerId, $cgs->cgs_id]);
                }
            }
            DB::commit();

            Notification::make()
                ->title('Success')
                ->body("Subscribed " . count($providerIds) . " providers for " . count($clientIds) . " clients.")
                ->success()
                ->send();

            $this->form->fill(); // Clear form after success

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }
}