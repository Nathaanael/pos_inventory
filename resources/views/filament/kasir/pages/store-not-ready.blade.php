<div class="snr">
<style>
    .snr { --card: #fff; --text: #1a2433; --muted: #6b7a8c; --line: #e3e9f0; --accent: #1556b8;
           min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: #f4f6f9; color: var(--text); }
    .dark .snr { --card: #161b22; --text: #e6edf3; --muted: #93a1b0; --line: #26303b; --accent: #5aa2ff; background: #0f1318; }
    .snr-card { width: 100%; max-width: 440px; background: var(--card); border-radius: 22px; padding: 36px 28px 28px; text-align: center; box-shadow: 0 24px 60px -24px rgba(16, 40, 72, .3); }
    .snr-ico { width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 999px; background: #fef3c7; color: #b45309; display: grid; place-items: center; }
    .snr-ico svg { width: 32px; height: 32px; }
    .snr-title { font-size: 22px; font-weight: 700; }
    .snr-text { margin-top: 8px; font-size: 14.5px; color: var(--muted); line-height: 1.5; }
    .snr-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 26px; }
    .snr-btn { height: 48px; width: 100%; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1.5px solid var(--line); background: transparent; color: var(--text); }
    .snr-btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
</style>

<div class="snr-card">
    <div class="snr-ico">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" /></svg>
    </div>
    <div class="snr-title">Toko belum disiapkan</div>
    <div class="snr-text">Hubungi admin untuk menyelesaikan setup toko. Setelah selesai, tekan <strong>Cek lagi</strong> untuk mulai berjualan.</div>

    <div class="snr-actions">
        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <button type="submit" class="snr-btn">Keluar</button>
        </form>
        <button type="button" class="snr-btn primary" wire:click="recheck">
            <span wire:loading.remove wire:target="recheck">Cek lagi</span>
            <span wire:loading wire:target="recheck">Memeriksa…</span>
        </button>
    </div>
</div>
</div>
