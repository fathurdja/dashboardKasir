<?php

namespace App\Filament\Admin\Resources\MasterStocks\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class MasterStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('TYUNIT')
                    ->required()
                    ->maxLength(20)
                    ->label('Unit Code'),
                Forms\Components\TextInput::make('NTYUNIT')
                    ->maxLength(60)
                    ->label('Unit Name'),
                Forms\Components\TextInput::make('kdklp')
                    ->required()
                    ->maxLength(3)
                    ->label('Group Code'),
                Forms\Components\TextInput::make('hjual')
                    ->numeric()
                    ->label('Selling Price')
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('stock')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->label('Jumlah Stock')
                    ->minValue(0),
                Forms\Components\TextInput::make('cmodule')
                    ->maxLength(15)
                    ->default(''),
                // Forms\Components\TextInput::make('userup')
                //     ->maxLength(60)
                //     ->default(fn() => auth()->user()->name), // Contoh auto-fill user
                Forms\Components\DatePicker::make('tglup')
                    ->label('Last Update Date'),
            ]);
    }
}
