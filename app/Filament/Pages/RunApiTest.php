<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class RunApiTest extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';
    protected string $view = 'filament.pages.run-api-test';
    protected static ?string $title = 'Tiger Games API Tester';

    protected static string $layout = 'filament-panels::components.layout.index';
    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];
    public array $report = [];
    public bool $isRunning = false;
    public ?string $reportUrl = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data') // This links the form to the $data array
            ->schema([
                Section::make('API Test Context')
                    ->schema([
                        TextInput::make('round_id')
                            ->label('Target Round ID')
                            ->required()
                            ->placeholder('e.g. RDTG5362273-119-201'),
                    ]),
            ]);
    }

    public function runTests()
    {
        set_time_limit(0);

        $formData = $this->form->getState();
        $this->isRunning = true;

        try {
            // 1. Load context from DB (The values that go into the Environment)
            $context = \App\Services\ApiTest\RoundContextService::load($formData['round_id']);
            
            // 2. Run the actual Newman command
            $result = \App\Services\ApiTest\NewmanRunner::run($formData['round_id'], (array)$context);
            
            $this->reportUrl = $result['report_url'];
            
            \Filament\Notifications\Notification::make()->success()->title('Newman Run Complete')->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()->danger()->title('Newman Error')->body($e->getMessage())->send();
        }

        $this->isRunning = false;
    }
}