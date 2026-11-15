<?php

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SalesChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Grafik Penjualan 7 Hari Terakhir';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Using basic group by since flowframe/trend might not be installed
        $data = Order::selectRaw('DATE(created_at) as date, sum(total_price) as total')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $labels = $data->pluck('date')->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d M'))->toArray();
        $values = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Omzet',
                    'data' => $values,
                    'backgroundColor' => '#8BAE66',
                    'borderColor' => '#8BAE66',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
