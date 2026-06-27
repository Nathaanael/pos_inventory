<?php 

namespace App\Filament\Resources\StockOpnames\Schemas; 

use Filament\Forms\Components\DatePicker; 
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\TextInput; 
use Filament\Forms\Components\Textarea; 
use Filament\Forms\Components\Repeater; 
use Filament\Forms\Components\Hidden; 
use Filament\Schemas\Schema; 

class StockOpnameForm 
{ 
    public static function configure(Schema $schema): Schema 
    { 
        $isRequired = fn ($livewire) => !property_exists($livewire, 'submitMode') || $livewire->submitMode === 'completed';

        return $schema->components([ 
            TextInput::make('code')
                ->label('Kode Sesi')
                ->default(fn() => 'SO-'.date('Y').'-'.rand(1000, 9999))
                ->required($isRequired)
                ->readOnly(), 
                
            TextInput::make('session_name')
                ->label('Nama Sesi')
                ->required($isRequired), 
                
            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->native(false)
                ->displayFormat('d F Y')
                ->required($isRequired)
                ->columnSpanFull(), 
                
            Hidden::make('status')
                ->default('selesai'), 
                
            Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(), 
                
            Hidden::make('created_by')
                ->default(fn() => auth()->id()), 
                
            Repeater::make('items')
                ->label('Barang')
                ->addActionLabel('Tambah Barang')
                ->relationship('items')
                ->schema([ 
                    Select::make('item_id')
                        ->label('Barang')
                        ->relationship('item', 'name')
                        ->searchable()
                        ->preload()
                        ->required($isRequired)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) { 
                            $item = \App\Models\Item::find($state); 
                            if($item) {
                                $set('system_stock', $item->current_stock); 
                            }
                        }), 
                        
                    TextInput::make('system_stock')
                        ->label('Stok Sistem')
                        ->numeric()
                        ->readOnly()
                        ->required($isRequired), 
                        
                    TextInput::make('physical_stock')
                        ->label('Stok Fisik')
                        ->numeric()
                        ->required($isRequired)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) { 
                            if ($state !== null && $state !== '') {
                                $diff = (int)$state - (int)$get('system_stock'); 
                                $set('difference', $diff); 
                                $set('status', $diff == 0 ? 'cocok' : ($diff > 0 ? 'lebih' : 'kurang')); 
                            }
                        }), 
                        
                    TextInput::make('difference')
                        ->label('Selisih')
                        ->numeric()
                        ->readOnly(), 
                        
                    Select::make('status')
                        ->options([
                            'cocok' => 'Cocok', 
                            'kurang' => 'Kurang', 
                            'lebih' => 'Lebih'
                        ])
                        ->disabled()
                        ->dehydrated()
                        ->required($isRequired), 
                        
                    TextInput::make('notes')->label('Catatan'), 
                ])
                ->columnSpanFull()
                ->columns(6) 
        ]); 
    } 
}