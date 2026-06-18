<?php

namespace App\Filament\Admin\Resources\StoreSettings;

use App\Filament\Admin\Resources\StoreSettings\Pages\CreateStoreSettings;
use App\Filament\Admin\Resources\StoreSettings\Pages\EditStoreSettings;
use App\Filament\Admin\Resources\StoreSettings\Pages\ListStoreSettings;
use App\Filament\Admin\Resources\StoreSettings\Schemas\StoreSettingsForm;
use App\Filament\Admin\Resources\StoreSettings\Tables\StoreSettingsTable;
use App\Models\StoreSettings;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StoreSettingsResource extends Resource
{
    protected static ?string $model = StoreSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return StoreSettingsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreSettingsTable::configure($table);
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
            'index' => ListStoreSettings::route('/'),
            'create' => CreateStoreSettings::route('/create'),
            'edit' => EditStoreSettings::route('/{record}/edit'),
        ];
    }
}

