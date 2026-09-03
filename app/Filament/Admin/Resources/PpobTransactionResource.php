<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PpobTransactionResource\Pages;
use App\Models\PpobTransaction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PpobTransactionResource extends Resource
{
    protected static ?string $model = PpobTransaction::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-device-phone-mobile';
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'PPOB';
    }
    
    public static function getModelLabel(): string
    {
        return 'Riwayat Transaksi';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Riwayat Transaksi PPOB';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ref_id')
                    ->label('Reference ID')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled()
                    ->label('Kasir'),
                Forms\Components\TextInput::make('customer_number')
                    ->label('Nomor Pelanggan')
                    ->disabled(),
                Forms\Components\TextInput::make('product_code')
                    ->label('Kode Produk')
                    ->disabled(),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Forms\Components\TextInput::make('iak_price')
                    ->label('Harga Modal')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Forms\Components\TextInput::make('sn')
                    ->label('Serial Number')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('Status')
                    ->disabled(),
                Forms\Components\Textarea::make('iak_response')
                    ->label('IAK Webhook Response')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Waktu'),
                Tables\Columns\TextColumn::make('ref_id')
                    ->searchable()
                    ->label('Ref ID'),
                Tables\Columns\TextColumn::make('customer_number')
                    ->searchable()
                    ->label('No. Pelanggan'),
                Tables\Columns\TextColumn::make('product_code')
                    ->searchable()
                    ->label('Produk'),
                Tables\Columns\TextColumn::make('price')
                    ->money('idr')
                    ->label('Harga Jual'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'SUCCESS' => 'success',
                        'FAILED' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sn')
                    ->searchable()
                    ->label('SN')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('iak_response')
                    ->label('Pesan dari API')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // 
            ])
            ->bulkActions([
                // 
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPpobTransactions::route('/'),
        ];
    }
}
