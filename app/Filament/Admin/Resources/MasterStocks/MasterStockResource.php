<?php

namespace App\Filament\Admin\Resources\MasterStocks;

use App\Filament\Admin\Resources\MasterStocks\Pages\CreateMasterStock;
use App\Filament\Admin\Resources\MasterStocks\Pages\EditMasterStock;
use App\Filament\Admin\Resources\MasterStocks\Pages\ListMasterStocks;
use App\Filament\Admin\Resources\MasterStocks\Schemas\MasterStockForm;
use App\Filament\Admin\Resources\MasterStocks\Tables\MasterStocksTable;
use App\Models\MasterStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterStockResource extends Resource
{
    protected static ?string $model = MasterStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MasterStockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterStocksTable::configure($table);
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
            'index' => ListMasterStocks::route('/'),
            'create' => CreateMasterStock::route('/create'),
            'edit' => EditMasterStock::route('/{record}/edit'),
        ];
    }
}
