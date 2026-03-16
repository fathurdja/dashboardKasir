<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Handle bulk syncing of orders from mobile offline-first POS using JSON batch payload.
     */
    public function syncOrders(Request $request)
    {
        $payload = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|uuid',
            'orders.*.receipt_number' => 'required|string',
            'orders.*.status' => 'required|in:pending,completed,canceled',
            'orders.*.order_type' => 'required|in:dine-in,take-away',
            'orders.*.total_price' => 'required|numeric',
            'orders.*.tax_amount' => 'required|numeric',
            'orders.*.discount_amount' => 'required|numeric',
            'orders.*.payment_method' => 'nullable|string',
            'orders.*.created_at' => 'nullable|date',
            
            'orders.*.items' => 'required|array',
            'orders.*.items.*.id' => 'required|uuid',
            'orders.*.items.*.product_id' => 'required|uuid',
            'orders.*.items.*.variant_id' => 'nullable|uuid',
            'orders.*.items.*.quantity' => 'required|integer|min:1',
            'orders.*.items.*.unit_price' => 'required|numeric',
            'orders.*.items.*.subtotal' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();
            $syncedCount = 0;

            foreach ($payload['orders'] as $orderData) {
                // If order exists by UUID, skip to prevent double insert.
                // Could be changed to update if mobile supports mutable offline editing.
                if (Order::where('id', $orderData['id'])->exists()) {
                    continue;
                }

                $order = Order::create([
                    'id' => $orderData['id'],
                    'receipt_number' => $orderData['receipt_number'],
                    'status' => $orderData['status'],
                    'order_type' => $orderData['order_type'],
                    'total_price' => $orderData['total_price'],
                    'tax_amount' => $orderData['tax_amount'],
                    'discount_amount' => $orderData['discount_amount'],
                    'payment_method' => $orderData['payment_method'],
                    'created_at' => $orderData['created_at'] ?? now(),
                ]);

                foreach ($orderData['items'] as $itemData) {
                    OrderItem::create([
                        'id' => $itemData['id'],
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'variant_id' => $itemData['variant_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    // Automatically deduct stock for each order tracked successfully.
                    StockTransaction::create([
                        // ID auto-generated as UUID by HasUuids model trait
                        'product_id' => $itemData['product_id'],
                        'variant_id' => $itemData['variant_id'],
                        'type' => 'out', // Outgoing stock
                        'quantity' => $itemData['quantity'],
                        'reference_id' => $order->id,
                        'notes' => 'Sync order receipt: ' . $order->receipt_number,
                    ]);
                }

                $syncedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orders synchronized successfully',
                'synced_count' => $syncedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Sync Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server encountered an error processing batch data'
            ], 500);
        }
    }
}
