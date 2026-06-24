<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: bold; margin: 0;">Laporan Laba Rugi</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Revenue -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <span style="font-size: 0.875rem; color: #6b7280;">Pendapatan (Penjualan)</span>
                <span style="font-weight: 500;">Rp {{ number_format($revenue, 0, ',', '.') }}</span>
            </div>

            <!-- COGS -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <span style="font-size: 0.875rem; color: #6b7280;">HPP (Modal Terjual)</span>
                <span style="font-weight: 500; color: #ef4444;">- Rp {{ number_format($cogs, 0, ',', '.') }}</span>
            </div>

            <!-- Gross Profit -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <span style="font-size: 0.875rem; font-weight: 600;">Laba Kotor</span>
                <span style="font-weight: 600; color: #f59e0b;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
            </div>

            <!-- Returns -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <span style="font-size: 0.875rem; color: #6b7280;">Retur Pembelian/Penjualan</span>
                <span style="font-weight: 500; color: #f59e0b;">- Rp {{ number_format($returns, 0, ',', '.') }}</span>
            </div>

            <!-- Net Profit -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; margin-top: 0.5rem; background-color: rgba(156, 163, 175, 0.1); padding: 0.75rem; border-radius: 0.5rem;">
                <span style="font-size: 1rem; font-weight: bold;">Laba Bersih (Estimasi)</span>
                <span style="font-size: 1.125rem; font-weight: bold; color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </span>
            </div>
            
            <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.5rem; text-align: right; margin-bottom: 0;">Data berdasarkan filter: {{ $filterLabel }}</p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
