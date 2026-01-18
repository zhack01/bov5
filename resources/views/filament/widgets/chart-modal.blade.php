<div class="p-2">
    {{-- We pass isModal => true to the component --}}
    @livewire($widget::class, [
        'pageFilters' => $widget->pageFilters,
        'isModal' => true 
    ])
    
    <x-filament-actions::modals />
</div>