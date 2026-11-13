<x-filament-panels::page>
    <style>
        .app-wrap {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem 2rem;
        }
        .app-logo-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 0.4rem;
        }
        .app-logo-icon {
            width: 44px;
            height: 44px;
            background: rgb(219 234 254); /* blue-100 */
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .app-logo-icon {
            background: rgb(30 58 138 / 0.4); /* blue-900/40 */
        }
        .app-logo-icon svg { width: 24px; height: 24px; color: rgb(29 78 216); }
        .dark .app-logo-icon svg { color: rgb(147 197 253); }
        .app-title {
            font-size: 1.375rem;
            font-weight: 600;
            margin: 0;
            color: rgb(17 24 39);
        }
        .dark .app-title { color: rgb(249 250 251); }
        .app-divider {
            width: 40px;
            height: 2px;
            background: rgb(0 0 0 / 0.1);
            border-radius: 2px;
            margin: 1.2rem auto 1.4rem;
        }
        .dark .app-divider { background: rgb(255 255 255 / 0.1); }
        .app-subtitle {
            font-size: 0.7rem;
            color: rgb(156 163 175);
            text-align: center;
            margin: 0 0 2rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .app-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            width: 100%;
            max-width: 560px;
        }
        @media (max-width: 640px) {
            .app-grid { grid-template-columns: 1fr; }
        }
        .app-card {
            background: rgb(255 255 255);
            border: 1px solid rgb(0 0 0 / 0.07);
            border-radius: 14px;
            padding: 2rem 1.5rem 1.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: border-color 0.18s, background 0.18s, transform 0.18s;
            text-align: center;
            text-decoration: none;
            color: inherit;
        }
        .dark .app-card {
            background: rgb(255 255 255 / 0.05);
            border-color: rgb(255 255 255 / 0.08);
        }
        .app-card:hover {
            background: rgb(249 250 251);
            border-color: rgb(0 0 0 / 0.12);
            transform: translateY(-2px);
        }
        .dark .app-card:hover {
            background: rgb(255 255 255 / 0.09);
            border-color: rgb(255 255 255 / 0.15);
        }
        .app-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .app-card-icon svg { width: 24px; height: 24px; }
        .app-card-icon.red {
            background: rgb(254 226 226);
            color: rgb(153 60 29);
        }
        .dark .app-card-icon.red {
            background: rgb(153 60 29 / 0.25);
            color: rgb(252 165 165);
        }
        .app-card-icon.blue {
            background: rgb(219 234 254);
            color: rgb(24 95 165);
        }
        .dark .app-card-icon.blue {
            background: rgb(24 95 165 / 0.25);
            color: rgb(147 197 253);
        }
        .app-card-label {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0 0 4px;
            color: rgb(17 24 39);
        }
        .dark .app-card-label { color: rgb(243 244 246); }
        .app-card-sub {
            font-size: 0.75rem;
            color: rgb(156 163 175);
            margin: 0;
        }
        .dark .app-card-sub { color: rgb(107 114 128); }
        .app-card-arrow {
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            color: rgb(156 163 175);
        }
        .app-card-arrow svg { width: 14px; height: 14px; }
        .app-logout {
            margin-top: 2.5rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: rgb(156 163 175);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.15s;
            padding: 0;
        }
        .app-logout:hover { color: rgb(107 114 128); }
        .app-logout svg { width: 16px; height: 16px; }
    </style>

    <div class="app-wrap">
        <div class="app-logo-row">
            <div class="app-logo-icon">
            <x-heroicon-o-building-storefront style="width:24px;height:24px;" />
            </div>
            <h1 class="app-title">{{ \App\Models\StoreSetting::storeName() }}</h1>
        </div>

        <div class="app-divider"></div>

        <p class="app-subtitle">Pilih modul aplikasi</p>

        <div class="app-grid">
            <a href="{{ url('/') }}" class="app-card">
            <div class="app-card-icon blue">
                <x-heroicon-o-clipboard-document-check />
            </div>
            <p class="app-card-label">Back Office</p>
            <p class="app-card-sub">Manajemen barang, gudang & laporan</p>
            <div class="app-card-arrow">
                <span>Buka</span>
                <x-heroicon-o-arrow-right />
            </div>
            </a>
            <a href="{{ url('/kasir') }}" class="app-card">
            <div class="app-card-icon red">
                <x-heroicon-o-shopping-cart />
            </div>
            <p class="app-card-label">Kasir</p>
            <p class="app-card-sub">Penjualan & cetak nota</p>
            <div class="app-card-arrow">
                <span>Buka</span>
                <x-heroicon-o-arrow-right />
            </div>
            </a>
        </div>

        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <button type="submit" class="app-logout">
            <x-heroicon-o-arrow-left-on-rectangle />
            Keluar
            </button>
        </form>
    </div>
</x-filament-panels::page>