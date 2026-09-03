<?php

namespace App\Filament\Admin\Resources\PpobTransactionResource\Pages;

use App\Filament\Admin\Resources\PpobTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPpobTransactions extends ListRecords
{
    protected static string $resource = PpobTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Usually we don't create PPOB transaction from the regular form,
            // we will create a dedicated custom page or modal for this.
        ];
    }
}
