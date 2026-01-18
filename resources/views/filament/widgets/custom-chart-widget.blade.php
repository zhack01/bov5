<x-filament-widgets::widget>
    @if (! ($isModal ?? false))
        <x-filament::section>
            <x-slot name="heading">
                {{ $this->getHeading() }}
            </x-slot>

            <x-slot name="headerEnd">
                {{ $this->zoomAction }}
            </x-slot>

            <div wire:poll.5s="updateChartData">
                @include('filament-widgets::chart-widget')
            </div>
        </x-filament::section>
    @else
        {{-- This part runs ONLY inside the popup modal --}}
        <div class="p-4">
            <div wire:poll.5s="updateChartData">
                @include('filament-widgets::chart-widget')
            </div>
        </div>
    @endif
</x-filament-widgets::widget>