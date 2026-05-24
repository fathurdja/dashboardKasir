<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionApiResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * List transactions with filters (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items.product')
            ->orderByDesc('created_at');

        // Date filter
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Date range filter
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [$request->input('from'), $request->input('to')]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Payment method filter
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => TransactionApiResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Show a single transaction with order items.
     */
    public function show(string $receiptNumber): JsonResponse
    {
        $order = Order::with('items.product')
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();

        return response()->json([
            'data' => new TransactionApiResource($order),
        ]);
    }

    /**
     * Create a new transaction (cash or bon).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|uuid',
            'receipt_number' => 'nullable|string|unique:orders,receipt_number',
            'order_type' => 'required|in:dine-in,take-away',
            'payment_method' => 'required|string|in:cash,qris,bon',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.bonus_qty' => 'nullable|integer|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['id'])) {
            $existingOrder = Order::with('items.product')->find($validated['id']);
            if ($existingOrder) {
                return response()->json([
                    'message' => 'Transaction already exists (Idempotent)',
                    'data' => new TransactionApiResource($existingOrder),
                ], 200);
            }
        }

        try {
            DB::beginTransaction();

            // Generate receipt number if not provided
            if (empty($validated['receipt_number'])) {
                $validated['receipt_number'] = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            }

            // Determine status based on payment method
            $status = match ($validated['payment_method']) {
                'cash', 'qris' => 'completed',
                'bon' => 'pending',
                default => 'pending',
            };

            // For QRIS, set to pending until webhook confirms
            if ($validated['payment_method'] === 'qris') {
                $status = 'pending';
            }

            $order = Order::create([
                'id' => $validated['id'] ?? (string) Str::uuid(),
                'receipt_number' => $validated['receipt_number'],
                'status' => $status,
                'order_type' => $validated['order_type'],
                'total_price' => $validated['total_price'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'payment_method' => $validated['payment_method'],
                'device_id' => $request->input('device_id'),
                'received_amount' => $validated['received_amount'] ?? null,
                'change_amount' => $validated['change_amount'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_name' => $product?->name,
                    'quantity' => $itemData['quantity'],
                    'bonus_qty' => $itemData['bonus_qty'] ?? 0,
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'],
                ]);

                // Reduce stock for completed (cash) orders
                if ($status === 'completed') {
                    $totalQty = $itemData['quantity'] + ($itemData['bonus_qty'] ?? 0);
                    StockTransaction::create([
                        'product_id' => $itemData['product_id'],
                        'type' => 'out',
                        'quantity' => $totalQty,
                        'reference_id' => $order->id,
                        'notes' => 'Penjualan: ' . $order->receipt_number,
                    ]);
                }
            }

            DB::commit();

            $order->load('items.product');

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => new TransactionApiResource($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Void a transaction and restore stock.
     */
    public function void(string $receiptNumber): JsonResponse
    {
        $order = Order::with('items')
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();

        if ($order->status === 'canceled') {
            return response()->json(['message' => 'Transaction already voided'], 422);
        }

        try {
            DB::beginTransaction();

            $order->update(['status' => 'canceled']);

            // Restore stock
            foreach ($order->items as $item) {
                $totalQty = $item->quantity + ($item->bonus_qty ?? 0);
                StockTransaction::create([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $totalQty,
                    'reference_id' => $order->id,
                    'notes' => 'Void: ' . $order->receipt_number,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Transaction voided successfully']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to void transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Mark a bon (credit) transaction as paid.
     */
    public function payBon(string $receiptNumber): JsonResponse
    {
        $order = Order::with('items')
            ->where('receipt_number', $receiptNumber)
            ->where('payment_method', 'bon')
            ->firstOrFail();

        if ($order->status === 'completed') {
            return response()->json(['message' => 'Bon already paid'], 422);
        }

        try {
            DB::beginTransaction();

            $order->update(['status' => 'completed']);

            // Reduce stock now that payment is confirmed
            foreach ($order->items as $item) {
                $totalQty = $item->quantity + ($item->bonus_qty ?? 0);
                StockTransaction::create([
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => $totalQty,
                    'reference_id' => $order->id,
                    'notes' => 'Bon Lunas: ' . $order->receipt_number,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Bon marked as paid successfully']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update bon',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
