<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form containing your filters and the View button --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- Table only shows data after isReady is true --}}
        <div class="filament-main-content">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>