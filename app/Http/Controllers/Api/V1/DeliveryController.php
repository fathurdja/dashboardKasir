<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Start a delivery shift for today.
     */
    public function startShift(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Check if already active today
        $existing = DeliveryAssignment::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Shift sudah dimulai hari ini',
                'data' => $this->formatAssignment($existing),
            ]);
        }

        $assignment = DeliveryAssignment::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => 'active',
            'started_at' => now()->format('H:i:s'),
        ]);

        return response()->json([
            'message' => 'Shift delivery dimulai',
            'data' => $this->formatAssignment($assignment),
        ], 201);
    }

    /**
     * End a delivery shift for today.
     */
    public function endShift(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $assignment = DeliveryAssignment::where('user_id', $user->id)
            ->where('date', $today)
            ->where('status', 'active')
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'Tidak ada shift aktif hari ini'], 404);
        }

        // Recalculate totals
        $orders = Order::where('delivery_assignment_id', $assignment->id)
            ->where('status', 'completed')
            ->get();

        $assignment->update([
            'status' => 'completed',
            'ended_at' => now()->format('H:i:s'),
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_price'),
            'total_collected' => $orders->where('payment_method', 'cash')->sum('total_price'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Shift delivery selesai',
            'data' => $this->formatAssignment($assignment),
        ]);
    }

    /**
     * Get orders assigned to the current delivery user.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        $orders = Order::with('items.product')
            ->where('delivery_user_id', $user->id)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'receipt_number' => $order->receipt_number,
                'status' => $order->status,
                'total_price' => round((float) $order->total_price, 2),
                'payment_method' => $order->payment_method,
                'delivery_address' => $order->delivery_address,
                'delivery_status' => $order->delivery_status,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        // Summary
        $completed = $orders->where('status', 'completed');

        return response()->json([
            'date' => $date,
            'summary' => [
                'total_orders' => $orders->count(),
                'completed_orders' => $completed->count(),
                'total_sales' => round($completed->sum('total_price'), 2),
                'pending_orders' => $orders->where('delivery_status', 'pending')->count(),
                'on_the_way' => $orders->where('delivery_status', 'on_the_way')->count(),
                'delivered' => $orders->where('delivery_status', 'delivered')->count(),
            ],
            'orders' => $orders->values(),
        ]);
    }

    /**
     * Update delivery status of an order.
     */
    public function updateDeliveryStatus(Request $request, string $receiptNumber): JsonResponse
    {
        $request->validate([
            'delivery_status' => 'required|in:pending,on_the_way,delivered,returned',
        ]);

        $order = Order::where('receipt_number', $receiptNumber)
            ->where('delivery_user_id', $request->user()->id)
            ->firstOrFail();

        $order->update([
            'delivery_status' => $request->delivery_status,
        ]);

        // If delivered + cash, mark as completed
        if ($request->delivery_status === 'delivered' && $order->status !== 'completed') {
            $order->update(['status' => 'completed']);
        }

        return response()->json([
            'message' => 'Status delivery diperbarui',
            'delivery_status' => $order->delivery_status,
        ]);
    }

    /**
     * Performance stats for delivery user.
     */
    public function performance(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'today');

        $query = DeliveryAssignment::where('user_id', $user->id);

        $query = match ($period) {
            'today' => $query->where('date', now()->toDateString()),
            'week' => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('date', now()->month)->whereYear('date', now()->year),
            default => $query->where('date', now()->toDateString()),
        };

        $assignments = $query->get();

        $totalOrders = $assignments->sum('total_orders');
        $totalSales = $assignments->sum('total_sales');
        $totalCollected = $assignments->sum('total_collected');
        $totalShifts = $assignments->where('status', 'completed')->count();
        $avgOrdersPerShift = $totalShifts > 0 ? round($totalOrders / $totalShifts, 1) : 0;

        // Bon outstanding for this delivery person
        $bonOutstanding = Order::where('delivery_user_id', $user->id)
            ->where('payment_method', 'bon')
            ->where('status', 'pending')
            ->sum('total_price');

        return response()->json([
            'period' => $period,
            'total_shifts' => $totalShifts + $assignments->where('status', 'active')->count(),
            'completed_shifts' => $totalShifts,
            'total_orders' => $totalOrders,
            'total_sales' => round((float) $totalSales, 2),
            'total_collected' => round((float) $totalCollected, 2),
            'avg_orders_per_shift' => $avgOrdersPerShift,
            'bon_outstanding' => round((float) $bonOutstanding, 2),
        ]);
    }

    private function formatAssignment(DeliveryAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'date' => $assignment->date->toDateString(),
            'status' => $assignment->status,
            'started_at' => $assignment->started_at,
            'ended_at' => $assignment->ended_at,
            'total_orders' => $assignment->total_orders,
            'total_sales' => round((float) $assignment->total_sales, 2),
            'total_collected' => round((float) $assignment->total_collected, 2),
        ];
    }
}
