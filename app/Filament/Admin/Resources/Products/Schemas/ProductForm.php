<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([

                // ── LEFT: Informasi + Harga + Variasi (2 kolom) ──
                Grid::make(1)
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        Section::make('Informasi Produk')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Nasi Goreng Special'),

                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Pilih Kategori'),

                                TextInput::make('barcode')
                                    ->label('Barcode / SKU')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('Opsional'),

                                TextInput::make('initial_stock')
                                    ->label('Stok Awal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('pcs')
                                    ->hiddenOn('edit')
                                    ->helperText('Jumlah stok saat produk pertama kali dibuat.'),

                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(3)
                                    ->placeholder('Deskripsi singkat produk...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Harga')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextInput::make('purchase_price')
                                    ->label('Harga Beli (HPP)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->placeholder('0'),

                                TextInput::make('price')
                                    ->label('Harga Jual')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->placeholder('0'),
                            ])
                            ->columns(2),

                        Section::make('Variasi Produk')
                            ->icon('heroicon-o-squares-2x2')
                            ->description('Tambahkan variasi jika produk memiliki pilihan seperti ukuran atau rasa.')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama Variasi')
                                            ->required()
                                            ->placeholder('Contoh: Ice / Hot'),
                                        TextInput::make('sku')
                                            ->label('SKU Variasi')
                                            ->placeholder('Opsional'),
                                        TextInput::make('purchase_price')
                                            ->label('Harga Beli')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('Rp'),
                                        TextInput::make('additional_price')
                                            ->label('Tambahan Harga Jual')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('Rp'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->addActionLabel('+ Tambah Variasi'),
                            ]),

                    ]),

                // ── RIGHT: Gambar + Status (1 kolom) ──
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        Section::make('Gambar Produk')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('products')
                                    ->maxSize(2048)
                                    ->hiddenLabel()
                                    ->imagePreviewHeight('220')
                                    ->panelAspectRatio('1:1')
                                    ->panelLayout('integrated'),
                            ]),

                        Section::make('Status Produk')
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Produk Aktif')
                                    ->default(true)
                                    ->required()
                                    ->helperText('Nonaktifkan jika produk tidak lagi dijual.'),
                            ]),

                    ]),

            ]);
    }
}
