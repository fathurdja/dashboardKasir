<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\StockTransaction;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    // Store requested stock temporarily
    protected int $initialStock = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['initial_stock'])) {
            $this->initialStock = (int) $data['initial_stock'];
            unset($data['initial_stock']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialStock > 0) {
            StockTransaction::create([
                'product_id' => $this->record->id,
                'type' => 'in',
                'quantity' => $this->initialStock,
                'notes' => 'Stok awal',
            ]);
        }
    }
}
