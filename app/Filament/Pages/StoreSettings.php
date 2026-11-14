<?php

namespace App\Filament\Pages;

use App\Enums\BusinessType;
use App\Enums\Feature;
use App\Models\StoreFeature;
use App\Models\StoreSetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StoreSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Pengaturan Toko';

    protected static ?string $title = 'Pengaturan Toko';

    protected static ?string $slug = 'pengaturan-toko';

    protected static \UnitEnum|string|null $navigationGroup = 'Aplikasi';

    protected static ?int $navigationSort = 20;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Nama file sementara logo yang terakhir disimpan, supaya tidak disimpan dua kali. */
    public ?string $savedLogoUpload = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function mount(): void
    {
        $setting = StoreSetting::current();

        $this->form->fill([
            ...$setting->attributesToArray(),
            'business_type' => $setting->business_type?->value,
            'features' => StoreFeature::query()->where('is_enabled', true)->pluck('feature')->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Usaha')
                    ->description('Tampil di panel admin, struk penjualan, dan laporan Excel.')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Nama Usaha')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('tagline')
                            ->label('Keterangan Singkat')
                            ->placeholder('mis. Toko Bangunan & Listrik')
                            ->maxLength(150),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('store')
                            ->maxSize(1024)
                            ->imagePreviewHeight('140')
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([null, '1:1', '4:1'])
                            // Logo disimpan sendiri oleh saveLogo(). Filament tidak boleh
                            // memindahkan file atau mengubah isi kotak upload, karena itu
                            // membuat kotak upload (dan editornya) menampilkan gambar dobel.
                            ->storeFiles(false)
                            ->dehydrated(false)
                            ->helperText('Opsional. Maks 1 MB. Langsung tersimpan saat diganti, diedit, atau dihapus. Untuk mengganti: klik × lalu pilih file baru, atau seret file baru ke kotak ini. Ikon pensil untuk memotong/memutar.')
                            ->live()
                            ->afterStateUpdated(fn (FileUpload $component) => $this->saveLogo($component))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Jenis Usaha & Fitur')
                    ->description('Mematikan fitur hanya menyembunyikan menu dan kolomnya; data yang sudah ada tidak dihapus.')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Select::make('business_type')
                            ->label('Jenis Usaha')
                            ->options(collect(BusinessType::cases())->mapWithKeys(fn (BusinessType $t) => [$t->value => $t->label()]))
                            ->required()
                            ->native(false)
                            ->live(),
                        CheckboxList::make('features')
                            ->label('Fitur Aktif')
                            ->options(collect(Feature::cases())->mapWithKeys(fn (Feature $f) => [$f->value => $f->label()]))
                            ->descriptions(collect(Feature::cases())->mapWithKeys(fn (Feature $f) => [$f->value => $f->description()]))
                            ->columns(2)
                            ->bulkToggleable()
                            ->hintAction(
                                Action::make('applyPreset')
                                    ->label('Pakai fitur bawaan jenis usaha')
                                    ->icon('heroicon-o-arrow-path')
                                    ->disabled(fn (Get $get) => ! BusinessType::tryFrom((string) $get('business_type')))
                                    ->action(function (Get $get, Set $set) {
                                        $type = BusinessType::tryFrom((string) $get('business_type'));
                                        if ($type) {
                                            $set('features', array_map(fn (Feature $f) => $f->value, $type->defaultFeatures()));
                                        }
                                    })
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Kontak & Alamat')
                    ->description('Dicetak di bagian atas struk.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),
                        TextInput::make('tax_number')
                            ->label('NPWP')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Struk & Printer')
                    ->icon('heroicon-o-printer')
                    ->schema([
                        TextInput::make('receipt_footer')
                            ->label('Teks Penutup Struk')
                            ->placeholder('Terima Kasih')
                            ->maxLength(255),
                        TextInput::make('printer_name')
                            ->label('Nama Printer Thermal')
                            ->placeholder('RP58 Printer')
                            ->helperText('Nama printer persis seperti di Windows (Settings > Printers). Kosongkan untuk memakai THERMAL_PRINTER_NAME di .env.')
                            ->maxLength(100),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan Pengaturan')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $features = $data['features'] ?? [];
        unset($data['features']);

        StoreSetting::current()->update($data);
        StoreSetting::saveFeatures($features);

        Notification::make()
            ->title('Pengaturan toko disimpan')
            ->success()
            ->send();

        // Muat ulang agar nama/logo di sidebar ikut berubah.
        $this->redirect(static::getUrl());
    }

    /**
     * Logo langsung disimpan saat diunggah atau dihapus (tanpa tombol Simpan),
     * file lama dihapus dari disk, dan logo di navbar ikut diganti tanpa reload.
     */
    public function saveLogo(FileUpload $component): void
    {
        // Ambil isi kotak upload apa adanya, TANPA mengubahnya.
        $files = array_values(array_filter((array) $component->getRawState()));
        $file = end($files) ?: null;

        if ($file instanceof TemporaryUploadedFile) {
            // Afterstate bisa terpanggil lagi untuk file yang sama; simpan sekali saja.
            if ($this->savedLogoUpload === $file->getFilename()) {
                return;
            }

            // Salin (bukan pindahkan) ke penyimpanan permanen; file sementara tetap
            // dipakai kotak upload untuk preview & editor.
            $path = $file->store('store', 'public');
            $this->savedLogoUpload = $file->getFilename();
        } else {
            $path = is_string($file) && filled($file) ? $file : null;
        }

        $setting = StoreSetting::current();
        $oldPath = $setting->logo;

        if ($oldPath === $path) {
            return;
        }

        $setting->update(['logo' => $path]);
        StoreSetting::flushCache();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        Notification::make()
            ->title($path ? 'Logo diperbarui' : 'Logo dihapus')
            ->success()
            ->send();

        $url = Js::from($path ? Storage::disk('public')->url($path) : null);
        $name = Js::from(StoreSetting::storeName());

        $this->js(<<<JS
            document.querySelectorAll('.fi-logo').forEach((el) => {
                const url = {$url};
                const node = document.createElement(url ? 'img' : 'div');
                if (url) {
                    node.src = url;
                    node.alt = {$name};
                    node.style.height = el.style.height || '2.5rem';
                } else {
                    node.textContent = {$name};
                }
                node.className = 'fi-logo';
                el.replaceWith(node);
            });
        JS);
    }
}
