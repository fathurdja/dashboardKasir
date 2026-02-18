<?php

namespace App\Filament\Admin\Resources\MasterStocks\Tables;

use Filament\Tables;
use Filament\Tables\Table;
// Pastikan import ini ada dan benar:
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class MasterStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('TYUNIT')
                    ->label('Unit Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('NTYUNIT')
                    ->label('Unit Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kdklp')
                    ->label('Group'),
                Tables\Columns\TextColumn::make('hjual')
                    ->label('Price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Jumlah Stock'),
                Tables\Columns\TextColumn::make('userup')
                    ->label('Updated By'),
                Tables\Columns\TextColumn::make('tglup')
                    ->label('Update Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                // Tambahkan filter di sini jika perlu
            ]);
    }
}
