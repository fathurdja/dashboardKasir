<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorization for real-time WebSocket events (Laravel Reverb).
| These channels are used by Flutter kasir apps and Filament dashboard.
|
*/

// All authenticated users can listen to store-wide transaction events
Broadcast::channel('store.transactions', function ($user) {
    return $user !== null;
});

// All authenticated users can listen to stock changes
Broadcast::channel('store.products', function ($user) {
    return $user !== null;
});

// Only the kasir who created the transaction can listen for payment confirmation
Broadcast::channel('payment.{receiptNumber}', function ($user, string $receiptNumber) {
    return Order::where('receipt_number', $receiptNumber)
        ->whereHas('device', fn ($q) => $q->where('user_id', $user->id))
        ->exists();
});
