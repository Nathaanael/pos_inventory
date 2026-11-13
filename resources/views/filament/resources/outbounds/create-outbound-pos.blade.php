<x-filament-panels::page>
    {{-- Inline Styles for POS Layout --}}
    <style>
        .pos-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            min-height: calc(100vh - 12rem);
        }
        @media (min-width: 1024px) {
            .pos-container {
                grid-template-columns: 58fr 42fr;
            }
        }

        /* Left Panel - Product Grid */
        .pos-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
        }
        .pos-search-bar {
            position: relative;
        }
        .pos-search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            background: #ffffff;
            color: #1f2937;
            transition: all 0.2s ease;
            outline: none;
        }
        .pos-search-bar input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .pos-search-bar .search-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.125rem;
            height: 1.125rem;
            color: #9ca3af;
            pointer-events: none;
        }
        .dark .pos-search-bar input {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: #f3f4f6;
        }
        .dark .pos-search-bar input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        /* Category Pills */
        .pos-categories {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }
        .pos-categories::-webkit-scrollbar { height: 4px; }
        .pos-categories::-webkit-scrollbar-track { background: transparent; }
        .pos-categories::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .dark .pos-categories::-webkit-scrollbar-thumb { background: #4b5563; }

        .pos-category-pill {
            flex-shrink: 0;
            padding: 0.4375rem 1rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .pos-category-pill.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        .dark .pos-category-pill {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: #9ca3af;
        }

        .dark .pos-category-pill.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
        }

        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            overflow-y: auto;
            flex: 1;
            padding-right: 0.25rem;
            align-content: start;
        }
        @media (min-width: 640px) {
            .pos-product-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 1280px) {
            .pos-product-grid { grid-template-columns: repeat(4, 1fr); }
        }

        .pos-product-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 8.5rem;
        }
        .pos-product-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 0.75rem;
            opacity: 0;
            background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(217,119,6,0.04));
            transition: opacity 0.2s ease;
        }


        .pos-product-card:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .pos-product-card.out-of-stock {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .pos-product-img-wrapper {
            height: 120px;
            width: 100%;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .pos-product-img-wrapper {
            background: rgba(0,0,0,0.2);
            border-bottom-color: rgba(255,255,255,0.05);
        }

        .dark .pos-product-card {
            background: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.08);
        }


        .pos-product-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .dark .pos-product-name { color: #f3f4f6; }

        .pos-product-code {
            font-size: 0.6875rem;
            color: #9ca3af;
        }
        .pos-product-price {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #d97706;
        }
        .dark .pos-product-price { color: #fbbf24; }

        .pos-stock-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            z-index: 10;
        }
        .pos-stock-badge.in-stock { background: #dcfce7; color: #166534; }
        .pos-stock-badge.low-stock { background: #fef9c3; color: #854d0e; }
        .pos-stock-badge.no-stock { background: #fee2e2; color: #991b1b; }
        .dark .pos-stock-badge.in-stock { background: rgba(16,185,129,0.15); color: #34d399; }
        .dark .pos-stock-badge.low-stock { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .dark .pos-stock-badge.no-stock { background: rgba(239,68,68,0.15); color: #f87171; }

        /* Right Panel - Cart & Form */
        .pos-right {
            display: flex;
            flex-direction: column;
            gap: 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            overflow: hidden;
            min-width: 0;
        }
        .dark .pos-right {
            background: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.08);
        }

        .pos-right-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
        }
        .pos-right-header h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        .pos-right-header .code {
            font-size: 0.8125rem;
            opacity: 0.85;
            margin-top: 0.125rem;
        }

        .pos-right-body {
            padding: 1rem 1.25rem;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Form Fields */
        .pos-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .pos-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .pos-form-group.full { grid-column: 1 / -1; }
        .pos-form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .dark .pos-form-label { color: #9ca3af; }

        .pos-form-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #ffffff;
            color: #1f2937;
            transition: all 0.15s ease;
            outline: none;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
        .pos-form-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245,158,11,0.15);
        }
        .dark .pos-form-input {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: #f3f4f6;
        }
        .dark .pos-form-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245,158,11,0.2);
        }

        select.pos-form-input {
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 4.5L6 7.5L9 4.5' stroke='%239ca3af' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2rem;
        }

        /* Cart Table */
        .pos-cart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pos-cart-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dark .pos-cart-title { color: #f3f4f6; }
        .pos-cart-count {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        .pos-cart-clear {
            font-size: 0.75rem;
            color: #ef4444;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 500;
            transition: color 0.15s;
        }


        .pos-cart-items {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
            overflow-y: auto;
            max-height: 35vh;
        }
        .pos-cart-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .dark .pos-cart-item {
            background: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.05);
        }
        .pos-cart-item-info {
            flex: 1;
            min-width: 0;
        }
        .pos-cart-item-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .pos-cart-item-name { color: #f3f4f6; }
        .pos-cart-item-price {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .pos-cart-qty {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .dark .pos-cart-qty {
            border-color: rgba(255,255,255,0.1);
        }
        .pos-cart-qty button {
            width: 1.75rem;
            height: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.15s;
        }

        .dark .pos-cart-qty button {
            background: rgba(255,255,255,0.05);
            color: #9ca3af;
        }

        .pos-cart-qty input {
            width: 2.5rem;
            height: 1.75rem;
            text-align: center;
            border: none;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1f2937;
            background: #ffffff;
            outline: none;
        }
        .dark .pos-cart-qty input {
            background: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.1);
            color: #f3f4f6;
        }

        .pos-cart-subtotal {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #d97706;
            white-space: nowrap;
            min-width: 5rem;
            text-align: right;
        }
        .dark .pos-cart-subtotal { color: #fbbf24; }

        .pos-cart-remove {
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: none;
            color: #d1d5db;
            cursor: pointer;
            border-radius: 0.25rem;
            transition: all 0.15s;
            flex-shrink: 0;
        }


        .pos-cart-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: #9ca3af;
        }
        .pos-cart-empty svg {
            width: 2.5rem;
            height: 2.5rem;
            margin: 0 auto 0.75rem;
            color: #d1d5db;
        }
        .dark .pos-cart-empty svg { color: #4b5563; }
        .pos-cart-empty p {
            font-size: 0.8125rem;
            margin: 0;
        }

        /* Summary / Footer */
        .pos-right-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
        }
        .dark .pos-right-footer {
            border-color: rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.02);
        }
        .pos-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            padding: 0.25rem 0;
        }
        .pos-summary-row .label { color: #6b7280; }
        .dark .pos-summary-row .label { color: #9ca3af; }
        .pos-summary-row .value { font-weight: 600; color: #1f2937; }
        .dark .pos-summary-row .value { color: #f3f4f6; }

        .pos-summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0 0.5rem;
            border-top: 2px solid #e5e7eb;
            margin-top: 0.5rem;
        }
        .dark .pos-summary-total { border-color: rgba(255,255,255,0.1); }
        .pos-summary-total .label {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #1f2937;
        }
        .dark .pos-summary-total .label { color: #f3f4f6; }
        .pos-summary-total .value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #d97706;
        }
        .dark .pos-summary-total .value { color: #fbbf24; }

        .pos-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .pos-btn {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            transition: all 0.2s ease;
        }
        .pos-btn svg { width: 1rem; height: 1rem; }
        .pos-btn-primary {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .pos-btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(245, 158, 11, 0.3);
        }
        .pos-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .pos-btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .dark .pos-btn-secondary {
            background: rgba(255,255,255,0.05);
            color: #9ca3af;
            border-color: rgba(255,255,255,0.1);
        }


        /* Divider */
        .pos-divider {
            border: none;
            height: 1px;
            background: #e5e7eb;
            margin: 0;
        }
        .dark .pos-divider { background: rgba(255,255,255,0.08); }

        /* Empty product state */
        .pos-empty-grid {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
        }
        .pos-empty-grid svg {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 0.75rem;
            color: #d1d5db;
        }
        .dark .pos-empty-grid svg { color: #4b5563; }

        /* Loading overlay for save */
        .pos-loading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Add to cart animation keyframe */
        @keyframes addPulse {
            0% { box-shadow: 0 0 0 0 rgba(245,158,11,0.4); }
            70% { box-shadow: 0 0 0 8px rgba(245,158,11,0); }
            100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
        }
        .pos-product-card.just-added {
            animation: addPulse 0.5s ease;
        }
    </style>

    <div class="pos-container">
        {{-- ═══════ LEFT PANEL: Product Catalog ═══════ --}}
        <div class="pos-left">
            {{-- Title & Code --}}
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.25rem;">
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #1f2937; margin: 0 0 0.5rem 0;">Tambah Barang Keluar</h2>
                    <span style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);">
                        {{ $transaction_code }}
                    </span>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="pos-search-bar" style="margin-top: 0.5rem;">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari barang (nama, kode, barcode)..."
                />
            </div>

            {{-- Category Pills --}}
            <div class="pos-categories">
                <button
                    type="button"
                    wire:click="$set('selectedCategory', null)"
                    class="pos-category-pill {{ is_null($this->selectedCategory) ? 'active' : '' }}"
                >
                    Semua
                </button>
                @foreach($this->getCategories() as $cat)
                    <button
                        type="button"
                        wire:click="$set('selectedCategory', {{ $cat->id }})"
                        class="pos-category-pill {{ $this->selectedCategory === $cat->id ? 'active' : '' }}"
                    >
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            {{-- Product Grid --}}
            <div class="pos-product-grid" wire:loading.class="opacity-50" wire:target="search, selectedCategory">
                @php $filteredItems = $this->getFilteredItems(); @endphp

                @forelse($filteredItems as $item)
                    @php
                        $isOutOfStock = $item->current_stock <= 0;
                        $isLowStock = !$isOutOfStock && $item->current_stock <= $item->min_stock;
                        $displayPrice = $this->price_type_applied === 'grosir' ? $item->harga_jual_grosir : $item->harga_jual_retail;
                    @endphp
                    <div
                        wire:key="item-{{ $item->id }}"
                        class="pos-product-card {{ $isOutOfStock ? 'out-of-stock' : '' }}"
                        @if(!$isOutOfStock)
                            wire:click="addToCart({{ $item->id }})"
                            x-on:click="$el.classList.add('just-added'); setTimeout(() => $el.classList.remove('just-added'), 500)"
                        @endif
                    >
                        {{-- Image/Placeholder --}}
                        <div class="pos-product-img-wrapper">
                            @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#d1d5db" style="width: 3.5rem; height: 3.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            @endif
                        </div>

                        {{-- Stock Badge --}}
                        <span class="pos-stock-badge {{ $isOutOfStock ? 'no-stock' : ($isLowStock ? 'low-stock' : 'in-stock') }}">
                            {{ $isOutOfStock ? 'Habis' : $item->current_stock . ' ' . ($item->unit->name ?? 'pcs') }}
                        </span>

                        {{-- Info --}}
                        <div style="padding: 0.75rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <div class="pos-product-name">{{ $item->name }}</div>
                            <div class="pos-product-code">{{ $item->code }}</div>
                            <div class="pos-product-price">Rp {{ number_format($displayPrice, 0, ',', '.') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="pos-empty-grid">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <p style="font-weight: 500;">Barang tidak ditemukan</p>
                        <p style="font-size: 0.75rem; margin-top: 0.25rem;">Coba kata kunci atau kategori lain</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="pos-right">
            {{-- Body --}}
            <div class="pos-right-body">
                {{-- Top Right Icons & Date --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; color: #f59e0b;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <div x-data="{ editing: false }" style="color: #4b5563; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <div 
                            x-show="!editing" 
                            @click="editing = true; $nextTick(() => $refs.dateInput.focus())" 
                            style="cursor: pointer; font-weight: 500;"
                        >
                            {{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d M Y') }}
                        </div>
                        <input 
                            x-show="editing" 
                            x-ref="dateInput"
                            type="date" 
                            wire:model.live="date" 
                            @blur="editing = false"
                            @change="editing = false"
                            style="border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.125rem 0.25rem; font-size: 0.75rem;"
                        />
                    </div>
                </div>

                {{-- Form Fields --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                    <div class="pos-form-group">
                        <label class="pos-form-label" style="text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Pelanggan</label>
                        <input type="text" wire:model="customer_name" class="pos-form-input" placeholder="Nama pelanggan..." />
                    </div>
                    <div class="pos-form-group">
                        <label class="pos-form-label" style="text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Tujuan</label>
                        <input type="text" wire:model="destination" class="pos-form-input" placeholder="Tujuan pengiriman..." />
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div class="pos-form-group" style="width: 50%;">
                        <label class="pos-form-label">Tipe Harga</label>
                        <select wire:model.live="price_type_applied" class="pos-form-input">
                            <option value="retail">Retail</option>
                            <option value="grosir">Grosir</option>
                        </select>
                    </div>
                </div>

                <hr class="pos-divider" />

                {{-- Cart Header --}}
                <div class="pos-cart-header">
                    <div class="pos-cart-title">
                        Keranjang
                        @if(count($cartItems) > 0)
                            <span class="pos-cart-count">{{ count($cartItems) }}</span>
                        @endif
                    </div>
                    @if(count($cartItems) > 0)
                        <button type="button" wire:click="clearCart" class="pos-cart-clear" wire:confirm="Hapus semua item dari keranjang?">
                            Hapus Semua
                        </button>
                    @endif
                </div>

                {{-- Cart Items --}}
                <div class="pos-cart-items">
                    @forelse($cartItems as $index => $cartItem)
                        <div class="pos-cart-item" wire:key="cart-{{ $index }}">
                            <div class="pos-cart-item-info">
                                <div class="pos-cart-item-name" title="{{ $cartItem['name'] }}">{{ $cartItem['name'] }}</div>
                                <div class="pos-cart-item-price">
                                    Rp {{ number_format($cartItem['sell_price'], 0, ',', '.') }} / pcs
                                </div>
                            </div>

                            {{-- Quantity Control --}}
                            <div class="pos-cart-qty">
                                <button type="button" wire:click="updateCartQuantity({{ $index }}, {{ $cartItem['quantity'] - 1 }})">−</button>
                                <input
                                    type="number"
                                    value="{{ $cartItem['quantity'] }}"
                                    wire:change="updateCartQuantity({{ $index }}, $event.target.value)"
                                    min="1"
                                />
                                <button type="button" wire:click="updateCartQuantity({{ $index }}, {{ $cartItem['quantity'] + 1 }})">+</button>
                            </div>

                            {{-- Subtotal --}}
                            <div class="pos-cart-subtotal">
                                Rp {{ number_format($cartItem['subtotal'], 0, ',', '.') }}
                            </div>

                            {{-- Remove --}}
                            <button type="button" wire:click="removeFromCart({{ $index }})" class="pos-cart-remove" title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 0.875rem; height: 0.875rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <div class="pos-cart-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                            <p style="font-weight: 500;">Keranjang kosong</p>
                            <p style="font-size: 0.75rem; margin-top: 0.25rem;">Klik produk untuk menambahkan</p>
                        </div>
                    @endforelse
                </div>

                {{-- Notes --}}
                <div class="pos-form-group">
                    <label class="pos-form-label">Catatan</label>
                    <textarea wire:model="notes" class="pos-form-input" rows="2" placeholder="Catatan tambahan..." style="resize: vertical;"></textarea>
                </div>
            </div>

            {{-- Footer / Summary --}}
            <div class="pos-right-footer">
                @php
                    $totalAmount = $this->getTotalAmount();
                    $totalCogs = $this->getTotalCogs();
                    $totalQty = collect($cartItems)->sum('quantity');
                @endphp

                @php
                    $margin = $totalAmount - $totalCogs;
                @endphp
                <div class="pos-summary-row">
                    <span class="label">Subtotal ({{ count($cartItems) }} item)</span>
                    <span class="value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                </div>
                @if($this->showCostInfo())
                <div class="pos-summary-row">
                    <span class="label">Total HPP</span>
                    <span class="value">Rp {{ number_format($totalCogs, 0, ',', '.') }}</span>
                </div>
                <div class="pos-summary-row">
                    <span class="label">Margin</span>
                    <span class="value" style="color: #059669;">
                        {{ $margin > 0 ? 'Rp ' . number_format($margin, 0, ',', '.') : '-' }}
                    </span>
                </div>
                @endif

                <div class="pos-summary-total">
                    <span class="label">Total</span>
                    <span class="value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                </div>

                {{-- Action Buttons --}}
                <div class="pos-actions">
                    <a href="{{ $this->getPosCancelUrl() }}" class="pos-btn pos-btn-secondary" style="text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Batal
                    </a>
                    <button
                        type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="pos-btn pos-btn-primary"
                        {{ count($cartItems) === 0 ? 'disabled' : '' }}
                    >
                        <span wire:loading.remove wire:target="save">
                            <span style="display: flex; align-items: center; gap: 0.375rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Simpan
                            </span>
                        </span>
                        <span wire:loading wire:target="save" class="pos-loading">
                            <span style="display: flex; align-items: center; gap: 0.375rem;">
                                <x-filament::loading-indicator class="h-5 w-5" />
                                Menyimpan...
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
