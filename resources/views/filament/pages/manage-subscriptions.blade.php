<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @if ($this->data['client_id'] ?? null)
        <x-filament::section heading="Providers">
            {{ $this->table }}
        </x-filament::section>
    @endif
</x-filament-panels::page>