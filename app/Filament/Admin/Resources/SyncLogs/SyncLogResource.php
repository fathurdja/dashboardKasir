<?php

namespace App\Filament\Admin\Resources\SyncLogs;

use App\Filament\Admin\Resources\SyncLogs\Pages\CreateSyncLog;
use App\Filament\Admin\Resources\SyncLogs\Pages\EditSyncLog;
use App\Filament\Admin\Resources\SyncLogs\Pages\ListSyncLogs;
use App\Filament\Admin\Resources\SyncLogs\Schemas\SyncLogForm;
use App\Filament\Admin\Resources\SyncLogs\Tables\SyncLogsTable;
use App\Models\SyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SyncLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SyncLogsTable::configure($table);
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
            'index' => ListSyncLogs::route('/'),
            'create' => CreateSyncLog::route('/create'),
            'edit' => EditSyncLog::route('/{record}/edit'),
        ];
    }
}
