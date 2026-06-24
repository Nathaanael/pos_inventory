<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\Supplier;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->default(fn () => static::generateCode())
                    ->unique(ignoreRecord: true)
                    ->readOnly()
                    ->dehydrated()
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama Supplier')
                    ->required()
                    ->maxLength(150)
                    ->autofocus()
                    ->placeholder('Masukkan nama supplier'),
                Section::make('Informasi Kontak')
                    ->description('Opsional — isi jika diperlukan')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->regex('/^[0-9\-\+]+$/')
                            ->minLength(10)
                            ->maxLength(20)
                            ->placeholder('08xxxxxxxxxx'),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->placeholder('Alamat lengkap supplier')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function generateCode(): string
    {
        $lastSupplier = Supplier::query()
            ->where('code', 'like', 'SUP-%')
            ->orderByDesc('code')
            ->first();

        if ($lastSupplier) {
            $lastNumber = (int) str_replace('SUP-', '', $lastSupplier->code);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'SUP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
