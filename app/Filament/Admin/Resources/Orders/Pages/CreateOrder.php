<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

use Livewire\Attributes\On;
use App\Filament\Admin\Resources\Orders\Schemas\OrderForm;
use Filament\Notifications\Notification;

use App\Models\StockTransaction;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public $category_id = null;

    protected function afterCreate(): void
    {
        $order = $this->record;

        if ($order->status === 'completed') {
            foreach ($order->items as $item) {
                StockTransaction::create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_id' => $order->id,
                    'notes' => 'Penjualan (Form): ' . $order->receipt_number,
                ]);
            }
        }
        
        Notification::make()
            ->success()
            ->title('Pesanan Berhasil')
            ->body('Pesanan ' . $order->receipt_number . ' telah disimpan.')
            ->send();
            
        $this->category_id = null;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
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
            
            $items[] = [
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
