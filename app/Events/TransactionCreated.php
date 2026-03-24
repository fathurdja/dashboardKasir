<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('store.transactions'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'receipt_number' => $this->order->receipt_number,
            'total_price' => $this->order->total_price,
            'payment_method' => $this->order->payment_method,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at?->toIso8601String(),
        ];
    }
}
