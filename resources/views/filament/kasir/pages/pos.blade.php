@php
    $catalog = $this->getCatalog();
    $categories = $this->getCategoryList();
    $recentOrders = $this->getRecentOrders();
@endphp

<x-filament-panels::page>
<style>
    .kpos {
        --bg: #f4f4f6; --surface: #ffffff; --surface-2: #f0f0f3; --line: #e4e4e9;
        --text: #18181b; --muted: #71717a; --accent: #18181b; --accent-text: #ffffff;
        --ink: #1b1b1f; --danger: #e11d48; --ok: #059669; --warn: #b45309;
        color: var(--text);
        font-feature-settings: "tnum";
    }
    .dark .kpos {
        --bg: #111113; --surface: #1c1c1f; --surface-2: #26262a; --line: #2e2e33;
        --text: #f4f4f5; --muted: #a1a1aa; --accent: #ffffff; --accent-text: #111113;
        --ok: #34d399; --warn: #fbbf24; --danger: #fb7185;
    }
    .kpos * { box-sizing: border-box; }
    .kpos { --side: 400px; --top: 4rem; }
    .kpos-shell { display: block; height: calc(100vh - var(--top) - 3rem); min-height: 520px; padding-right: calc(var(--side) - 1rem); }
    .kpos-shell > .kpos-main { height: 100%; }
    .kpos-main { display: flex; flex-direction: column; gap: 14px; min-width: 0; min-height: 0; }
    .kpos-panel { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; }

    /* Search */
    .kpos-search { display: flex; gap: 10px; align-items: center; }
    .kpos-search-box { flex: 1; display: flex; align-items: center; gap: 10px; padding: 0 14px; height: 46px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
    .kpos-search-box:focus-within { border-color: var(--muted); }
    .kpos-search-box svg { width: 18px; height: 18px; color: var(--muted); flex: none; }
    .kpos-search-box input { flex: 1; border: 0; background: transparent; outline: none; box-shadow: none; color: var(--text); font-size: 15px; padding: 0; }
    .kpos-search-box input:focus { box-shadow: none; }
    .kpos-kbd { font-size: 11px; color: var(--muted); border: 1px solid var(--line); border-radius: 6px; padding: 1px 6px; }

    /* Categories */
    .kpos-cats { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; flex: none; max-height: 220px; overflow-y: auto; }
    .kpos-cat { position: relative; text-align: left; border-radius: 14px; padding: 12px 14px; min-height: 88px; display: flex; flex-direction: column; justify-content: space-between; color: var(--ink); border: 2px solid transparent; transition: transform .08s ease, border-color .08s; cursor: pointer; }
    .kpos-cat:hover { transform: translateY(-1px); }
    .kpos-cat.is-active { border-color: var(--ink); box-shadow: 0 0 0 2px var(--surface) inset; }
    .dark .kpos-cat.is-active { border-color: #fff; }
    .kpos-cat-icon { width: 28px; height: 28px; border-radius: 8px; background: rgba(0,0,0,.08); display: grid; place-items: center; font-weight: 700; font-size: 13px; }
    .kpos-cat-name { font-weight: 600; font-size: 15px; line-height: 1.2; margin-top: 10px; }
    .kpos-cat-count { font-size: 12px; opacity: .65; }

    /* Products */
    .kpos-products { flex: 1; min-height: 0; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; align-content: start; padding: 2px; }
    .kpos-prod { position: relative; display: flex; flex-direction: column; gap: 6px; padding: 12px 12px 10px 16px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; cursor: pointer; text-align: left; transition: background .1s, transform .08s; min-height: 132px; overflow: hidden; user-select: none; }
    .kpos-prod::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--tone); opacity: .9; }
    .kpos-prod:hover { transform: translateY(-1px); }
    .kpos-prod:active { transform: scale(.98); }
    .kpos-prod.in-cart { background: var(--tone); color: var(--ink); border-color: transparent; }
    .kpos-prod.in-cart .kpos-prod-meta { color: rgba(0,0,0,.6); }
    .kpos-prod.is-empty { opacity: .45; cursor: not-allowed; }
    .kpos-prod-meta { font-size: 11px; color: var(--muted); display: flex; justify-content: space-between; gap: 6px; }
    .kpos-prod-name { font-weight: 600; font-size: 14px; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .kpos-prod-price { font-size: 14px; font-weight: 500; }
    .kpos-prod-foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .kpos-prod-foot .kpos-prod-meta { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
    .kpos-prod-img { position: absolute; top: 10px; right: 10px; width: 34px; height: 34px; border-radius: 8px; object-fit: cover; }
    .kpos-qty { display: flex; align-items: center; gap: 8px; }
    .kpos-qty button { width: 28px; height: 28px; border-radius: 8px; border: 1.5px solid currentColor; display: grid; place-items: center; font-size: 16px; line-height: 1; background: transparent; color: inherit; cursor: pointer; }
    .kpos-qty button:disabled { opacity: .3; cursor: not-allowed; }
    .kpos-qty span { min-width: 18px; text-align: center; font-weight: 600; }
    .kpos-empty { grid-column: 1 / -1; text-align: center; color: var(--muted); padding: 48px 0; }

    /* Recent orders strip */
    .kpos-strip { flex: none; display: flex; gap: 10px; overflow-x: auto; padding: 10px 0 2px; border-top: 1px solid var(--line); }
    .kpos-order { flex: none; min-width: 210px; display: flex; gap: 10px; align-items: center; padding: 10px 12px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
    .kpos-order-avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--surface-2); display: grid; place-items: center; font-weight: 700; font-size: 12px; flex: none; }
    .kpos-order-title { font-weight: 600; font-size: 13px; }
    .kpos-order-sub { font-size: 11px; color: var(--muted); }
    .kpos-badge { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .kpos-badge.pending { background: #fde68a; color: #78350f; }
    .kpos-badge.selesai { background: #bbf7d0; color: #14532d; }
    .kpos-badge.voided { background: #fecdd3; color: #881337; }
    .kpos-strip-link { flex: none; align-self: center; font-size: 13px; color: var(--muted); padding: 0 8px; text-decoration: underline; }

    /* Cart */
    .kpos-cart { position: fixed; top: var(--top); right: 0; bottom: 0; width: var(--side); z-index: 10;
                 display: flex; flex-direction: column; min-height: 0; border-radius: 0; border-top: 0; border-right: 0; border-bottom: 0;
                 box-shadow: -8px 0 24px -16px rgba(0,0,0,.18); }
    .kpos-cart-top { flex: none; display: flex; flex-direction: column; gap: 10px; padding: 16px 16px 10px; }
    .kpos-cart-foot { flex: none; display: flex; flex-direction: column; gap: 10px; padding: 12px 16px 14px; border-top: 1px solid var(--line); background: var(--surface); }
    .kpos-cart-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .kpos-cart-title { font-size: 20px; font-weight: 700; }
    .kpos-input { width: 100%; height: 40px; border-radius: 10px; border: 1px solid var(--line); background: var(--surface-2); color: var(--text); padding: 0 12px; font-size: 14px; }
    .kpos-input:focus { outline: none; border-color: var(--muted); box-shadow: none; }
    .kpos-icon-btn { width: 34px; height: 34px; border-radius: 999px; border: 1px solid var(--line); display: grid; place-items: center; color: var(--muted); background: transparent; cursor: pointer; flex: none; }
    .kpos-icon-btn svg { width: 16px; height: 16px; }
    .kpos-lines { flex: 1 1 auto; min-height: 0; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding: 0 16px 12px; }
    .kpos-line { display: grid; grid-template-columns: 24px 1fr auto; gap: 10px; align-items: center; padding: 10px 12px; background: var(--surface-2); border-radius: 12px; }
    .kpos-line-no { width: 22px; height: 22px; border-radius: 999px; background: var(--accent); color: var(--accent-text); display: grid; place-items: center; font-size: 11px; font-weight: 700; }
    .kpos-line-name { font-size: 13.5px; font-weight: 500; line-height: 1.25; }
    .kpos-line-name small { color: var(--muted); font-weight: 400; }
    .kpos-line-ctrl { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
    .kpos-line-ctrl button { width: 24px; height: 24px; border-radius: 7px; border: 1px solid var(--line); background: var(--surface); color: var(--text); cursor: pointer; line-height: 1; }
    .kpos-line-ctrl input { width: 46px; height: 24px; text-align: center; border-radius: 7px; border: 1px solid var(--line); background: var(--surface); color: var(--text); font-size: 13px; padding: 0; }
    .kpos-line-total { font-weight: 600; font-size: 14px; text-align: right; }
    .kpos-line-remove { display: block; margin-left: auto; margin-top: 6px; font-size: 11px; color: var(--danger); background: none; border: 0; cursor: pointer; }
    .kpos-cart-empty { flex: 1; display: grid; place-items: center; text-align: center; color: var(--muted); font-size: 14px; padding: 24px; margin: 0 16px 12px; border: 1.5px dashed var(--line); border-radius: 14px; }

    .kpos-summary { background: var(--surface-2); border-radius: 14px; padding: 10px 14px; display: flex; flex-direction: column; gap: 4px; flex: none; }
    .kpos-row { display: flex; justify-content: space-between; font-size: 13.5px; }
    .kpos-row.muted { color: var(--muted); }
    .kpos-total { display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; margin-top: 4px; border-top: 1px solid var(--line); }
    .kpos-total span:first-child { font-size: 16px; }
    .kpos-total span:last-child { font-size: 26px; font-weight: 700; }

    .kpos-label { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
    .kpos-seg { display: grid; grid-template-columns: repeat(var(--n, 2), 1fr); gap: 6px; }
    .kpos-seg button { height: 38px; border-radius: 10px; border: 1px solid var(--line); background: transparent; color: var(--text); font-size: 13px; cursor: pointer; }
    .kpos-pay button { height: 42px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 13px; }
    .kpos-pay svg { width: 20px; height: 20px; }
    .kpos-seg button.is-on { background: var(--accent); color: var(--accent-text); border-color: var(--accent); }
    .kpos-quick { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
    .kpos-quick button { font-size: 12px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line); background: transparent; color: var(--text); cursor: pointer; }
    .kpos-change { display: flex; justify-content: space-between; font-size: 14px; margin-top: 8px; }
    .kpos-change.bad { color: var(--danger); }
    .kpos-change.good strong { color: var(--ok); }

    .kpos-submit { height: 52px; border-radius: 999px; border: 0; background: var(--accent); color: var(--accent-text); font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; flex: none; }
    .kpos-submit:disabled { opacity: .35; cursor: not-allowed; }
    .kpos-hint { font-size: 11px; color: var(--muted); text-align: center; }

    /* Modal konfirmasi */
    .kpos-modal { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 16px; background: rgba(0,0,0,.45); }
    .kpos-modal-box { width: 100%; max-width: 420px; background: var(--surface); color: var(--text); border-radius: 22px; padding: 28px 24px 24px; text-align: center; box-shadow: 0 24px 60px -20px rgba(0,0,0,.45); }
    .kpos-modal-icon { width: 56px; height: 56px; margin: 0 auto 12px; border-radius: 999px; display: grid; place-items: center; background: #fee2e2; color: #dc2626; }
    .kpos-modal-icon svg { width: 28px; height: 28px; }
    .kpos-modal-title { font-size: 20px; font-weight: 700; }
    .kpos-modal-text { font-size: 14px; color: var(--muted); margin-top: 6px; }
    .kpos-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
    .kpos-modal-btn { height: 88px; border-radius: 18px; border: 0; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; font-size: 14px; font-weight: 600; transition: transform .08s, filter .1s; }
    .kpos-modal-btn:hover { filter: brightness(.95); }
    .kpos-modal-btn:active { transform: scale(.97); }
    .kpos-modal-btn svg { width: 36px; height: 36px; }
    .kpos-modal-btn.no { background: #dc2626; color: #fff; }
    .kpos-modal-btn.yes { background: #059669; color: #fff; }
    .kpos-modal-btn small { font-size: 11px; font-weight: 400; opacity: .7; }

    @media (max-width: 1100px) {
        .kpos-shell { height: auto; padding-right: 0; display: flex; flex-direction: column; gap: 16px; }
        .kpos-products { max-height: 60vh; }
        .kpos-cart { position: static; width: auto; height: 80vh; min-height: 560px; border-radius: 18px; border: 1px solid var(--line); box-shadow: none; overflow: hidden; }
    }
</style>

<script>
    window.kasirPos = function (catalog, categories, paymentMethods, recent) {
        const palette = ['#cde9df', '#e3d3f2', '#c7e3ef', '#cfcdf4', '#f6c7dc', '#efdbe6', '#f5ccd5', '#c8ecd9', '#f6e0bf', '#d6e4c4'];
        const fmt = new Intl.NumberFormat('id-ID');

        return {
            catalog,
            categories,
            paymentMethods,
            recent,             // transaksi terakhir hari ini
            search: '',
            activeCategory: null,
            cart: [],           // [{ id, qty }]
            priceType: 'retail',
            payment: 'tunai',
            paid: '',
            customer: '',
            notes: '',
            showNotes: false,
            saving: false,
            confirmOpen: false, // modal konfirmasi kosongkan keranjang
            saleId: null,       // id unik per penjualan, mencegah transaksi tersimpan dobel

            newSaleId() {
                return window.crypto?.randomUUID?.() ?? (Date.now().toString(36) + Math.random().toString(36).slice(2));
            },

            init() {
                this.saleId = this.newSaleId();
                this.$nextTick(() => this.$refs.search?.focus());
                // Sidebar pesanan menempel tepat di bawah topbar Filament.
                const syncTop = () => {
                    const bar = document.querySelector('.fi-topbar');
                    if (bar) this.$el.style.setProperty('--top', bar.getBoundingClientRect().height + 'px');
                };
                syncTop();
                window.addEventListener('resize', syncTop);
            },

            // ---------- data helpers ----------
            get itemsById() {
                const map = {};
                for (const item of this.catalog) map[item.id] = item;
                return map;
            },
            toneFor(categoryId) {
                const index = this.categories.findIndex(c => c.id === categoryId);
                return palette[(index < 0 ? 0 : index) % palette.length];
            },
            categoryName(categoryId) {
                return this.categories.find(c => c.id === categoryId)?.name ?? '';
            },
            countIn(categoryId) {
                return this.catalog.filter(i => i.category_id === categoryId).length;
            },
            get filtered() {
                const q = this.search.trim().toLowerCase();
                return this.catalog
                    .filter(i => this.activeCategory === null || i.category_id === this.activeCategory)
                    .filter(i => !q
                        || i.name.toLowerCase().includes(q)
                        || (i.code ?? '').toLowerCase().includes(q)
                        || (i.barcode ?? '').toLowerCase().includes(q))
                    .slice(0, 150);
            },
            price(item) {
                return this.priceType === 'grosir' && item.grosir > 0 ? item.grosir : item.retail;
            },
            money(value) {
                return 'Rp' + fmt.format(Math.round(value || 0));
            },

            // ---------- cart ----------
            qtyOf(id) {
                return this.cart.find(l => l.id === id)?.qty ?? 0;
            },
            add(item, amount = 1) {
                if (item.stock <= 0) return this.toast(`${item.name} stoknya habis`, 'warning');
                const line = this.cart.find(l => l.id === item.id);
                const next = (line?.qty ?? 0) + amount;
                if (next > item.stock) return this.toast(`Stok ${item.name} tersisa ${item.stock}`, 'warning');
                if (line) line.qty = next; else this.cart.push({ id: item.id, qty: amount });
            },
            setQty(id, value) {
                const item = this.itemsById[id];
                let qty = parseInt(value, 10);
                if (!item) return;
                if (isNaN(qty) || qty <= 0) return this.remove(id);
                if (qty > item.stock) { qty = item.stock; this.toast(`Stok ${item.name} tersisa ${item.stock}`, 'warning'); }
                const line = this.cart.find(l => l.id === id);
                if (line) line.qty = qty;
            },
            dec(id) { this.setQty(id, this.qtyOf(id) - 1); },
            remove(id) { this.cart = this.cart.filter(l => l.id !== id); },
            clearCart() {
                if (!this.cart.length) return this.resetSale();
                this.confirmOpen = true;
            },
            confirmClear(yes) {
                this.confirmOpen = false;
                if (yes) this.resetSale();
                this.$nextTick(() => this.$refs.search?.focus());
            },
            resetSale() {
                this.cart = []; this.paid = ''; this.customer = ''; this.notes = ''; this.showNotes = false;
                this.saleId = this.newSaleId();
            },
            get lines() {
                return this.cart
                    .map(l => ({ ...l, item: this.itemsById[l.id] }))
                    .filter(l => l.item);
            },
            get totalQty() { return this.cart.reduce((s, l) => s + l.qty, 0); },
            get total() { return this.lines.reduce((s, l) => s + this.price(l.item) * l.qty, 0); },
            get paidNumber() { return parseInt(String(this.paid).replace(/\D/g, ''), 10) || 0; },
            get change() { return this.paidNumber - this.total; },
            get quickAmounts() {
                const t = this.total;
                if (t <= 0) return [];
                const set = new Set([t]);
                for (const step of [5000, 10000, 50000, 100000]) set.add(Math.ceil(t / step) * step);
                return [...set].sort((a, b) => a - b).slice(0, 4);
            },
            get canSubmit() {
                if (this.saving || !this.cart.length) return false;
                return this.payment !== 'tunai' || this.paidNumber >= this.total;
            },

            // ---------- search / scanner ----------
            onEnter() {
                const q = this.search.trim().toLowerCase();
                if (!q) return;
                const exact = this.catalog.find(i => (i.barcode ?? '').toLowerCase() === q || (i.code ?? '').toLowerCase() === q);
                const target = exact ?? (this.filtered.length === 1 ? this.filtered[0] : null);
                if (target) { this.add(target); this.search = ''; }
            },
            shortcut(e) {
                if (this.confirmOpen) {
                    if (e.key === 'Escape') { e.preventDefault(); this.confirmClear(false); }
                    if (e.key === 'Enter') { e.preventDefault(); this.confirmClear(true); }
                    return;
                }
                if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                    e.preventDefault(); this.$refs.search?.focus();
                }
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.submit(); }
            },

            // ---------- checkout ----------
            async submit() {
                if (!this.canSubmit) return;
                this.saving = true;
                try {
                    const result = await this.$wire.checkout(
                        this.cart.map(l => ({ id: l.id, qty: l.qty })),
                        this.priceType,
                        this.payment,
                        this.payment === 'tunai' ? this.paidNumber : null,
                        this.customer || null,
                        this.notes || null,
                        this.saleId,
                    );
                    if (result?.ok) {
                        const change = this.payment === 'tunai' ? this.change : 0;
                        this.toast(result.message + (change > 0 ? ` Kembalian ${this.money(change)}.` : ''), 'success');
                        if (result.catalog) this.catalog = result.catalog;
                        if (result.recent) this.recent = result.recent;
                        this.resetSale();
                        this.$nextTick(() => this.$refs.search?.focus());
                    } else {
                        this.toast(result?.message ?? 'Gagal menyimpan transaksi.', 'danger');
                    }
                } catch (e) {
                    this.toast('Koneksi ke server gagal. Coba lagi.', 'danger');
                } finally {
                    this.saving = false;
                }
            },

            toast(message, status = 'info') {
                if (window.FilamentNotification) {
                    new window.FilamentNotification().title(message)[status]?.().send();
                } else {
                    alert(message);
                }
            },
        };
    };
</script>

<div class="kpos"
     wire:ignore
     x-data="kasirPos(@js($catalog), @js($categories), @js(\App\Filament\Kasir\Pages\Pos::PAYMENT_METHODS), @js($recentOrders))"
     x-on:keydown.window="shortcut($event)">
    <div class="kpos-shell">
        {{-- ================= KIRI: cari, kategori, barang ================= --}}
        <div class="kpos-main">
            <div class="kpos-search">
                <label class="kpos-search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input x-ref="search" type="text" x-model="search" x-on:keydown.enter.prevent="onEnter()"
                           placeholder="Cari nama / kode, atau scan barcode lalu Enter" autocomplete="off">
                    <span class="kpos-kbd">/</span>
                </label>
            </div>

            <div class="kpos-cats">
                <button type="button" class="kpos-cat" style="background:#e4e4e7"
                        x-bind:class="{ 'is-active': activeCategory === null }"
                        x-on:click="activeCategory = null">
                    <span class="kpos-cat-icon">∗</span>
                    <span>
                        <span class="kpos-cat-name" style="display:block">Semua</span>
                        <span class="kpos-cat-count" style="display:block" x-text="catalog.length + ' barang'"></span>
                    </span>
                </button>
                <template x-for="cat in categories" x-bind:key="cat.id">
                    <button type="button" class="kpos-cat"
                            x-bind:style="{ background: toneFor(cat.id) }"
                            x-bind:class="{ 'is-active': activeCategory === cat.id }"
                            x-on:click="activeCategory = activeCategory === cat.id ? null : cat.id">
                        <span class="kpos-cat-icon" x-text="cat.name.charAt(0).toUpperCase()"></span>
                        <span>
                            <span class="kpos-cat-name" style="display:block" x-text="cat.name"></span>
                            <span class="kpos-cat-count" style="display:block" x-text="countIn(cat.id) + ' barang'"></span>
                        </span>
                    </button>
                </template>
            </div>

            <div class="kpos-products">
                <template x-for="item in filtered" x-bind:key="item.id">
                    <div class="kpos-prod" role="button" tabindex="0"
                         x-bind:style="{ '--tone': toneFor(item.category_id) }"
                         x-bind:class="{ 'in-cart': qtyOf(item.id) > 0, 'is-empty': item.stock <= 0 }"
                         x-on:click="add(item)" x-on:keydown.enter="add(item)">
                        <img x-show="item.image" x-bind:src="item.image" class="kpos-prod-img" alt="" loading="lazy">
                        <div class="kpos-prod-meta"><span x-text="categoryName(item.category_id)"></span></div>
                        <div class="kpos-prod-name" x-text="item.name" x-bind:style="item.image ? 'padding-right:40px' : ''"></div>
                        <div class="kpos-prod-price" x-text="money(price(item))"></div>
                        <div class="kpos-prod-foot">
                            <span class="kpos-prod-meta" x-text="item.stock > 0 ? item.stock + (item.unit ? ' ' + item.unit : '') : 'Habis'"
                                  x-bind:title="'Stok tersedia: ' + item.stock"></span>
                            <div class="kpos-qty" x-on:click.stop>
                                <button type="button" x-bind:disabled="qtyOf(item.id) === 0" x-on:click="dec(item.id)">−</button>
                                <span x-text="qtyOf(item.id)"></span>
                                <button type="button" x-bind:disabled="item.stock <= 0" x-on:click="add(item)">+</button>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="kpos-empty" x-show="filtered.length === 0">Barang tidak ditemukan.</div>
            </div>

            <div class="kpos-strip">
                <template x-for="order in recent" x-bind:key="order.id">
                    <div class="kpos-order">
                        <div class="kpos-order-avatar" x-text="order.no"></div>
                        <div style="min-width:0">
                            <div class="kpos-order-title" x-text="order.customer"></div>
                            <div class="kpos-order-sub" x-text="order.items + ' barang · ' + money(order.total) + ' · ' + order.time"></div>
                        </div>
                        <span class="kpos-badge" x-bind:class="order.status"
                              x-text="({ pending: 'Menunggu', selesai: 'Selesai', voided: 'Batal' })[order.status] ?? order.status"></span>
                        <a x-show="order.print_url" x-bind:href="order.print_url" target="_blank" class="kpos-strip-link" style="padding:0">Nota</a>
                    </div>
                </template>
                <div class="kpos-order-sub" x-show="!recent.length" style="padding:6px 2px">Belum ada transaksi hari ini.</div>
                <a x-show="recent.length" href="{{ \App\Filament\Kasir\Pages\RiwayatTransaksi::getUrl() }}" class="kpos-strip-link">Lihat semua →</a>
            </div>
        </div>

        {{-- ================= KANAN: pesanan ================= --}}
        <div class="kpos-panel kpos-cart">
            <div class="kpos-cart-top">
            <div class="kpos-cart-head">
                <div>
                    <div class="kpos-cart-title">Pesanan Baru</div>
                    <div class="kpos-order-sub" x-text="totalQty ? totalQty + ' barang di keranjang' : 'Klik barang untuk menambah'"></div>
                </div>
                <button type="button" class="kpos-icon-btn" title="Kosongkan keranjang" x-on:click="clearCart()" x-show="cart.length">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                </button>
            </div>

            <input type="text" class="kpos-input" x-model="customer" placeholder="Nama pelanggan (opsional)" maxlength="100">
            </div>

            <div class="kpos-lines" x-show="cart.length">
                <template x-for="(line, index) in lines" x-bind:key="line.id">
                    <div class="kpos-line">
                        <span class="kpos-line-no" x-text="index + 1"></span>
                        <div>
                            <div class="kpos-line-name">
                                <span x-text="line.item.name"></span>
                                <small x-text="' · ' + money(price(line.item))"></small>
                            </div>
                            <div class="kpos-line-ctrl">
                                <button type="button" x-on:click="dec(line.id)">−</button>
                                <input type="number" min="1" x-bind:max="line.item.stock" x-bind:value="line.qty"
                                       x-on:change="setQty(line.id, $event.target.value)">
                                <button type="button" x-on:click="add(line.item)">+</button>
                            </div>
                        </div>
                        <div>
                            <div class="kpos-line-total" x-text="money(price(line.item) * line.qty)"></div>
                            <button type="button" class="kpos-line-remove" x-on:click="remove(line.id)">Hapus</button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="kpos-cart-empty" x-show="!cart.length">
                <div>Keranjang kosong.<br><small>Klik barang, atau scan barcode di kolom cari.</small></div>
            </div>

            <div class="kpos-cart-foot">
            <div class="kpos-summary">
                @if (\App\Models\StoreSetting::hasFeature(\App\Enums\Feature::WholesalePrice))
                <div class="kpos-row muted"><span>Harga</span>
                    <span class="kpos-seg" style="--n:2; width: 170px">
                        <button type="button" x-bind:class="{ 'is-on': priceType === 'retail' }" x-on:click="priceType = 'retail'" style="height:28px">Eceran</button>
                        <button type="button" x-bind:class="{ 'is-on': priceType === 'grosir' }" x-on:click="priceType = 'grosir'" style="height:28px">Grosir</button>
                    </span>
                </div>
                @endif
                <div class="kpos-row muted"><span x-text="'Subtotal (' + totalQty + ' barang)'"></span><span x-text="money(total)"></span></div>
                <div class="kpos-total"><span>Total</span><span x-text="money(total)"></span></div>
            </div>

            <div>
                <div class="kpos-label">Metode Pembayaran</div>
                <div class="kpos-seg kpos-pay" style="--n:3">
                    <button type="button" x-bind:class="{ 'is-on': payment === 'tunai' }" x-on:click="payment = 'tunai'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        Tunai
                    </button>
                    <button type="button" x-bind:class="{ 'is-on': payment === 'debit' }" x-on:click="payment = 'debit'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                        Debit
                    </button>
                    <button type="button" x-bind:class="{ 'is-on': payment === 'qris' }" x-on:click="payment = 'qris'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" /></svg>
                        QRIS
                    </button>
                </div>

                <div x-show="payment === 'tunai'" style="margin-top:10px">
                    <input type="text" inputmode="numeric" class="kpos-input" placeholder="Uang diterima"
                           x-bind:value="paidNumber ? 'Rp' + new Intl.NumberFormat('id-ID').format(paidNumber) : ''"
                           x-on:input="paid = $event.target.value"
                           x-on:keydown.enter.prevent="submit()">
                    <div class="kpos-quick">
                        <template x-for="amount in quickAmounts" x-bind:key="amount">
                            <button type="button" x-on:click="paid = String(amount)" x-text="amount === total ? 'Uang pas' : money(amount)"></button>
                        </template>
                    </div>
                    <div class="kpos-change" x-show="paidNumber > 0"
                         x-bind:class="change < 0 ? 'bad' : 'good'">
                        <span x-text="change < 0 ? 'Kurang' : 'Kembalian'"></span>
                        <strong x-text="money(Math.abs(change))"></strong>
                    </div>
                </div>
            </div>

            <div>
                <button type="button" class="kpos-quick" style="border:0;background:none;padding:0;color:var(--muted);font-size:12px;cursor:pointer"
                        x-on:click="showNotes = !showNotes" x-text="showNotes ? '− Sembunyikan catatan' : '+ Tambah catatan'"></button>
                <textarea x-show="showNotes" x-model="notes" class="kpos-input" style="height:60px;padding:8px 12px;margin-top:6px" maxlength="500" placeholder="Catatan transaksi"></textarea>
            </div>

            <button type="button" class="kpos-submit" x-bind:disabled="!canSubmit" x-on:click="submit()">
                <span x-show="!saving">Simpan Transaksi</span>
                <span x-show="saving">Menyimpan…</span>
            </button>
            <div class="kpos-hint">Ctrl+Enter untuk simpan</div>
            </div>
        </div>

        {{-- ================= Modal konfirmasi kosongkan keranjang ================= --}}
        <div class="kpos-modal" x-show="confirmOpen" x-cloak x-transition.opacity
             x-on:click.self="confirmClear(false)" role="dialog" aria-modal="true" style="display:none">
            <div class="kpos-modal-box" x-show="confirmOpen" x-transition.scale.95>
                <div class="kpos-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                </div>
                <div class="kpos-modal-title">Kosongkan keranjang?</div>
                <div class="kpos-modal-text" x-text="totalQty + ' barang senilai ' + money(total) + ' akan dihapus dari pesanan.'"></div>
                <div class="kpos-modal-actions">
                    <button type="button" class="kpos-modal-btn no" x-on:click="confirmClear(false)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        Batal <small>Esc</small>
                    </button>
                    <button type="button" class="kpos-modal-btn yes" x-on:click="confirmClear(true)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Ya, kosongkan <small>Enter</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</x-filament-panels::page>
