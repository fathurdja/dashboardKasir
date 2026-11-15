<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public $category_id = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    #[On('add-to-order')]
    public function addItemToOrder($id, $name, $price, $variant_id = null, $stock = 0)
    {
        $items = $this->data['items'] ?? [];
        
        // Check if item already exists
        $exists = false;
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $id && ($item['variant_id'] === $variant_id)) {
                if ($items[$index]['quantity'] + 1 > $stock) {
                    Notification::make()
                        ->warning()
                        ->title('Stok Tidak Mencukupi')
                        ->body("Sisa stok {$name} hanya {$stock}.")
                        ->send();
                    return;
                }
                
                $items[$index]['quantity']++;
                $items[$index]['subtotal'] = $items[$index]['quantity'] * $items[$index]['unit_price'];
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            if (1 > $stock) {
                Notification::make()
                    ->warning()
                    ->title('Stok Habis')
                    ->body("Produk {$name} sudah habis.")
                    ->send();
                return;
            }
            
            // Wait, for Edit we need an ID for new repeater items to avoid Filament errors?
            // Usually adding an item without ID generates a UUID on the frontend but Filament handles simple arrays if saved.
            // Using uniqid() ensures proper keying
            $items[uniqid('new_')] = [
                'product_id' => $id,
                'variant_id' => $variant_id,
                'unit_price' => $price,
                'quantity' => 1,
                'subtotal' => $price,
            ];
        }

        $this->data['items'] = $items;
        
        // Trigger total calculation
        $this->updateTotals();
    }

    protected function updateTotals()
    {
        $items = collect($this->data['items'] ?? []);
        $subtotal = $items->sum('subtotal');
        
        $tax = (float) ($this->data['tax_amount'] ?? 0);
        $discount = (float) ($this->data['discount_amount'] ?? 0);
        
        $this->data['total_price'] = $subtotal + $tax - $discount;
    }
}
