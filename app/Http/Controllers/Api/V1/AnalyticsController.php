<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Daily summary — sales, orders, items, avg ticket, payment breakdown.
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $orders = Order::whereDate('created_at', $date)
            ->where('status', '!=', 'canceled');

        $totalSales = (clone $orders)->sum('total_price');
        $totalOrders = (clone $orders)->count();
        $totalTax = (clone $orders)->sum('tax_amount');

        // Items sold count
        $orderIds = (clone $orders)->pluck('id');
        $itemsSold = OrderItem::whereIn('order_id', $orderIds)->sum('quantity');
        $avgTicket = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Payment breakdown
        $paymentBreakdown = (clone $orders)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method')
            ->map(fn ($item) => [
                'count' => $item->count,
                'total' => round((float) $item->total, 2),
            ]);

        // Comparison with yesterday
        $yesterday = Order::whereDate('created_at', now()->parse($date)->subDay())
            ->where('status', '!=', 'canceled')
            ->sum('total_price');

        $salesChangePercent = $yesterday > 0
            ? round((($totalSales - $yesterday) / $yesterday) * 100, 1)
            : 0;

        return response()->json([
            'date' => $date,
            'total_sales' => round((float) $totalSales, 2),
            'total_orders' => $totalOrders,
            'items_sold' => (int) $itemsSold,
            'avg_ticket' => $avgTicket,
            'total_tax' => round((float) $totalTax, 2),
            'payment_breakdown' => $paymentBreakdown,
            'comparison_yesterday' => [
                'sales_change_pct' => $salesChangePercent,
            ],
        ]);
    }

    /**
     * Hourly performance for bar chart.
     */
    public function hourly(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $hourlyData = Order::whereDate('created_at', $date)
            ->where('status', '!=', 'canceled')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_price) as sales')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->map(fn ($item) => [
                'hour' => (int) $item->hour,
                'label' => sprintf('%02d:00', $item->hour),
                'orders' => $item->orders,
                'sales' => round((float) $item->sales, 2),
            ]);

        return response()->json([
            'date' => $date,
            'data' => $hourlyData,
        ]);
    }

    /**
     * Periodic summary (week or month).
     */
    public function summary(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');

        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $orders = Order::where('created_at', '>=', $startDate)
            ->where('status', '!=', 'canceled');

        $totalSales = (clone $orders)->sum('total_price');
        $totalOrders = (clone $orders)->count();
        $avgTicket = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Daily breakdown
        $dailyData = (clone $orders)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_price) as sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'orders' => $item->orders,
                'sales' => round((float) $item->sales, 2),
            ]);

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'total_sales' => round((float) $totalSales, 2),
            'total_orders' => $totalOrders,
            'avg_ticket' => $avgTicket,
            'daily_data' => $dailyData,
        ]);
    }

    /**
     * Top selling products by quantity.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $date = $request->input('date', null);

        $query = OrderItem::select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'canceled');

        if ($date) {
            $query->whereDate('orders.created_at', $date);
        }

        $topProducts = $query
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name ?? 'Unknown',
                'total_qty' => (int) $item->total_qty,
                'total_revenue' => round((float) $item->total_revenue, 2),
            ]);

        return response()->json([
            'data' => $topProducts,
        ]);
    }

    /**
     * Bon/credit report — outstanding unpaid orders.
     */
    public function bonReport(): JsonResponse
    {
        $bonOrders = Order::with('items.product')
            ->where('payment_method', 'bon')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($order) => [
                'receipt_number' => $order->receipt_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total_price' => round((float) $order->total_price, 2),
                'due_date' => $order->due_date?->toDateString(),
                'created_at' => $order->created_at->toIso8601String(),
                'is_overdue' => $order->due_date && $order->due_date->isPast(),
                'items_count' => $order->items->count(),
            ]);

        $totalOutstanding = $bonOrders->sum('total_price');

        return response()->json([
            'total_outstanding' => round($totalOutstanding, 2),
            'count' => $bonOrders->count(),
            'data' => $bonOrders,
        ]);
    }
}
