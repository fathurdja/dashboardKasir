<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'status' => $this->status,
            'order_type' => $this->order_type,
            'payment_method' => $this->payment_method,
            'total_price' => round((float) $this->total_price, 2),
            'tax_amount' => round((float) $this->tax_amount, 2),
            'discount_amount' => round((float) $this->discount_amount, 2),
            'received_amount' => $this->received_amount ? round((float) $this->received_amount, 2) : null,
            'change_amount' => $this->change_amount ? round((float) $this->change_amount, 2) : null,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name ?? $item->product?->name ?? 'Unknown',
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'bonus_qty' => $item->bonus_qty ?? 0,
                    'unit_price' => round((float) $item->unit_price, 2),
                    'subtotal' => round((float) $item->subtotal, 2),
                ])
            ),
            'xendit_status' => $this->xendit_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
