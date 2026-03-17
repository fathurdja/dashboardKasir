<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class FilteredStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 2;

    protected int | array | null $columns = 3;

    protected string $view = 'filament.widgets.filtered-stats-overview';

    public string $filter = 'today';

    public function updatedFilter(): void
    {
        $this->cachedStats = null;
    }

    public function getFilterOptions(): array
    {
        return [
            'today' => 'Hari Ini',
            'week'  => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year'  => 'Tahun Ini',
            'all'   => 'Semua Waktu',
        ];
    }

    public function getFilterLabel(): string
    {
        return $this->getFilterOptions()[$this->filter] ?? 'Hari Ini';
    }

    protected function getHeading(): ?string
    {
        return 'Overview Keseluruhan';
    }

    protected function getStats(): array
    {
        $query = Order::where('status', 'completed');

        $query = match ($this->filter) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week'  => $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereMonth('created_at', Carbon::now()->month)
                             ->whereYear('created_at', Carbon::now()->year),
            'year'  => $query->whereYear('created_at', Carbon::now()->year),
            'all'   => $query,
            default => $query->whereDate('created_at', Carbon::today()),
        };

        $totalSales         = (clone $query)->sum('total_price');
        $totalTransactions  = (clone $query)->count();
        $averageTransaction = (clone $query)->avg('total_price') ?? 0;

        $label = $this->getFilterLabel();

        return [
            Stat::make('Total Penjualan', 'Rp ' . number_format($totalSales, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description('Pendapatan ' . strtolower($label)),

            Stat::make('Jumlah Transaksi', $totalTransactions . ' Transaksi')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->description('Total order selesai'),

            Stat::make('Rata-rata Transaksi', 'Rp ' . number_format($averageTransaction, 0, ',', '.'))
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->description('Per transaksi'),
        ];
    }
}
