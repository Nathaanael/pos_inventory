<?php

namespace App\Filament\Resources\DiningTables;

use App\Enums\Feature;
use App\Filament\Resources\DiningTables\Pages\ManageDiningTables;
use App\Models\DiningTable;
use App\Models\StoreSetting;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Master meja (modul FNB). Hanya tampil bila fitur "Manajemen Meja" aktif.
 */
class DiningTableResource extends Resource
{
    protected static ?string $model = DiningTable::class;

    protected static ?string $modelLabel = 'Meja';

    protected static ?string $pluralModelLabel = 'Meja';

    protected static ?string $navigationLabel = 'Master Meja';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'meja';

    public static function canAccess(): bool
    {
        return StoreSetting::hasFeature(Feature::DiningTables) && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')
                ->label('Nomor Meja')
                ->placeholder('mis. T1 atau 12')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true)
                ->validationMessages(['unique' => 'Nomor meja ini sudah dipakai.']),
            TextInput::make('name')
                ->label('Nama Meja')
                ->placeholder('mis. Teras depan, VIP 1')
                ->maxLength(100),
            TextInput::make('capacity')
                ->label('Jumlah Kursi')
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->placeholder('4'),
            Toggle::make('is_active')
                ->label('Aktif')
                ->helperText('Meja nonaktif tidak muncul di kasir.')
                ->default(true)
                ->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('activeOrders'))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('Kursi')
                    ->placeholder('-')
                    ->suffix(' kursi'),
                TextColumn::make('active_orders_count')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => $state > 0 ? 'Terisi' : 'Kosong')
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalDescription('Riwayat penjualan di meja ini tetap tersimpan, hanya tidak lagi terhubung ke meja.'),
            ])
            ->emptyStateHeading('Belum ada meja')
            ->emptyStateDescription('Tambahkan meja beserta nomornya agar bisa dipilih di kasir.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiningTables::route('/'),
        ];
    }
}
