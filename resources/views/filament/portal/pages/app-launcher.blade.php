<x-filament-panels::page>
    <style>
        .wje-wrap {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem 2rem;
        }
        .wje-logo-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 0.4rem;
        }
        .wje-logo-icon {
            width: 44px;
            height: 44px;
            background: #E6F1FB;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wje-logo-icon svg { width: 24px; height: 24px; color: #185FA5; }
        .wje-title {
            font-size: 1.375rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: -0.3px;
        }
        .wje-divider {
            width: 40px;
            height: 2px;
            background: rgba(0,0,0,0.12);
            border-radius: 2px;
            margin: 1.2rem auto 1.4rem;
        }
        .wje-subtitle {
            font-size: 0.75rem;
            opacity: 0.45;
            text-align: center;
            margin: 0 0 2rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .wje-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            width: 100%;
            max-width: 560px;
        }
        @media (max-width: 640px) {
            .wje-grid { grid-template-columns: 1fr; }
        }
        .wje-card {
            background: white;
            border: 0.5px solid rgba(0,0,0,0.1);
            border-radius: 12px;
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
        .wje-card:hover {
            background: #f8f8f8;
            border-color: rgba(0,0,0,0.18);
            transform: translateY(-2px);
        }
        .wje-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .wje-card-icon svg { width: 24px; height: 24px; }
        .wje-card-icon.red { background: #FAECE7; color: #993C1D; }
        .wje-card-icon.blue { background: #E6F1FB; color: #185FA5; }
        .wje-card-label {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0 0 4px;
        }
        .wje-card-sub {
            font-size: 0.75rem;
            opacity: 0.5;
            margin: 0;
        }
        .wje-card-arrow {
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            opacity: 0.4;
        }
        .wje-logout {
            margin-top: 2.5rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            opacity: 0.45;
            background: none;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
            color: inherit;
            padding: 0;
        }
        .wje-logout:hover { opacity: 0.75; }
        .wje-logout svg { width: 16px; height: 16px; }
    </style>

    <div class="wje-wrap">
        <div class="wje-logo-row">
            <div class="wje-logo-icon">
                <x-heroicon-o-bolt style="width:24px;height:24px;color:#185FA5;" />
            </div>
            <h1 class="wje-title">Wahyu Jaya Electric</h1>
        </div>

        <div class="wje-divider"></div>

        <p class="wje-subtitle">Pilih modul aplikasi</p>

        <div class="wje-grid">
            <a href="#" class="wje-card">
            <div class="wje-card-icon red">
                <x-heroicon-o-shopping-cart style="width:24px;height:24px;" />
            </div>
            <p class="wje-card-label">Pencatatan Jual Beli</p>
            <p class="wje-card-sub">Kasir & transaksi penjualan</p>
            <div class="wje-card-arrow">
                <span>Buka</span>
                <x-heroicon-o-arrow-right style="width:14px;height:14px;" />
            </div>
            </a>

            <a href="/admin" class="wje-card">
            <div class="wje-card-icon blue">
                <x-heroicon-o-clipboard-document-check style="width:24px;height:24px;" />
            </div>
            <p class="wje-card-label">Stock Opname</p>
            <p class="wje-card-sub">Manajemen barang & gudang</p>
            <div class="wje-card-arrow">
                <span>Buka</span>
                <x-heroicon-o-arrow-right style="width:14px;height:14px;" />
            </div>
            </a>
        </div>

        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <button type="submit" class="wje-logout">
                <x-heroicon-o-arrow-left-on-rectangle style="width:16px;height:16px;" />
                Keluar
            </button>
        </form>
    </div>
</x-filament-panels::page>