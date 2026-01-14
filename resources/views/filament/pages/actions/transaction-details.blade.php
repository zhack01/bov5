<div class="p-4 space-y-6">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
    
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div>
                <p style="margin: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Total Win (Extension)</p>
                <p style="margin: 4px 0 0 0; font-size: 24px; font-weight: 700; color: #16a34a;">
                    {{ number_format($total, 2) }}
                </p>
            </div>
            <div style="padding: 8px; background: #f0fdf4; border-radius: 8px; flex-shrink: 0;">
                <x-heroicon-m-banknotes style="width: 24px; height: 24px; color: #16a34a;" />
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div>
                <p style="margin: 0; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Report Win</p>
                <p style="margin: 4px 0 0 0; font-size: 24px; font-weight: 700; color: {{ $syncPayout ? '#dc2626' : '#111827' }};">
                    {{ number_format($record->win, 2) }}
                </p>
            </div>
            <div style="padding: 8px; background: {{ $syncPayout ? '#fef2f2' : '#f9fafb' }}; border-radius: 8px; flex-shrink: 0;">
                @if($syncPayout)
                    <x-heroicon-m-exclamation-triangle style="width: 24px; height: 24px; color: #dc2626;" />
                @else
                    <x-heroicon-m-check-badge style="width: 24px; height: 24px; color: #16a34a;" />
                @endif
            </div>
        </div>

    </div>
    <div class="fi-ta-ctn border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Transaction ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Round ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Provider Trans ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Provider Round ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Type</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Amount</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($transactions as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $item->date }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-primary-600">{{ $item->transaction_id }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-primary-600">{{ $item->round_id }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-primary-600">{{ $item->pt_id }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-primary-600">{{ $item->pr_id }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                'bg-blue-100 text-blue-700' => $item->type === 'debit',
                                'bg-green-100 text-green-700' => $item->type === 'credit',
                                'bg-red-100 text-red-700' => $item->type === 'refund',
                            ])>{{ $item->type }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($item->amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $isSuccess = $item->transaction_status === 'SUCCESS_TRANSACTION'; @endphp
                            <x-filament::badge :color="$isSuccess ? 'success' : 'danger'">
                                {{ $item->transaction_status }}
                            </x-filament::badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">No transactions found for this round.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($syncPayout)
    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
        <x-filament::button 
            color="warning" 
            icon="heroicon-m-arrow-path"
            wire:click="syncAmount('{{ $record->round_id }}', {{ $total }})"
            wire:loading.attr="disabled"
        >
            Sync Win to {{ number_format($total, 2) }}
        </x-filament::button>
    </div>
    @endif
</div>