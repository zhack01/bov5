<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Client;
use App\Models\SubscribeGame;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions; 
use Filament\Actions\Contracts\HasActions;         
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class GameExclusionManager extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;

    public $providerId;
    public $clientId;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Game::query()
                    ->where('sub_provider_id', $this->providerId)
                    ->withExists(['gameSubscriptions as is_recorded' => function ($query) {
                        $query->whereHas('cgs', fn($q) => $q->where('client_id', $this->clientId));
                    }])
                    ->withExists(['gameSubscriptions as is_status_zero' => function ($query) {
                        $query->whereHas('cgs', fn($q) => $q->where('client_id', $this->clientId))
                            ->where('status_id', 0);
                    }]);
            })
            ->columns([
                TextColumn::make('game_id')->label('ID')->sortable(),
                TextColumn::make('game_name')->label('Name')->searchable()->sortable(),

                // SUBSCRIBE TOGGLE
                ToggleColumn::make('is_recorded')
                    ->label('Subscribed')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn ($record) => (bool) $record->is_recorded)
                    ->updateStateUsing(function ($record, $state) {
                        $cgs = \App\Models\ClientGameSubscribe::firstOrCreate(['client_id' => $this->clientId]);

                        if ($state) {
                            // MATCHING YOUR LEGACY CODE: Insert with status_id = 0
                            \App\Models\SubscribeGame::firstOrCreate([
                                'cgs_id' => $cgs->cgs_id,
                                'game_id' => $record->game_id,
                            ], ['status_id' => 0]); 
                        } else {
                            \App\Models\SubscribeGame::where('cgs_id', $cgs->cgs_id)
                                ->where('game_id', $record->game_id)
                                ->delete();
                        }
                    }),

                // MAINTENANCE TOGGLE
                ToggleColumn::make('maintenance')
                    ->label('Maintenance')
                    ->onColor('success') // Green = ON
                    ->offColor('danger')  // Red = OFF
                    ->getStateUsing(function ($record) {
                        /**
                         * LEGACY SQL: CASE WHEN sg.game_id IS NOT NULL AND sg.status_id = 0 THEN 'off' ELSE 'on' END
                         */
                        // 1. If NOT in table -> 'on' (Green)
                        if (!$record->is_recorded) return true;

                        // 2. If in table AND status_id = 0 -> 'off' (Red)
                        if ($record->is_status_zero) return false;

                        // 3. Otherwise (in table and status_id != 0) -> 'on' (Green)
                        return true;
                    })
                    ->updateStateUsing(function ($record, $state) {
                        if (!$record->is_recorded) return;

                        $cgs = \App\Models\ClientGameSubscribe::where('client_id', $this->clientId)->first();
                        if ($cgs) {
                            // If toggled to Green (true) -> set status_id to 1 (or anything not 0)
                            // If toggled to Red (false) -> set status_id to 0
                            \App\Models\SubscribeGame::where('cgs_id', $cgs->cgs_id)
                                ->where('game_id', $record->game_id)
                                ->update(['status_id' => $state ? 1 : 0]);
                        }
                    })
                    ->disabled(fn ($record) => !$record->is_recorded),
            ]);
    }


    public function render()
    {
        return view('livewire.game-exclusion-manager');
    }
}