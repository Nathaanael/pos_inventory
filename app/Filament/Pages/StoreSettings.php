<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function mount(): void
    {
        $this->form->fill(StoreSetting::current()->attributesToArray());
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
                            ->helperText('Opsional. Maks 1 MB. Tampil di pojok kiri atas panel admin.')
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

        StoreSetting::current()->update($data);

        Notification::make()
            ->title('Pengaturan toko disimpan')
            ->success()
            ->send();

        // Muat ulang agar nama/logo di sidebar ikut berubah.
        $this->redirect(static::getUrl());
    }
}
