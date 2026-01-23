<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\MasterStock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Information')
                ->schema([
                    Select::make('TYUNIT')
                        ->label('Select Item')
                        ->options(MasterStock::all()->pluck('NTYUNIT', 'TYUNIT'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $stock = MasterStock::find($state);
                            if ($stock) {
                                // Auto-fill field name dan price
                                $set('name', $stock->NTYUNIT);
                                $set('price', $stock->hjual);
                            }
                        })
                        ->required(),

                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('price')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    TextInput::make('stock')
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }
}
