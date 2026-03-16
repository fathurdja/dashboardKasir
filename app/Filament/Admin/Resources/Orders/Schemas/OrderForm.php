<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Group;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Menggunakan 3 kolom untuk layar besar (2 kiri, 1 kanan)
            ->columns(['lg' => 3])
            ->components([
                
                // --- KIRI: MENU PRODUK (2 KOLOM) ---
                Group::make()
                    ->schema([
                        Section::make('Menu Produk')
                            ->schema([
                                ViewField::make('product_picker')
                                    ->view('filament.forms.components.product-picker')
                                    ->hiddenLabel(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // --- KANAN: KERANJANG & INFO (1 KOLOM) ---
                Group::make()
                    ->schema([
                        // --- INFORMASI PESANAN ---
                        Section::make('Informasi Pesanan')
                            ->collapsed(false)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('receipt_number')
                                            ->label('No. Struk')
                                            ->default(fn () => 'ORD-' . strtoupper(uniqid()))
                                            ->required()
                                            ->readOnly()
                                            ->columnSpanFull(),
                                        
                                        Select::make('order_type')
                                            ->options([
                                                'dine-in' => 'Dine In',
                                                'take-away' => 'Take Away',
                                                'delivery' => 'Delivery',
                                            ])
                                            ->required()
                                            ->default('take-away')
                                            ->columnSpanFull(),

                                        Select::make('status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'completed' => 'Completed',
                                                'canceled' => 'Canceled',
                                            ])
                                            ->required()
                                            ->default('pending'),
                                        
                                        TextInput::make('payment_method')
                                            ->label('Pembayaran')
                                            ->placeholder('Cash/QRIS'),
                                    ]),
                            ]),

                        // --- KERANJANG BELANJA ---
                        Section::make('Daftar Belanja & Pembayaran')
                            ->schema([
                                Repeater::make('items')
                                    ->hiddenLabel()
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->disableLabel()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 5]),
                                        
                                        TextInput::make('quantity')
                                            ->disableLabel()
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateItemSubtotal($set, $get))
                                            ->prefix('x')
                                            ->columnSpan(['default' => 3]),
                                        
                                        TextInput::make('unit_price')
                                            ->hidden()
                                            ->dehydrated(),
                                        
                                        TextInput::make('subtotal')
                                            ->disableLabel()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 4]),
                                    ])
                                    ->columns(['default' => 12])
                                    ->defaultItems(0)
                                    ->deletable(true)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateOrderTotal($set, $get)),

                                // Area Rincian Biaya
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('subtotal_display')
                                            ->label('Sub-Total')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['class' => 'font-semibold text-gray-700']),
                                        
                                        TextInput::make('tax_amount')
                                            ->label('Pajak / Service')
                                            ->numeric()
                                            ->placeholder('0')
                                            ->prefix('Rp')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateOrderTotal($set, $get)),
                                        
                                        TextInput::make('discount_amount')
                                            ->label('Diskon / Potongan')
                                            ->numeric()
                                            ->placeholder('0')
                                            ->prefix('Rp')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateOrderTotal($set, $get)),
                                        
                                        TextInput::make('total_price')
                                            ->label('Total Akhir')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpanFull()
                                            ->extraInputAttributes([
                                                'class' => '!text-2xl font-bold !text-primary-600',
                                            ]),
                                    ])
                                    ->extraAttributes(['class' => 'mt-6 border-t pt-6 dark:border-gray-700']),
                                    
                                // Tombol Bayar kustom Anda
                                ViewField::make('charge_button')
                                    ->view('filament.forms.components.charge-button')
                                    ->hiddenLabel(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function updateItemSubtotal(Set $set, Get $get): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $set('subtotal', $quantity * $unitPrice);
    }

    public static function updateOrderTotal(Set $set, Get $get): void
    {
        $items = collect($get('items') ?? []);
        $subtotal = $items->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
        
        $set('subtotal_display', $subtotal);

        $tax = (float) ($get('tax_amount') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);
        
        $set('total_price', $subtotal + $tax - $discount);
    }
}