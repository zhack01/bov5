@props(['record', 'transactions', 'total', 'syncPayout'])

<style>
    /* Light Mode (Default) */
    .transaction-modal {
        --tm-bg-card: #ffffff;
        --tm-bg-table: #ffffff;
        --tm-bg-header: #f8fafc;
        --tm-border: #e2e8f0;
        --tm-text-main: #1e293b;
        --tm-text-muted: #64748b;
        --tm-text-code: #3b82f6;
        --tm-bg-code: #eff6ff;
    }

    /* Dark Mode (When Filament adds the .dark class to the html/body) */
    .dark .transaction-modal {
        --tm-bg-card: #1f2937;
        --tm-bg-table: #111827;
        --tm-bg-header: #1f2937;
        --tm-border: #374151;
        --tm-text-main: #f8fafc;
        --tm-text-muted: #94a3b8;
        --tm-text-code: #60a5fa;
        --tm-bg-code: rgba(59, 130, 246, 0.1);
    }
</style>

<div class="transaction-modal" style="padding: 1.5rem; background-color: transparent; font-family: sans-serif; color: var(--tm-text-main);">
    
    <div style="margin-bottom: 24px; padding: 16px; background: var(--tm-bg-card); border: 1px solid var(--tm-border); border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div>
            <p style="margin: 0; font-size: 11px; font-weight: 700; color: var(--tm-text-muted); text-transform: uppercase;">Player Info</p>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
                <button type="button" wire:click.stop="getBalance('{{ $record->player_id }}')" wire:loading.attr="disabled" style="cursor: pointer; background: var(--tm-bg-code); color: var(--tm-text-code); border: 1px solid var(--tm-text-code); padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                    <span wire:loading.remove wire:target="getBalance">{{ $record->player_id }}</span>
                    <span wire:loading wire:target="getBalance">Fetching...</span>
                </button>
                @if($this->playerBalance)
                    <span style="font-size: 16px; font-weight: 700; color: #10b981;">Bal: {{ $this->playerBalance }}</span>
                @endif
            </div>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; font-size: 11px; font-weight: 700; color: var(--tm-text-muted); text-transform: uppercase;">Inquiry Status</p>
            <p style="margin: 4px 0 0 0; font-size: 14px; font-weight: 600;">
                <span style="color: var(--tm-text-code);">{{ $this->inquiryStatus }}</span>
                <span style="margin-left: 8px; font-size: 11px; color: var(--tm-text-muted); font-weight: 400;">{{ Str::limit($this->inquiryResponse, 40) }}</span>
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: var(--tm-bg-card); border: 1px solid var(--tm-border); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div>
                <p style="margin: 0; font-size: 11px; font-weight: 700; color: var(--tm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Total Win (Extension)</p>
                <p style="margin: 4px 0 0 0; font-size: 28px; font-weight: 800; color: #10b981;">{{ number_format($total, 2) }}</p>
            </div>
            <div style="padding: 10px; background: rgba(16, 185, 129, 0.1); border-radius: 10px;">
                <x-heroicon-m-banknotes style="width: 28px; height: 28px; color: #10b981;" />
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: var(--tm-bg-card); border: 1px solid var(--tm-border); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div>
                <p style="margin: 0; font-size: 11px; font-weight: 700; color: var(--tm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                    Report Win
                </p>
                <p style="margin: 4px 0 0 0; font-size: 28px; font-weight: 800; color: {{ $syncPayout ? '#fb7185' : '#10b981' }};">
                    {{ number_format($displayWin ?? $record->win, 2) }}
                </p>
            </div>
            <div style="padding: 10px; background: {{ $syncPayout ? 'rgba(244, 63, 94, 0.1)' : 'rgba(148, 163, 184, 0.1)' }}; border-radius: 10px;">
                @if($syncPayout)
                    <x-heroicon-m-exclamation-triangle style="width: 28px; height: 28px; color: #fb7185;" />
                @else
                    <x-heroicon-m-check-badge style="width: 28px; height: 28px; color: #10b981;" />
                @endif
            </div>
        </div>
    </div>

    <div style="border: 1px solid var(--tm-border); border-radius: 12px; overflow: hidden; background: var(--tm-bg-table); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background: var(--tm-bg-header); border-bottom: 2px solid var(--tm-border);">
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px;">Date</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px;">Transaction ID</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px;">Provider IDs</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px;">Type</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px; text-align: right;">Amount</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px; text-align: center;">Status</th>
                    <th style="padding: 14px 16px; font-weight: 600; color: var(--tm-text-muted); text-transform: uppercase; font-size: 11px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $item)
                    <tr style="border-bottom: 1px solid var(--tm-border);">
                        <td style="padding: 12px 16px; color: var(--tm-text-muted); white-space: nowrap;">{{ $item->date }}</td>
                        <td style="padding: 12px 16px;">
                            <button type="button" wire:click.stop="checkTransactionStatus('{{ $item->round_id }}', '{{ $item->transaction_id }}', '{{ $record->player_id }}')" style="font-family: monospace; font-size: 11px; color: var(--tm-text-code); background: var(--tm-bg-code); padding: 2px 6px; border: 1px solid var(--tm-text-code); border-radius: 4px; cursor: pointer;">
                                {{ $item->transaction_id }}
                            </button>
                            <div style="font-size: 10px; color: var(--tm-text-muted); margin-top: 4px;">Round: {{ $item->round_id }}</div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div style="font-size: 11px; color: var(--tm-text-muted);"><span style="opacity: 0.6;">TX:</span> {{ $item->pt_id }}</div>
                            <div style="font-size: 11px; color: var(--tm-text-muted);"><span style="opacity: 0.6;">RD:</span> {{ $item->pr_id }}</div>
                        </td>
                        <td style="padding: 12px 16px; font-weight: 700; color: var(--tm-text-main); font-size: 12px; text-transform: uppercase;">{{ $item->type }}</td>
                        <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: var(--tm-text-main);">{{ number_format($item->amount, 2) }}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @php $isSuccess = $item->transaction_status === 'SUCCESS_TRANSACTION'; @endphp
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; background: {{ $isSuccess ? 'rgba(16, 185, 129, 0.1)' : 'rgba(244, 63, 94, 0.1)' }}; color: {{ $isSuccess ? '#34d399' : '#fb7185' }}; border: 1px solid {{ $isSuccess ? 'rgba(52, 211, 153, 0.2)' : 'rgba(251, 113, 133, 0.2)' }};">
                                {{ $item->transaction_status }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <button 
                                type="button"
                                x-on:click="
                                    const params = new URLSearchParams({
                                        raw_id: '{{ $item->id }}',
                                        round_id: '{{ $item->round_id }}',
                                        trans_id: '{{ $item->transaction_id }}',
                                        operator_id: '{{ $record->operators?->operator_id }}',
                                        client_name: '{{ $record->operators?->client_name }}',
                                        display_id: '{{ str($item->id)->mask('*', 3, -3) }}',
                                        amount: '{{ $item->amount }}',
                                        player: '{{ $record->player_id }}',
                                        type: '{{ strtolower($item->type) }}'
                                    });
                                    window.open(`/admin/settlement-loggers/create?${params.toString()}`, '_blank');
                                "
                                style="cursor: pointer; background: #6366f1; color: white; border: none; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                            >
                                Settle TX
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
        @if($syncPayout)
            <button 
                type="button"
                wire:click.stop="syncAmount('{{ $record->round_id }}', {{ $total }})"
                x-on:click="
                    setTimeout(() => {
                        let closeBtn = $el.closest('.fi-modal').querySelector('.fi-modal-close-btn');
                        if (closeBtn) closeBtn.click();
                        let cancelBtn = document.querySelector('.fi-modal-footer button[color=\'gray\']');
                        if (cancelBtn) cancelBtn.click();
                    }, 1000)
                "
                wire:loading.attr="disabled"
                style="cursor: pointer; background: #f59e0b; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; font-size: 13px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                onmouseover="this.style.background='#d97706'" 
                onmouseout="this.style.background='#f59e0b'"
                wire:loading.class="opacity-70 cursor-not-allowed"
            >
                <x-heroicon-m-arrow-path 
                    style="width: 16px; height: 16px;" 
                    wire:loading.class="animate-spin" 
                    wire:target="syncAmount" 
                />
                
                <span wire:loading.remove wire:target="syncAmount">
                    Sync Report Win to Extension Total
                </span>
                
                <span wire:loading wire:target="syncAmount">
                    Syncing...
                </span>
            </button>
        @endif
    </div>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        .opacity-70 {
            opacity: 0.7;
        }
    </style>
</div>