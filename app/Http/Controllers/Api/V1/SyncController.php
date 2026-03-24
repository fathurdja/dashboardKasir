<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionApiResource;
use App\Models\Device;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Bulk upload offline transactions from mobile.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'nullable|integer|exists:devices,id',
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|uuid',
            'orders.*.receipt_number' => 'required|string',
            'orders.*.status' => 'required|in:pending,completed,canceled',
            'orders.*.order_type' => 'required|in:dine-in,take-away',
            'orders.*.total_price' => 'required|numeric',
            'orders.*.tax_amount' => 'required|numeric',
            'orders.*.discount_amount' => 'required|numeric',
            'orders.*.payment_method' => 'nullable|string',
            'orders.*.received_amount' => 'nullable|numeric',
            'orders.*.change_amount' => 'nullable|numeric',
            'orders.*.customer_name' => 'nullable|string',
            'orders.*.customer_phone' => 'nullable|string',
            'orders.*.due_date' => 'nullable|date',
            'orders.*.notes' => 'nullable|string',
            'orders.*.created_at' => 'nullable|date',
            'orders.*.items' => 'required|array',
            'orders.*.items.*.id' => 'required|uuid',
            'orders.*.items.*.product_id' => 'required|uuid',
            'orders.*.items.*.product_name' => 'nullable|string',
            'orders.*.items.*.variant_id' => 'nullable|uuid',
            'orders.*.items.*.quantity' => 'required|integer|min:1',
            'orders.*.items.*.bonus_qty' => 'nullable|integer|min:0',
            'orders.*.items.*.unit_price' => 'required|numeric',
            'orders.*.items.*.subtotal' => 'required|numeric',
        ]);

        $syncedCount = 0;
        $failedCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($validated['orders'] as $orderData) {
                // Skip if order already exists (idempotent)
                if (Order::where('id', $orderData['id'])->exists()) {
                    continue;
                }

                try {
                    $order = Order::create([
                        'id' => $orderData['id'],
                        'receipt_number' => $orderData['receipt_number'],
                        'status' => $orderData['status'],
                        'order_type' => $orderData['order_type'],
                        'total_price' => $orderData['total_price'],
                        'tax_amount' => $orderData['tax_amount'],
                        'discount_amount' => $orderData['discount_amount'],
                        'payment_method' => $orderData['payment_method'],
                        'device_id' => $validated['device_id'] ?? null,
                        'received_amount' => $orderData['received_amount'] ?? null,
                        'change_amount' => $orderData['change_amount'] ?? null,
                        'customer_name' => $orderData['customer_name'] ?? null,
                        'customer_phone' => $orderData['customer_phone'] ?? null,
                        'due_date' => $orderData['due_date'] ?? null,
                        'notes' => $orderData['notes'] ?? null,
                        'created_at' => $orderData['created_at'] ?? now(),
                    ]);

                    foreach ($orderData['items'] as $itemData) {
                        OrderItem::create([
                            'id' => $itemData['id'],
                            'order_id' => $order->id,
                            'product_id' => $itemData['product_id'],
                            'product_name' => $itemData['product_name'] ?? null,
                            'variant_id' => $itemData['variant_id'] ?? null,
                            'quantity' => $itemData['quantity'],
                            'bonus_qty' => $itemData['bonus_qty'] ?? 0,
                            'unit_price' => $itemData['unit_price'],
                            'subtotal' => $itemData['subtotal'],
                        ]);

                        // Deduct stock for completed orders
                        if ($orderData['status'] === 'completed') {
                            $totalQty = $itemData['quantity'] + ($itemData['bonus_qty'] ?? 0);
                            StockTransaction::create([
                                'product_id' => $itemData['product_id'],
                                'variant_id' => $itemData['variant_id'] ?? null,
                                'type' => 'out',
                                'quantity' => $totalQty,
                                'reference_id' => $order->id,
                                'notes' => 'Sync order: ' . $order->receipt_number,
                            ]);
                        }
                    }

                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'receipt_number' => $orderData['receipt_number'],
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('Sync: failed to sync order', [
                        'receipt' => $orderData['receipt_number'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Log sync activity
            if ($validated['device_id'] ?? null) {
                SyncLog::create([
                    'device_id' => $validated['device_id'],
                    'direction' => 'upload',
                    'records_synced' => $syncedCount,
                    'status' => $failedCount === 0 ? 'success' : ($syncedCount > 0 ? 'partial' : 'failed'),
                    'error_message' => $failedCount > 0 ? json_encode($errors) : null,
                ]);

                // Update device last_synced_at
                Device::where('id', $validated['device_id'])->update(['last_synced_at' => now()]);
            }

            return response()->json([
                'synced' => $syncedCount,
                'failed' => $failedCount,
                'errors' => $errors,
                'server_time' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync upload failed completely', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Download server changes since a given timestamp.
     */
    public function download(Request $request): JsonResponse
    {
        $since = $request->input('since');

        $query = Product::with('category', 'variants');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }
        $products = $query->get();

        $ordersQuery = Order::with('items');
        if ($since) {
            $ordersQuery->where('updated_at', '>=', $since);
        }
        $orders = $ordersQuery->limit(500)->get();

        // Update device sync timestamp
        if ($request->input('device_id')) {
            Device::where('id', $request->input('device_id'))
                ->update(['last_synced_at' => now()]);

            SyncLog::create([
                'device_id' => $request->input('device_id'),
                'direction' => 'download',
                'records_synced' => $products->count() + $orders->count(),
                'status' => 'success',
            ]);
        }

        return response()->json([
            'products' => $products,
            'orders' => TransactionApiResource::collection($orders),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check sync status for a device.
     */
    public function status(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');

        if (!$deviceId) {
            return response()->json(['message' => 'device_id is required'], 422);
        }

        $device = Device::with(['syncLogs' => function ($q) {
            $q->latest()->limit(10);
        }])->find($deviceId);

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        return response()->json([
            'device_id' => $device->id,
            'device_name' => $device->device_name,
            'last_synced_at' => $device->last_synced_at?->toIso8601String(),
            'recent_logs' => $device->syncLogs,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
