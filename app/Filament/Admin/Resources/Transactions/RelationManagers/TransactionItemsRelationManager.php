<?php

namespace App\Filament\Admin\Resources\Transactions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tyunit')
                    ->label('Kode Unit'),

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang'),

                Tables\Columns\TextColumn::make('harga')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty'),

                Tables\Columns\TextColumn::make('bonus'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->money('IDR'),
            ])
            ->paginated(false)     // semua item langsung tampil
            ->defaultSort('id')
            ->actions([])          // READ ONLY
            ->headerActions([]);   // tidak bisa tambah
    }
}
