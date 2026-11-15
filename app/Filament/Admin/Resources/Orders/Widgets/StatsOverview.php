<?php

namespace App\Filament\Admin\Resources\Orders\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use App\Models\Order;
use App\Models\DeliveryAssignment;
use Carbon\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $today = Carbon::today();
        
        $totalOmzet = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total_price');
            
        $totalPesanan = Order::whereDate('created_at', $today)->count();
        
        $activeDeliveries = DeliveryAssignment::whereIn('status', ['pending', 'in_transit'])->count();

        return [
            Stat::make('Total Omzet Hari Ini', 'Rp ' . number_format($totalOmzet, 0, ',', '.'))
                ->description('Pemasukan hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Pesanan', $totalPesanan)
                ->description('Pesanan masuk hari ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            Stat::make('Kurir Aktif', $activeDeliveries)
                ->description('Pengiriman sedang berjalan')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
