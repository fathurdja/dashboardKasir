<?php

namespace App\Filament\Admin\Resources\StoreSettings\Pages;

use App\Filament\Admin\Resources\StoreSettings\StoreSettingsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreSettings extends ListRecords
{
    protected static string $resource = StoreSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
