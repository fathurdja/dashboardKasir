<?php

namespace App\Filament\Admin\Resources\StoreSettings\Pages;

use App\Filament\Admin\Resources\StoreSettings\StoreSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreSettings extends EditRecord
{
    protected static string $resource = StoreSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
