<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Device;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Get target FCM tokens for active devices.
     * Optionally filter by target user_id.
     */
    protected function getActiveFcmTokens(?int $userId = null): array
    {
        $query = Device::where('is_active', true)
            ->whereNotNull('fcm_token');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->pluck('fcm_token')->unique()->toArray();
    }

    /**
     * 1. Notif Stock Update
     */
    public function notifyStockUpdate(Product|string $productOrName, string $changeType, int $quantity, int $newStock, ?int $userId = null): AppNotification
    {
        $productName = is_object($productOrName) ? $productOrName->name : $productOrName;
        $productId = is_object($productOrName) ? $productOrName->id : null;

        $typeLabel = match (strtolower($changeType)) {
            'in', 'masuk', 'tambah' => 'bertambah',
            'out', 'keluar', 'jual', 'kurang' => 'berkurang',
            default => 'di-update',
        };

        $title = "📦 Update Stok: {$productName}";
        $body = "Stok {$productName} {$typeLabel} sejumlah {$quantity} unit. Stok saat ini: {$newStock} unit.";

        $dataPayload = [
            'type' => 'stock_update',
            'product_id' => (string) $productId,
            'product_name' => $productName,
            'change_type' => $changeType,
            'quantity' => $quantity,
            'new_stock' => $newStock,
        ];

        // 1. Store in Database
        $notification = AppNotification::create([
            'user_id' => $userId,
            'type' => 'stock_update',
            'title' => $title,
            'body' => $body,
            'data' => $dataPayload,
            'is_read' => false,
        ]);

        // 2. Push via FCM
        $tokens = $this->getActiveFcmTokens($userId);
        $this->fcmService->sendPushNotification($tokens, $title, $body, $dataPayload);

        return $notification;
    }

    /**
     * 2. Notif Pembayaran Berhasil
     */
    public function notifyPaymentSuccess(Order $order, ?int $userId = null): AppNotification
    {
        $receiptNumber = $order->receipt_number;
        $formattedAmount = 'Rp ' . number_format($order->total_price, 0, ',', '.');
        $paymentMethod = strtoupper($order->payment_method ?? 'CASH');

        $title = "💳 Pembayaran Berhasil: {$receiptNumber}";
        $body = "Pembayaran sebesar {$formattedAmount} via {$paymentMethod} untuk transaksi #{$receiptNumber} telah berhasil!";

        $dataPayload = [
            'type' => 'payment_success',
            'order_id' => (string) $order->id,
            'receipt_number' => $receiptNumber,
            'total_price' => (float) $order->total_price,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
        ];

        // 1. Store in Database
        $notification = AppNotification::create([
            'user_id' => $userId ?? $order->user_id,
            'type' => 'payment_success',
            'title' => $title,
            'body' => $body,
            'data' => $dataPayload,
            'is_read' => false,
        ]);

        // 2. Push via FCM
        $tokens = $this->getActiveFcmTokens($userId ?? $order->user_id);
        $this->fcmService->sendPushNotification($tokens, $title, $body, $dataPayload);

        return $notification;
    }

    /**
     * 3. Notif Transaksi Dilakukan
     */
    public function notifyTransactionCreated(Order $order, ?int $userId = null): AppNotification
    {
        $receiptNumber = $order->receipt_number;
        $formattedAmount = 'Rp ' . number_format($order->total_price, 0, ',', '.');
        $customerName = $order->customer_name ? " (`{$order->customer_name}`)" : "";

        $title = "🧾 Transaksi Baru: {$receiptNumber}";
        $body = "Transaksi baru{$customerName} dibuat senilai {$formattedAmount}. Status: " . ucfirst($order->status);

        $dataPayload = [
            'type' => 'transaction_created',
            'order_id' => (string) $order->id,
            'receipt_number' => $receiptNumber,
            'total_price' => (float) $order->total_price,
            'order_type' => $order->order_type,
            'payment_method' => $order->payment_method,
            'customer_name' => $order->customer_name,
        ];

        // 1. Store in Database
        $notification = AppNotification::create([
            'user_id' => $userId ?? $order->user_id,
            'type' => 'transaction_created',
            'title' => $title,
            'body' => $body,
            'data' => $dataPayload,
            'is_read' => false,
        ]);

        // 2. Push via FCM
        $tokens = $this->getActiveFcmTokens($userId ?? $order->user_id);
        $this->fcmService->sendPushNotification($tokens, $title, $body, $dataPayload);

        return $notification;
    }
}
