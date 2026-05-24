<?php

namespace App\Filament\Admin\Resources\SyncLogs\Pages;

use App\Filament\Admin\Resources\SyncLogs\SyncLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSyncLogs extends ListRecords
{
    protected static string $resource = SyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
