<?php

namespace App\Events;

use App\Models\Order;
use App\Models\XenditPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QrisPaymentConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public XenditPayment $payment,
    ) {}

    public function broadcastOn(): array
    {
        return [
            // Channel specific to the receipt — only the relevant kasir receives this
            new PrivateChannel("payment.{$this->order->receipt_number}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'receipt_number' => $this->order->receipt_number,
            'status' => 'PAID',
            'paid_at' => $this->payment->paid_at?->toIso8601String(),
        ];
    }
}
