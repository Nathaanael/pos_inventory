@php
    $steps = \App\Filament\Pages\SetupWizard::STEPS;
    $selected = $this->getSelectedBusiness();
    $setting = \App\Models\StoreSetting::current();
    $existingLogo = $setting->logoUrl();
    $headings = [
        1 => ['Identitas Toko', 'Nama dan kontak ini tampil di aplikasi dan dicetak di struk.'],
        2 => ['Jenis Usaha', 'Usaha apa yang Anda jalankan? Fitur akan disesuaikan otomatis.'],
        3 => ['Fitur', 'Fitur sudah dicentang sesuai jenis usaha. Ubah bila perlu.'],
        4 => ['Ringkasan', 'Periksa kembali sebelum mulai menggunakan aplikasi.'],
    ];
@endphp

<div class="sw">
<style>
    .sw {
        --bg: #1f4f8f; --card: #ffffff; --side: #ffffff; --main: #f5f8fb; --line: #e3e9f0;
        --text: #1a2433; --muted: #6b7a8c; --faint: #a9b4c2;
        --accent: #1556b8; --accent-soft: #e8f0fb; --ok: #2bb673; --danger: #d92d20;
        min-height: 100vh; display: grid; place-items: center; padding: 40px 16px;
        background: linear-gradient(180deg, #1f4f8f 0%, #1a4479 100%);
        color: var(--text); font-feature-settings: "tnum";
    }
    .dark .sw {
        --card: #161b22; --side: #161b22; --main: #0f1318; --line: #26303b;
        --text: #e6edf3; --muted: #93a1b0; --faint: #5b6876; --accent: #5aa2ff; --accent-soft: #15243a;
        background: linear-gradient(180deg, #0b2747 0%, #071a30 100%);
    }
    .sw * { box-sizing: border-box; }
    .sw-card { width: 100%; max-width: 1200px; min-height: 680px; display: grid; grid-template-columns: 260px 1fr; background: var(--card); border-radius: 22px; overflow: hidden; box-shadow: 0 30px 80px -30px rgba(4, 30, 66, .55); }

    /* ---------- Kiri: pelacak langkah ---------- */
    .sw-side { background: var(--side); border-right: 1px solid var(--line); padding: 32px 28px; display: flex; flex-direction: column; }
    .sw-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 20px; color: var(--accent); }
    .sw-brand img { height: 32px; width: auto; }
    .sw-brand-mark { width: 32px; height: 32px; border-radius: 9px; background: var(--accent); color: #fff; display: grid; place-items: center; font-size: 15px; }
    .sw-side-title { margin-top: 96px; font-size: 18px; font-weight: 700; }
    .sw-side-sub { margin-top: 6px; font-size: 13px; color: var(--muted); }
    .sw-steps { margin-top: 44px; display: flex; flex-direction: column; gap: 4px; list-style: none; padding: 0; }
    .sw-steps button { width: 100%; display: flex; align-items: center; gap: 12px; padding: 9px 0; background: none; border: 0; text-align: left; font-size: 13.5px; color: var(--faint); cursor: default; }
    .sw-step-ico { width: 18px; height: 18px; display: grid; place-items: center; flex: none; }
    .sw-step-ico svg { width: 14px; height: 14px; }
    .sw-steps .is-done button { color: var(--ok); cursor: pointer; }
    .sw-steps .is-done .sw-step-ico { background: rgba(43, 182, 115, .14); border-radius: 999px; }
    .sw-steps .is-current button { color: var(--text); font-weight: 600; }
    .sw-side-foot { margin-top: auto; font-size: 12px; color: var(--faint); display: flex; flex-direction: column; gap: 10px; }

    /* ---------- Kanan: isi ---------- */
    .sw-main { background: var(--main); padding: 40px 56px 36px 100px; display: flex; flex-direction: column; position: relative; min-width: 0; }
    .sw-close { position: absolute; top: 40px; right: 36px; display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px; border-radius: 999px; border: 1.5px solid var(--line); background: transparent; color: var(--muted); font-size: 13px; cursor: pointer; }
    .sw-close:hover { border-color: var(--muted); color: var(--text); }
    .sw-close svg { width: 15px; height: 15px; }
    .sw-title { font-size: 26px; font-weight: 700; margin: 26px 0 0; }
    .sw-sub { margin-top: 10px; font-size: 14.5px; color: var(--muted); }
    .sw-body { margin-top: 34px; max-width: 720px; flex: 1; }

    .sw-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .sw-opt { position: relative; display: flex; align-items: center; gap: 16px; padding: 18px 18px; min-height: 76px; background: var(--card); border: 1.5px solid transparent; border-radius: 10px; text-align: left; cursor: pointer; box-shadow: 0 1px 3px rgba(16, 40, 72, .08); transition: border-color .1s, transform .08s; color: var(--text); }
    .sw-opt:hover { border-color: var(--line); }
    .sw-opt:active { transform: scale(.99); }
    .sw-opt-ico { width: 38px; height: 38px; flex: none; border-radius: 8px; background: var(--main); display: grid; place-items: center; color: var(--text); }
    .sw-opt-ico svg { width: 21px; height: 21px; }
    .sw-opt-name { font-weight: 600; font-size: 14.5px; }
    .sw-opt-desc { font-size: 12.5px; color: var(--muted); margin-top: 3px; line-height: 1.35; }
    .sw-opt.is-on { border-color: var(--accent); }
    .sw-opt.is-on .sw-opt-name { color: var(--accent); }
    .sw-opt.is-on .sw-opt-ico { background: var(--accent); color: #fff; }
    .sw-opt-check { position: absolute; top: -9px; right: -9px; width: 22px; height: 22px; border-radius: 999px; background: var(--accent); color: #fff; display: none; place-items: center; border: 2px solid var(--main); }
    .sw-opt-check svg { width: 12px; height: 12px; }
    .sw-opt.is-on .sw-opt-check { display: grid; }
    .sw-box { margin-left: auto; width: 22px; height: 22px; flex: none; border-radius: 6px; border: 1.5px solid var(--faint); display: grid; place-items: center; color: transparent; }
    .sw-box svg { width: 14px; height: 14px; }
    .sw-opt.is-on .sw-box { background: var(--accent); border-color: var(--accent); color: #fff; }

    .sw-field { display: flex; flex-direction: column; gap: 7px; }
    .sw-field.full { grid-column: 1 / -1; }
    .sw-field label { font-size: 13px; font-weight: 600; }
    .sw-field label span { color: var(--danger); }
    .sw-input { width: 100%; height: 46px; padding: 0 14px; border-radius: 10px; border: 1.5px solid var(--line); background: var(--card); color: var(--text); font-size: 14.5px; }
    textarea.sw-input { height: 84px; padding: 12px 14px; resize: vertical; }
    .sw-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
    .sw-err { font-size: 12.5px; color: var(--danger); }
    .sw-hint { font-size: 12px; color: var(--muted); }
    .sw-upload { display: flex; align-items: center; gap: 16px; padding: 14px; border: 1.5px dashed var(--line); border-radius: 10px; background: var(--card); }
    .sw-upload-prev { width: 56px; height: 56px; border-radius: 10px; background: var(--main); display: grid; place-items: center; overflow: hidden; flex: none; color: var(--faint); }
    .sw-upload-prev img { width: 100%; height: 100%; object-fit: contain; }
    .sw-upload-prev svg { width: 24px; height: 24px; }
    .sw-link { background: none; border: 0; padding: 0; color: var(--accent); font-size: 13px; font-weight: 600; cursor: pointer; }
    .sw-link.danger { color: var(--danger); }

    .sw-note { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; background: var(--accent-soft); color: var(--accent); font-size: 13px; }
    .sw-note svg { width: 18px; height: 18px; flex: none; margin-top: 1px; }

    .sw-sum { background: var(--card); border-radius: 12px; box-shadow: 0 1px 3px rgba(16, 40, 72, .08); }
    .sw-sum-row { display: grid; grid-template-columns: 170px 1fr auto; gap: 16px; align-items: start; padding: 16px 20px; border-bottom: 1px solid var(--line); font-size: 14px; }
    .sw-sum-row:last-child { border-bottom: 0; }
    .sw-sum-row dt { color: var(--muted); }
    .sw-sum-row dd { margin: 0; font-weight: 500; }
    .sw-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .sw-chip { font-size: 12px; padding: 3px 10px; border-radius: 999px; background: var(--accent-soft); color: var(--accent); font-weight: 600; }
    .sw-chip.off { background: var(--main); color: var(--faint); text-decoration: line-through; font-weight: 400; }

    .sw-actions { display: flex; align-items: center; gap: 20px; margin-top: 36px; }
    .sw-back { display: inline-flex; align-items: center; gap: 8px; background: none; border: 0; color: var(--accent); font-size: 14px; font-weight: 600; cursor: pointer; padding: 0 4px; }
    .sw-back svg { width: 15px; height: 15px; }
    .sw-next { height: 44px; padding: 0 26px; border-radius: 999px; border: 0; background: var(--accent); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .sw-next:hover { filter: brightness(1.08); }
    .sw-next[disabled] { opacity: .6; cursor: wait; }

    @media (max-width: 900px) {
        .sw { padding: 0; place-items: stretch; }
        .sw-card { grid-template-columns: 1fr; border-radius: 0; min-height: 100vh; }
        .sw-side { padding: 20px 16px; border-right: 0; border-bottom: 1px solid var(--line); }
        .sw-side-title, .sw-side-sub, .sw-side-foot { display: none; }
        .sw-steps { margin-top: 16px; flex-direction: row; gap: 14px; overflow-x: auto; }
        .sw-steps button { white-space: nowrap; }
        .sw-main { padding: 24px 16px; }
        .sw-close { top: 20px; right: 16px; }
        .sw-title { margin-top: 48px; }
        .sw-grid { grid-template-columns: 1fr; }
        .sw-sum-row { grid-template-columns: 1fr; gap: 4px; }
    }
</style>

{{-- Pilihan jenis usaha & fitur diproses di browser (instan); dikirim ke server saat "Lanjut". --}}
<div class="sw-card"
     x-data="{
         businessType: $wire.entangle('businessType'),
         features: $wire.entangle('features'),
         presets: @js(collect(\App\Enums\BusinessType::cases())->mapWithKeys(fn ($t) => [$t->value => array_map(fn ($f) => $f->value, $t->defaultFeatures())])),
         pick(type) {
             if (this.businessType === type) return;
             this.businessType = type;
             this.features = [...this.presets[type]];
         },
         toggle(feature) {
             this.features = this.features.includes(feature)
                 ? this.features.filter(f => f !== feature)
                 : [...this.features, feature];
         },
     }">
    {{-- ======================= KIRI: pelacak langkah ======================= --}}
    <aside class="sw-side">
        <div class="sw-brand">
            @if ($existingLogo)
                <img src="{{ $existingLogo }}" alt="">
            @else
                <span class="sw-brand-mark">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
                </span>
                <span>Setup</span>
            @endif
        </div>

        <div class="sw-side-title">Setup Toko</div>
        <div class="sw-side-sub">Siapkan toko Anda dalam beberapa langkah.</div>

        <ol class="sw-steps">
            @foreach ($steps as $number => $label)
                @php $state = $number < $step ? 'is-done' : ($number === $step ? 'is-current' : ''); @endphp
                <li class="{{ $state }}">
                    <button type="button" wire:click="goTo({{ $number }})" @disabled($number >= $step)>
                        <span class="sw-step-ico">
                            @if ($number < $step)
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @elseif ($number === $step)
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            @endif
                        </span>
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ol>

        <div class="sw-side-foot">
            <span>Semua pengaturan bisa diubah lagi di menu Pengaturan Toko.</span>
            <span>&copy; {{ now()->year }}</span>
        </div>
    </aside>

    {{-- ======================= KANAN: isi langkah ======================= --}}
    <main class="sw-main">
        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <button type="submit" class="sw-close" title="Keluar dari akun">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                Keluar
            </button>
        </form>

        <h1 class="sw-title">{{ $headings[$step][0] }}</h1>
        <div class="sw-sub">{{ $headings[$step][1] }}</div>

        <div class="sw-body">
            {{-- ---------- 1. Identitas toko ---------- --}}
            @if ($step === 1)
                <div class="sw-grid">
                    <div class="sw-field">
                        <label for="sw-name">Nama toko <span>*</span></label>
                        <input id="sw-name" type="text" class="sw-input" wire:model="store_name" maxlength="100" placeholder="mis. Toko Makmur Jaya" autofocus>
                        @error('store_name') <div class="sw-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="sw-field">
                        <label for="sw-tagline">Keterangan singkat</label>
                        <input id="sw-tagline" type="text" class="sw-input" wire:model="tagline" maxlength="150" placeholder="mis. Bangunan & Listrik">
                        @error('tagline') <div class="sw-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="sw-field">
                        <label for="sw-phone">Telepon / WhatsApp</label>
                        <input id="sw-phone" type="tel" class="sw-input" wire:model="phone" maxlength="30" placeholder="08xx">
                        @error('phone') <div class="sw-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="sw-field">
                        <label>Logo</label>
                        <div class="sw-upload">
                            <div class="sw-upload-prev">
                                @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                                    <img src="{{ $logo->temporaryUrl() }}" alt="">
                                @elseif ($existingLogo)
                                    <img src="{{ $existingLogo }}" alt="">
                                @else
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" /></svg>
                                @endif
                            </div>
                            <div style="display:flex;flex-direction:column;gap:4px">
                                <label class="sw-link" style="cursor:pointer">
                                    <input type="file" wire:model="logo" accept="image/*" hidden>
                                    {{ $logo || $existingLogo ? 'Ganti logo' : 'Pilih logo' }}
                                </label>
                                @if ($logo)
                                    <button type="button" class="sw-link danger" wire:click="removeLogo" style="text-align:left">Hapus</button>
                                @endif
                                <span class="sw-hint" wire:loading wire:target="logo">Mengunggah…</span>
                                <span class="sw-hint" wire:loading.remove wire:target="logo">Opsional, maks 1 MB</span>
                            </div>
                        </div>
                        @error('logo') <div class="sw-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="sw-field full">
                        <label for="sw-address">Alamat</label>
                        <textarea id="sw-address" class="sw-input" wire:model="address" maxlength="500" placeholder="Alamat lengkap toko (dicetak di struk)"></textarea>
                        @error('address') <div class="sw-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif

            {{-- ---------- 2. Jenis usaha ---------- --}}
            @if ($step === 2)
                <div class="sw-grid">
                    @foreach (\App\Enums\BusinessType::cases() as $type)
                        <button type="button" class="sw-opt"
                                x-bind:class="{ 'is-on': businessType === '{{ $type->value }}' }"
                                x-on:click="pick('{{ $type->value }}')">
                            <span class="sw-opt-ico">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $type->iconPath() }}" /></svg>
                            </span>
                            <span>
                                <span class="sw-opt-name" style="display:block">{{ $type->label() }}</span>
                                <span class="sw-opt-desc" style="display:block">{{ $type->description() }}</span>
                            </span>
                            <span class="sw-opt-check">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                        </button>
                    @endforeach
                </div>
                @error('businessType') <div class="sw-err" style="margin-top:14px" x-show="!businessType">{{ $message }}</div> @enderror
            @endif

            {{-- ---------- 3. Fitur ---------- --}}
            @if ($step === 3)
                @if ($selected)
                    <div class="sw-note">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        <span>Dicentang otomatis untuk <strong>{{ $selected->label() }}</strong>. Mematikan fitur hanya menyembunyikannya; data tidak dihapus.</span>
                    </div>
                @endif
                <div class="sw-grid">
                    @foreach (\App\Enums\Feature::cases() as $feature)
                        <button type="button" class="sw-opt"
                                x-bind:class="{ 'is-on': features.includes('{{ $feature->value }}') }"
                                x-bind:aria-pressed="features.includes('{{ $feature->value }}')"
                                x-on:click="toggle('{{ $feature->value }}')">
                            <span class="sw-opt-ico">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature->iconPath() }}" /></svg>
                            </span>
                            <span style="min-width:0">
                                <span class="sw-opt-name" style="display:block">{{ $feature->label() }}</span>
                                <span class="sw-opt-desc" style="display:block">{{ $feature->description() }}</span>
                            </span>
                            <span class="sw-box">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- ---------- 4. Ringkasan ---------- --}}
            @if ($step === 4)
                <dl class="sw-sum">
                    <div class="sw-sum-row">
                        <dt>Nama toko</dt>
                        <dd>{{ $store_name }}@if ($tagline) <span style="color:var(--muted);font-weight:400"> · {{ $tagline }}</span>@endif</dd>
                        <button type="button" class="sw-link" wire:click="goTo(1)">Ubah</button>
                    </div>
                    <div class="sw-sum-row">
                        <dt>Kontak & alamat</dt>
                        <dd>{{ $phone ?: '–' }}<br><span style="color:var(--muted);font-weight:400">{{ $address ?: 'Alamat belum diisi' }}</span></dd>
                        <button type="button" class="sw-link" wire:click="goTo(1)">Ubah</button>
                    </div>
                    <div class="sw-sum-row">
                        <dt>Jenis usaha</dt>
                        <dd>{{ $selected?->label() ?? '–' }}</dd>
                        <button type="button" class="sw-link" wire:click="goTo(2)">Ubah</button>
                    </div>
                    <div class="sw-sum-row">
                        <dt>Fitur</dt>
                        <dd class="sw-chips">
                            @foreach (\App\Enums\Feature::cases() as $feature)
                                <span class="sw-chip {{ in_array($feature->value, $features, true) ? '' : 'off' }}">{{ $feature->label() }}</span>
                            @endforeach
                        </dd>
                        <button type="button" class="sw-link" wire:click="goTo(3)">Ubah</button>
                    </div>
                </dl>
            @endif
        </div>

        <div class="sw-actions">
            @if ($step > 1)
                <button type="button" class="sw-back" wire:click="back">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Kembali
                </button>
            @endif

            @if ($step < count($steps))
                <button type="button" class="sw-next" wire:click="next" wire:loading.attr="disabled" wire:target="next,logo">Lanjut</button>
            @else
                <button type="button" class="sw-next" wire:click="finish" wire:loading.attr="disabled" wire:target="finish">
                    <span wire:loading.remove wire:target="finish">Mulai Gunakan</span>
                    <span wire:loading wire:target="finish">Menyimpan…</span>
                </button>
            @endif
        </div>
    </main>
</div>
</div>
