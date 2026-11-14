@php
    $tables = $this->getTables();
    $posUrl = \App\Filament\Kasir\Pages\Pos::getUrl();
@endphp

<x-filament-panels::page>
<style>
    .kmeja {
        --surface: #ffffff; --line: #ececec; --text: #1f2328; --muted: #8a8f98;
        /* Kosong = merah, terisi = hijau */
        --free: #fde0e0; --free-chair: #fbcaca; --free-text: #9b1c1c;
        --busy: #d5f5e3; --busy-chair: #bfeed4; --busy-text: #14532d; --busy-chip: #b4e9cb;
        --accent: #f97316;
        color: var(--text);
    }
    .dark .kmeja {
        --surface: #1c1c1f; --line: #2e2e33; --text: #f4f4f5; --muted: #a1a1aa;
        --free: #4a1d1f; --free-chair: #5e2427; --free-text: #fecaca;
        --busy: #14382a; --busy-chair: #1a4734; --busy-text: #a7f3d0; --busy-chip: #1f5a40;
    }
    .kmeja * { box-sizing: border-box; }
    .kmeja-head { display: flex; align-items: end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 6px; }
    .kmeja-title { font-size: 22px; font-weight: 700; }
    .kmeja-sum { display: flex; gap: 14px; font-size: 13px; color: var(--muted); }
    .kmeja-dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }

    .kmeja-panel { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 20px; }
    .kmeja-tools { display: flex; gap: 10px; justify-content: space-between; flex-wrap: wrap; margin-bottom: 22px; }
    .kmeja-search { flex: 1; max-width: 320px; height: 40px; display: flex; align-items: center; gap: 8px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; }
    .kmeja-search svg { width: 16px; height: 16px; color: var(--muted); }
    .kmeja-search input { border: 0; outline: none; background: transparent; flex: 1; color: var(--text); font-size: 14px; padding: 0; box-shadow: none; }
    .kmeja-filter { display: flex; gap: 6px; }
    .kmeja-filter button { height: 40px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--line); background: transparent; color: var(--text); font-size: 13px; cursor: pointer; }
    .kmeja-filter button.is-on { border-color: var(--accent); color: var(--accent); font-weight: 600; }

    .kmeja-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 30px 26px; padding: 6px 10px; }
    /* Meja + kursi di keempat sisi */
    .kmeja-table { position: relative; padding: 12px 14px; cursor: pointer; background: none; border: 0; text-align: left; color: inherit; }
    .kmeja-table::before, .kmeja-table::after,
    .kmeja-chair-l, .kmeja-chair-r { content: ""; position: absolute; background: var(--chair); border-radius: 6px; }
    .kmeja-table::before { top: 0; left: 50%; width: 36px; height: 8px; transform: translateX(-50%); }
    .kmeja-table::after { bottom: 0; left: 50%; width: 36px; height: 8px; transform: translateX(-50%); }
    .kmeja-chair-l { left: 0; top: 50%; width: 8px; height: 36px; transform: translateY(-50%); }
    .kmeja-chair-r { right: 0; top: 50%; width: 8px; height: 36px; transform: translateY(-50%); }
    .kmeja-top { position: relative; aspect-ratio: 1 / 1; border-radius: 14px; background: var(--top); color: var(--ink); padding: 14px; display: flex; flex-direction: column; gap: 6px; transition: transform .08s; }
    .kmeja-table:hover .kmeja-top { transform: translateY(-2px); }
    .kmeja-table.free { --top: var(--free); --chair: var(--free-chair); --ink: var(--free-text); }
    .kmeja-table.busy { --top: var(--busy); --chair: var(--busy-chair); --ink: var(--busy-text); }
    .kmeja-no { font-size: 18px; font-weight: 700; line-height: 1; }
    .kmeja-name { font-size: 12px; opacity: .75; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .kmeja-chips { margin-top: auto; display: flex; flex-wrap: wrap; gap: 5px; }
    .kmeja-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 7px; border-radius: 7px; background: var(--busy-chip); }
    .kmeja-chip svg { width: 12px; height: 12px; }
    .kmeja-free-label { margin-top: auto; font-size: 12px; font-weight: 600; opacity: .8; }
    .kmeja-empty { text-align: center; color: var(--muted); padding: 48px 0; }

    /* Detail meja (modal) */
    .kmeja-modal { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 16px; background: rgba(0,0,0,.45); }
    .kmeja-box { width: 100%; max-width: 440px; background: var(--surface); border-radius: 20px; padding: 22px; color: var(--text); }
    .kmeja-box h3 { font-size: 20px; font-weight: 700; margin: 0; }
    .kmeja-orders { margin: 16px 0; display: flex; flex-direction: column; gap: 8px; max-height: 280px; overflow-y: auto; }
    .kmeja-order { display: flex; justify-content: space-between; gap: 10px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 13.5px; }
    .kmeja-order small { color: var(--muted); display: block; }
    .kmeja-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 16px; padding-top: 10px; border-top: 1px solid var(--line); }
    .kmeja-actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 18px; }
    .kmeja-btn { height: 46px; border-radius: 12px; border: 1px solid var(--line); background: transparent; color: var(--text); font-weight: 600; font-size: 13px; cursor: pointer; display: grid; place-items: center; text-decoration: none; }
    .kmeja-btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
    .kmeja-btn.ok { background: #059669; border-color: #059669; color: #fff; }
</style>

<div class="kmeja"
     wire:poll.20s
     x-data="{
        search: '',
        status: 'all',
        now: Date.now(),
        detail: null,
        init() { setInterval(() => this.now = Date.now(), 30000); },
        match(t) {
            const q = this.search.trim().toLowerCase();
            const okQ = !q || t.number.toLowerCase().includes(q) || (t.name ?? '').toLowerCase().includes(q) || (t.customer ?? '').toLowerCase().includes(q);
            const okS = this.status === 'all' || (this.status === 'busy' ? t.occupied : !t.occupied);
            return okQ && okS;
        },
        duration(since) {
            if (!since) return '';
            const m = Math.max(0, Math.floor((this.now - new Date(since).getTime()) / 60000));
            return Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0');
        },
        money(v) { return 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(v || 0)); },
     }">
    @php
        $busy = collect($tables)->where('occupied', true)->count();
        $free = count($tables) - $busy;
    @endphp

    <div class="kmeja-head">
        <div class="kmeja-title">Meja</div>
        <div class="kmeja-sum">
            <span><i class="kmeja-dot" style="background:var(--free)"></i>{{ $free }} kosong</span>
            <span><i class="kmeja-dot" style="background:var(--busy)"></i>{{ $busy }} terisi</span>
        </div>
    </div>

    <div class="kmeja-panel">
        <div class="kmeja-tools">
            <label class="kmeja-search">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="text" x-model="search" placeholder="Cari meja atau nama pelanggan">
            </label>
            <div class="kmeja-filter">
                <button type="button" x-bind:class="{ 'is-on': status === 'all' }" x-on:click="status = 'all'">Semua</button>
                <button type="button" x-bind:class="{ 'is-on': status === 'free' }" x-on:click="status = 'free'">Kosong</button>
                <button type="button" x-bind:class="{ 'is-on': status === 'busy' }" x-on:click="status = 'busy'">Terisi</button>
            </div>
        </div>

        @if (empty($tables))
            <div class="kmeja-empty">Belum ada meja aktif. Minta admin menambahkan meja di menu <strong>Master Meja</strong>.</div>
        @else
            <div class="kmeja-grid">
                @foreach ($tables as $t)
                    @php $json = \Illuminate\Support\Js::from($t); @endphp
                    <button type="button" wire:key="meja-{{ $t['id'] }}"
                            class="kmeja-table {{ $t['occupied'] ? 'busy' : 'free' }}"
                            x-show="match({{ $json }})"
                            @if ($t['occupied'])
                                x-on:click="detail = {{ $json }}"
                            @else
                                x-on:click="window.location.href = @js($posUrl . '?meja=' . $t['id'])"
                                title="Buat pesanan untuk meja ini"
                            @endif>
                        <span class="kmeja-chair-l"></span><span class="kmeja-chair-r"></span>
                        <span class="kmeja-top">
                            <span class="kmeja-no">{{ $t['number'] }}</span>
                            @if ($t['name'])
                                <span class="kmeja-name">{{ $t['name'] }}</span>
                            @endif

                            @if ($t['occupied'])
                                <span class="kmeja-chips">
                                    <span class="kmeja-chip" x-text="money({{ $t['total'] }})"></span>
                                    <span class="kmeja-chip">
                                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        <span x-text="duration(@js($t['since']))"></span>
                                    </span>
                                    @if ($t['capacity'])
                                        <span class="kmeja-chip">
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                                            {{ $t['capacity'] }}
                                        </span>
                                    @endif
                                </span>
                            @else
                                <span class="kmeja-free-label">Kosong{{ $t['capacity'] ? ' · ' . $t['capacity'] . ' kursi' : '' }}</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Detail meja terisi --}}
    <div class="kmeja-modal" x-show="detail" x-cloak x-transition.opacity x-on:click.self="detail = null"
         x-on:keydown.escape.window="detail = null" style="display:none">
        <div class="kmeja-box" x-show="detail">
            <template x-if="detail">
                <div>
                    <h3 x-text="'Meja ' + detail.number + (detail.name ? ' · ' + detail.name : '')"></h3>
                    <div style="font-size:13px;color:var(--muted);margin-top:4px" x-text="'Terisi sejak ' + (detail.orders[0]?.time ?? '-') + ' · ' + duration(detail.since) + ' jam'"></div>
                    <div class="kmeja-orders">
                        <template x-for="o in detail.orders" x-bind:key="o.code">
                            <div class="kmeja-order">
                                <div><span x-text="o.customer || 'Pelanggan umum'"></span><small x-text="o.code + ' · ' + o.time"></small></div>
                                <strong x-text="money(o.total)"></strong>
                            </div>
                        </template>
                    </div>
                    <div class="kmeja-total"><span>Total</span><span x-text="money(detail.total)"></span></div>
                    <div class="kmeja-actions">
                        <button type="button" class="kmeja-btn" x-on:click="detail = null">Tutup</button>
                        <a class="kmeja-btn primary" x-bind:href="@js($posUrl) + '?meja=' + detail.id">Tambah Pesanan</a>
                        <button type="button" class="kmeja-btn ok"
                                x-on:click="$wire.releaseTable(detail.id); detail = null">Kosongkan</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
</x-filament-panels::page>
