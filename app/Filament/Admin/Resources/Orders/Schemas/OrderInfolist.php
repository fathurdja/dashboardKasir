<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([
                Section::make('Informasi Pesanan')
                    ->schema([
                        TextEntry::make('receipt_number')
                            ->label('Nomor Resi')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'canceled' => 'danger',
                                default => 'primary',
                            }),
                        TextEntry::make('order_type')
                            ->label('Tipe Pesanan'),
                        TextEntry::make('payment_method')
                            ->label('Pembayaran')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => 2]),

                Section::make('Rincian Biaya')
                    ->schema([
                        TextEntry::make('tax_amount')
                            ->label('Pajak / Service')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                        TextEntry::make('discount_amount')
                            ->label('Diskon / Potongan')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                        TextEntry::make('total_price')
                            ->label('Total Akhir')
                            ->weight('bold')
                            ->color('primary')
                            ->size('TextEntry\TextEntrySize::Large')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                    ])
                    ->columnSpan(['lg' => 1]),

                Section::make('Item Pesanan')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Produk')
                                    ->weight('bold'),
                                TextEntry::make('variant.name')
                                    ->label('Varian')
                                    ->placeholder('-'),
                                TextEntry::make('unit_price')
                                    ->label('Harga')
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                            ])
                            ->columns(5),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
