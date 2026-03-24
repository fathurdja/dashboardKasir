<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get daily summary for a given date.
     */
    public function getDailySummary(string $date): array
    {
        $orders = Order::whereDate('created_at', $date)
            ->where('status', '!=', 'canceled');

        $totalSales = (clone $orders)->sum('total_price');
        $totalOrders = (clone $orders)->count();

        $orderIds = (clone $orders)->pluck('id');
        $itemsSold = OrderItem::whereIn('order_id', $orderIds)->sum('quantity');
        $avgTicket = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        $paymentBreakdown = (clone $orders)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method')
            ->map(fn ($item) => [
                'count' => $item->count,
                'total' => round((float) $item->total, 2),
            ]);

        return [
            'date' => $date,
            'total_sales' => round((float) $totalSales, 2),
            'total_orders' => $totalOrders,
            'items_sold' => (int) $itemsSold,
            'avg_ticket' => $avgTicket,
            'payment_breakdown' => $paymentBreakdown,
        ];
    }

    /**
     * Get hourly performance for bar chart.
     */
    public function getHourlyPerformance(string $date): array
    {
        return Order::whereDate('created_at', $date)
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
            ])
            ->toArray();
    }

    /**
     * Get top selling products.
     */
    public function getTopProducts(int $limit = 10, ?string $date = null): array
    {
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

        return $query
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name ?? 'Unknown',
                'total_qty' => (int) $item->total_qty,
                'total_revenue' => round((float) $item->total_revenue, 2),
            ])
            ->toArray();
    }

    /**
     * Get outstanding bon/credit report.
     */
    public function getBonReport(): array
    {
        $bonOrders = Order::where('payment_method', 'bon')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return [
            'total_outstanding' => round((float) $bonOrders->sum('total_price'), 2),
            'count' => $bonOrders->count(),
            'orders' => $bonOrders->map(fn ($order) => [
                'receipt_number' => $order->receipt_number,
                'customer_name' => $order->customer_name,
                'total_price' => round((float) $order->total_price, 2),
                'due_date' => $order->due_date?->toDateString(),
                'is_overdue' => $order->due_date && $order->due_date->isPast(),
            ])->toArray(),
        ];
    }
}
