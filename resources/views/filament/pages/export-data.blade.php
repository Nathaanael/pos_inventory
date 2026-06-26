<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Export Form --}}
        <form wire:submit.prevent="processExport">
            {{ $this->form }}

            <div style="margin-top: 1.5rem;">
                <x-filament::button
                    type="submit"
                    size="lg"
                    icon="heroicon-o-arrow-down-tray"
                    wire:loading.attr="disabled"
                    wire:target="processExport"
                >
                    <span wire:loading.remove wire:target="processExport">
                        Proses Export
                    </span>
                    <span wire:loading wire:target="processExport">
                        <x-filament::loading-indicator class="h-5 w-5 inline-block" />
                    </span>
                </x-filament::button>
            </div>
        </form>

        {{-- Info --}}
        <x-filament::section icon="heroicon-o-bell-alert" style="margin-top: 1.5rem;">
            <x-slot name="heading">
                Notifikasi Otomatis
            </x-slot>
            <x-slot name="description">
                Anda akan mendapat notifikasi ketika file siap didownload.
            </x-slot>
        </x-filament::section>

        {{-- Export History --}}
        @php
            $files = $this->getExportFiles();
        @endphp

        @if(count($files) > 0)
            <x-filament::section icon="heroicon-o-clock" style="margin-top: 1.5rem;">
                <x-slot name="heading">
                    Riwayat Export
                </x-slot>
                <x-slot name="description">
                    File export yang pernah dibuat (maksimal 20 terbaru)
                </x-slot>

                <div style="overflow-x: auto; margin-top: 1rem;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 0.75rem 1rem; font-weight: 500; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">Nama File</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 500; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">Ukuran</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 500; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">Dibuat</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 500; color: #6b7280; text-transform: uppercase; font-size: 0.75rem; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 0.75rem 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <x-heroicon-o-document-text class="text-emerald-500" style="width: 1rem; height: 1rem; flex-shrink: 0;" />
                                            <span style="font-weight: 500; color: #374151;">{{ $file['name'] }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; color: #6b7280;">{{ $file['size'] }}</td>
                                    <td style="padding: 0.75rem 1rem; color: #6b7280;">{{ $file['date'] }}</td>
                                    <td style="padding: 0.75rem 1rem; text-align: right;">
                                        <a
                                            href="{{ $file['url'] }}"
                                            target="_blank"
                                            style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 500; color: #d97706; text-decoration: none;"
                                            onmouseover="this.style.color='#b45309'"
                                            onmouseout="this.style.color='#d97706'"
                                        >
                                            <x-heroicon-o-arrow-down-tray style="width: 0.875rem; height: 0.875rem;" />
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
