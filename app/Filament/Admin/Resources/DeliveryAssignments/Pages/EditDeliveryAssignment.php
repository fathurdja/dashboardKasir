<?php

namespace App\Filament\Admin\Resources\DeliveryAssignments\Pages;

use App\Filament\Admin\Resources\DeliveryAssignments\DeliveryAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryAssignment extends EditRecord
{
    protected static string $resource = DeliveryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
