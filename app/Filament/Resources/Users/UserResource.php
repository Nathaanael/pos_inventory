<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Aplikasi';

    protected static ?int $navigationSort = 15;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $isSelf = fn (?Model $record) => $record?->is(auth()->user()) ?? false;

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Email (untuk login)')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Role')
                    ->options(User::ROLES)
                    ->default(User::ROLE_KASIR)
                    ->required()
                    ->native(false)
                    ->disabled($isSelf)
                    ->helperText(fn (?Model $record) => $isSelf($record)
                        ? 'Anda tidak bisa mengubah role akun sendiri.'
                        : 'Kasir hanya bisa membuka portal kasir (/kasir).'),
                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(6)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengganti password.'
                        : null),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->disabled($isSelf)
                    ->helperText('User nonaktif tidak bisa login.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? ucfirst($state))
                    ->color(fn (string $state) => $state === User::ROLE_KASIR ? 'info' : 'warning'),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(User::ROLES),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil disimpan'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->hidden(fn (User $record) => $record->is(auth()->user()))
                    ->successNotificationTitle('Berhasil dihapus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
