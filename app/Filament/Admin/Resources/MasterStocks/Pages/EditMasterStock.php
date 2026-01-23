<?php

namespace App\Filament\Admin\Resources\MasterStocks\Pages;

use App\Filament\Admin\Resources\MasterStocks\MasterStockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterStock extends EditRecord
{
    protected static string $resource = MasterStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
