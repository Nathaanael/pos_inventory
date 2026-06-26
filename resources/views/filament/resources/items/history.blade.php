<style>
    .hist-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
    .hist-table th { background-color: rgba(156, 163, 175, 0.1); padding: 0.75rem 1rem; font-weight: 600; border-bottom: 1px solid rgba(156, 163, 175, 0.2); }
    .hist-table td { padding: 0.75rem 1rem; border-bottom: 1px solid rgba(156, 163, 175, 0.1); }
    .hist-table tr:hover { background-color: rgba(156, 163, 175, 0.05); }
    .hist-badge { padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; display: inline-block; }
    .bg-inbound { background-color: rgba(16, 185, 129, 0.1); color: #059669; }
    .bg-outbound { background-color: rgba(245, 158, 11, 0.1); color: #d97706; }
    .bg-retur { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .bg-adj { background-color: rgba(59, 130, 246, 0.1); color: #2563eb; }
    .text-plus { color: #059669; font-weight: 600; }
    .text-minus { color: #dc2626; font-weight: 600; }
</style>

<div style="overflow-x: auto; border: 1px solid rgba(156, 163, 175, 0.2); border-radius: 0.5rem; margin-bottom: 1rem;">
    <table class="hist-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Catatan</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @php
                            $typeLabel = match ($movement->type) {
                                'return_in' => 'Return In',
                                'return_out' => 'Retur',
                                default => ucfirst($movement->type),
                            };
                            $colorClass = match ($movement->type) {
                                'inbound', 'return_in' => 'bg-inbound',
                                'outbound' => 'bg-outbound',
                                'return_out', 'void' => 'bg-retur',
                                'adjustment' => 'bg-adj',
                                default => '',
                            };
                        @endphp
                        <span class="hist-badge {{ $colorClass }}">
                            {{ $typeLabel }}
                        </span>
                    </td>
                    <td>{{ $movement->notes ?: '-' }}</td>
                    <td style="text-align: right;">
                        <span class="{{ $movement->quantity > 0 ? 'text-plus' : 'text-minus' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">{{ $movement->stock_after }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 2rem; text-align: center; color: #6b7280;">
                        Belum ada riwayat pergerakan stok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($movements->count() > 0)
    <p style="font-size: 0.75rem; color: #6b7280; text-align: center; margin-top: 1rem;">
        Menampilkan {{ $movements->count() }} riwayat terakhir (Maksimal 10).
    </p>
@endif
