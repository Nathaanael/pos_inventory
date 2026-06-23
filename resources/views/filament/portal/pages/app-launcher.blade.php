<x-filament-panels::page>
    <style>
        .launcher-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            width: 100%;
            padding: 1rem;
        }
        .launcher-wrapper {
            max-width: 56rem;
            width: 100%;
        }
        .launcher-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .launcher-title {
            font-size: 1.875rem;
            font-weight: 700;
        }
        .launcher-subtitle {
            margin-top: 0.5rem;
            opacity: 0.7;
        }
        .launcher-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .launcher-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .launcher-card {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
        }
        .launcher-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .launcher-card-bg {
            position: absolute;
            inset: 0;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        .launcher-card:hover .launcher-card-bg {
            opacity: 1;
        }
        .launcher-card-content {
            position: relative;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 250px;
        }
        .launcher-icon {
            width: 5rem;
            height: 5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
            transition: transform 0.3s ease;
        }
        .launcher-card:hover .launcher-icon {
            transform: scale(1.1);
        }
        .launcher-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin: 0;
        }
        .launcher-card-desc {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.8;
            text-align: center;
        }
        .logout-container {
            margin-top: 3rem;
            text-align: center;
        }
        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s ease;
            font-size: 1rem;
            color: inherit;
        }
        .logout-btn:hover {
            opacity: 1;
        }
        .logout-icon {
            width: 1.25rem;
            height: 1.25rem;
        }
    </style>

    <div class="launcher-container">
        <div class="launcher-wrapper">
            <div class="launcher-header">
                <h1 class="launcher-title">Wahyu Jaya Electric</h1>
                <p class="launcher-subtitle">Silakan pilih modul aplikasi yang ingin diakses.</p>
            </div>

            <div class="launcher-grid">
                <!-- Pencatatan Jual Beli -->
                <a href="#" class="launcher-card">
                    <div class="launcher-card-bg" style="background-color: #E74C3C;"></div>
                    <div class="launcher-card-content">
                        <x-heroicon-o-shopping-cart class="launcher-icon" />
                        <h2 class="launcher-card-title">Pencatatan Jual Beli</h2>
                        <p class="launcher-card-desc">Kasir dan Transaksi Penjualan</p>
                    </div>
                </a>

                <!-- Stock Opname -->
                <a href="/admin" class="launcher-card">
                    <div class="launcher-card-bg" style="background-color: #3498DB;"></div>
                    <div class="launcher-card-content">
                        <x-heroicon-o-clipboard-document-check class="launcher-icon" />
                        <h2 class="launcher-card-title">Stock Opname</h2>
                        <p class="launcher-card-desc">Manajemen Barang dan Gudang</p>
                    </div>
                </a>
            </div>

            <div class="logout-container">
                <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <x-heroicon-o-arrow-left-on-rectangle class="logout-icon" />
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
