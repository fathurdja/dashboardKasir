<?php

namespace App\Filament\Admin\Resources\MasterStocks\Pages;

use App\Filament\Admin\Resources\MasterStocks\MasterStockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMasterStock extends CreateRecord
{
    protected static string $resource = MasterStockResource::class;
}
