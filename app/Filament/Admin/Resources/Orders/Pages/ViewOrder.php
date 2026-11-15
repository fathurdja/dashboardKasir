<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('PrintBill')
                ->label('Cetak Struk')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('orders.print', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
