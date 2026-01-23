<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_transaksi')
                    ->label('ID Transaksi')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'LUNAS',
                        'warning' => 'BON',
                    ])
                    ->formatStateUsing(fn($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'LUNAS' => 'LUNAS',
                        'BON'   => 'BON',
                    ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                ViewAction::make(),

                EditAction::make()
                    ->label('Ubah Status'),

                DeleteAction::make()
                    ->visible(false),
            ]);
    }
}
