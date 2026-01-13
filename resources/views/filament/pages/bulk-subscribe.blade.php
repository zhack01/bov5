<x-filament-panels::page>
    <form wire:submit="process">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            {{ $this->getFormActions()[0] }}
        </div>
    </form>

    <div wire:loading wire:target="process" class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
        <div class="flex flex-col items-center bg-white p-6 rounded-lg shadow-xl dark:bg-gray-800">
            <x-filament::loading-indicator class="h-12 w-12 text-primary-600" />
            <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Processing Bulk Subscriptions...</p>
            <p class="text-sm text-gray-500">This may take a moment for large operators.</p>
        </div>
    </div>
</x-filament-panels::page>