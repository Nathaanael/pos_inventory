<x-filament-panels::page>
    <div class="space-y-6" x-data="{ deleteFileName: '' }">
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
            $exportData = $this->getExportFiles();
            $files = $exportData['items'];
            $total = $exportData['total'];
            $totalPages = $exportData['totalPages'];
            $currentPage = $exportData['currentPage'];
            $from = $exportData['from'];
            $to = $exportData['to'];
        @endphp

        <x-filament::section icon="heroicon-o-clock" style="margin-top: 1.5rem;">
            <x-slot name="heading">
                Riwayat Export
            </x-slot>
            <x-slot name="description">
                Daftar file export yang pernah dibuat
            </x-slot>

            @if($total > 0)
                {{-- Top Bar: Per-page selector & Info --}}
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="perPageSelect" style="font-size: 0.875rem; color: #6b7280; white-space: nowrap;">Tampilkan</label>
                        <select
                            id="perPageSelect"
                            wire:model.live="perPage"
                            style="
                                padding: 0.375rem 0.625rem;
                                font-size: 0.875rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.5rem;
                                background: #ffffff;
                                color: #374151;
                                cursor: pointer;
                                outline: none;
                                min-width: 4rem;
                            "
                            onfocus="this.style.borderColor='#f59e0b'; this.style.boxShadow='0 0 0 2px rgba(245,158,11,0.2)'"
                            onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span style="font-size: 0.875rem; color: #6b7280;">data per halaman</span>
                    </div>

                    <div style="font-size: 0.8125rem; color: #9ca3af;">
                        Menampilkan <strong style="color: #374151;">{{ $from }}–{{ $to }}</strong> dari <strong style="color: #374151;">{{ $total }}</strong> file
                    </div>
                </div>

                {{-- Table --}}
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Nama File</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Ukuran</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">Dibuat</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr
                                    style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.15s ease;"
                                    onmouseover="this.style.backgroundColor='#f9fafb'"
                                    onmouseout="this.style.backgroundColor='transparent'"
                                >
                                    <td style="padding: 0.75rem 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <x-heroicon-o-document-text class="text-emerald-500" style="width: 1.125rem; height: 1.125rem; flex-shrink: 0;" />
                                            <span style="font-weight: 500; color: #374151;">{{ $file['name'] }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; color: #6b7280;">{{ $file['size'] }}</td>
                                    <td style="padding: 0.75rem 1rem; color: #6b7280;">{{ $file['date'] }}</td>
                                    <td style="padding: 0.75rem 1rem; text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                            {{-- Download --}}
                                            <a
                                                href="{{ $file['url'] }}"
                                                target="_blank"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 500; font-size: 0.8125rem; color: #d97706; text-decoration: none; transition: color 0.15s ease;"
                                                onmouseover="this.style.color='#b45309'"
                                                onmouseout="this.style.color='#d97706'"
                                                title="Download file"
                                            >
                                                <x-heroicon-o-arrow-down-tray style="width: 0.875rem; height: 0.875rem;" />
                                                Download
                                            </a>

                                            {{-- Delete --}}
                                            <button
                                                type="button"
                                                x-on:click="deleteFileName = '{{ $file['name'] }}'; $dispatch('open-modal', { id: 'delete-export-modal' })"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 500; font-size: 0.8125rem; color: #ef4444; background: none; border: none; cursor: pointer; padding: 0; transition: color 0.15s ease;"
                                                onmouseover="this.style.color='#dc2626'"
                                                onmouseout="this.style.color='#ef4444'"
                                                title="Hapus file"
                                            >
                                                <x-heroicon-o-trash style="width: 0.875rem; height: 0.875rem;" />
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Controls --}}
                @if($totalPages > 1)
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.375rem; margin-top: 1.25rem; flex-wrap: wrap;">
                        {{-- Previous --}}
                        <button
                            type="button"
                            wire:click="goToPage({{ max(1, $currentPage - 1) }})"
                            @if($currentPage <= 1) disabled @endif
                            style="
                                display: inline-flex; align-items: center; justify-content: center;
                                width: 2.25rem; height: 2.25rem;
                                border-radius: 0.5rem;
                                font-size: 0.8125rem; font-weight: 500;
                                border: 1px solid {{ $currentPage <= 1 ? '#e5e7eb' : '#d1d5db' }};
                                background: {{ $currentPage <= 1 ? '#f9fafb' : '#ffffff' }};
                                color: {{ $currentPage <= 1 ? '#d1d5db' : '#374151' }};
                                cursor: {{ $currentPage <= 1 ? 'not-allowed' : 'pointer' }};
                                transition: all 0.15s ease;
                            "
                            @if($currentPage > 1)
                                onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#f59e0b'"
                                onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#d1d5db'"
                            @endif
                            title="Halaman sebelumnya"
                        >
                            <x-heroicon-s-chevron-left style="width: 1rem; height: 1rem;" />
                        </button>

                        {{-- Page Numbers --}}
                        @php
                            // Show at most 7 page buttons with ellipsis
                            $pages = [];
                            if ($totalPages <= 7) {
                                $pages = range(1, $totalPages);
                            } else {
                                $pages = [1];
                                if ($currentPage > 3) $pages[] = '...';
                                for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
                                    $pages[] = $i;
                                }
                                if ($currentPage < $totalPages - 2) $pages[] = '...';
                                $pages[] = $totalPages;
                            }
                        @endphp

                        @foreach($pages as $page)
                            @if($page === '...')
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; font-size: 0.8125rem; color: #9ca3af;">…</span>
                            @else
                                <button
                                    type="button"
                                    wire:click="goToPage({{ $page }})"
                                    style="
                                        display: inline-flex; align-items: center; justify-content: center;
                                        width: 2.25rem; height: 2.25rem;
                                        border-radius: 0.5rem;
                                        font-size: 0.8125rem; font-weight: {{ $page === $currentPage ? '600' : '500' }};
                                        border: 1px solid {{ $page === $currentPage ? '#f59e0b' : '#d1d5db' }};
                                        background: {{ $page === $currentPage ? '#f59e0b' : '#ffffff' }};
                                        color: {{ $page === $currentPage ? '#ffffff' : '#374151' }};
                                        cursor: pointer;
                                        transition: all 0.15s ease;
                                    "
                                    @if($page !== $currentPage)
                                        onmouseover="this.style.backgroundColor='#fffbeb'; this.style.borderColor='#f59e0b'"
                                        onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#d1d5db'"
                                    @endif
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        <button
                            type="button"
                            wire:click="goToPage({{ min($totalPages, $currentPage + 1) }})"
                            @if($currentPage >= $totalPages) disabled @endif
                            style="
                                display: inline-flex; align-items: center; justify-content: center;
                                width: 2.25rem; height: 2.25rem;
                                border-radius: 0.5rem;
                                font-size: 0.8125rem; font-weight: 500;
                                border: 1px solid {{ $currentPage >= $totalPages ? '#e5e7eb' : '#d1d5db' }};
                                background: {{ $currentPage >= $totalPages ? '#f9fafb' : '#ffffff' }};
                                color: {{ $currentPage >= $totalPages ? '#d1d5db' : '#374151' }};
                                cursor: {{ $currentPage >= $totalPages ? 'not-allowed' : 'pointer' }};
                                transition: all 0.15s ease;
                            "
                            @if($currentPage < $totalPages)
                                onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#f59e0b'"
                                onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#d1d5db'"
                            @endif
                            title="Halaman berikutnya"
                        >
                            <x-heroicon-s-chevron-right style="width: 1rem; height: 1rem;" />
                        </button>
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div style="text-align: center; padding: 3rem 1rem;">
                    <x-heroicon-o-inbox-stack style="width: 3rem; height: 3rem; margin: 0 auto; color: #d1d5db;" />
                    <p style="margin-top: 0.75rem; font-size: 0.875rem; font-weight: 500; color: #6b7280;">Belum ada file export</p>
                    <p style="margin-top: 0.25rem; font-size: 0.8125rem; color: #9ca3af;">File export yang dibuat akan muncul di sini.</p>
                </div>
            @endif
        </x-filament::section>
        {{-- Delete Confirmation Modal using Native Filament Modal --}}
        <x-filament::modal id="delete-export-modal" width="md" alignment="center">
            <x-slot name="heading">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; margin-bottom: 1rem; border-radius: 9999px; background-color: #fef2f2;" class="dark:bg-red-900/20">
                        <x-heroicon-o-exclamation-triangle style="width: 1.5rem; height: 1.5rem; color: #dc2626;" class="dark:text-red-500" />
                    </div>
                    Hapus File Export
                </div>
            </x-slot>

            <div style="text-align: center;">
                <p style="font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus file
                </p>
                <p style="margin-top: 0.5rem; padding: 0.375rem 0.75rem; font-size: 0.875rem; font-weight: 600; color: #374151; background-color: #f3f4f6; border-radius: 0.375rem; display: inline-block; word-break: break-all;" class="dark:bg-gray-800 dark:text-gray-200" x-text="deleteFileName"></p>
                <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <x-slot name="footer">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; width: 100%;">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'delete-export-modal' })">
                        Batal
                    </x-filament::button>
                    
                    <x-filament::button color="danger" icon="heroicon-o-trash" x-on:click="$wire.deleteExportFile(deleteFileName); $dispatch('close-modal', { id: 'delete-export-modal' })">
                        Hapus File
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    </div>
</x-filament-panels::page>
