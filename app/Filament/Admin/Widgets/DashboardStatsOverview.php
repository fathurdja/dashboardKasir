<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 2;

    protected int | array | null $columns = 4;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalSales = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total_price');

        $totalTransactions = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->count();

        $averageTransaction = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->avg('total_price') ?? 0;

        $lowStockCount = Product::where('is_active', true)
            ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->get()
            ->filter(function ($product) {
                return (($product->stock_in ?? 0) - ($product->stock_out ?? 0)) <= 5;
            })
            ->count();

        return [
            Stat::make('Total Penjualan Hari Ini', 'Rp ' . number_format($totalSales, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Jumlah Transaksi', $totalTransactions . ' Transaksi')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),
            Stat::make('Rata-rata Transaksi', 'Rp ' . number_format($averageTransaction, 0, ',', '.'))
                ->icon('heroicon-o-chart-bar')
                ->color('info'),
            Stat::make('Stok Menipis', $lowStockCount . ' Produk')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->description('Stok <= 5'),
        ];
    }
}
