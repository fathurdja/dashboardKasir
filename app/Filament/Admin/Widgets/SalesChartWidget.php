<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
class SalesChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Grafik Penjualan Per Jam (Hari Ini)';

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $today = Carbon::today();

        $orders = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->get();

        $salesByHour = [];
        foreach ($orders as $order) {
            $h = (int) $order->created_at->format('H');
            if (!isset($salesByHour[$h])) {
                $salesByHour[$h] = 0;
            }
            $salesByHour[$h] += $order->total_price;
        }

        $hours = [];
        $data = [];

        // Fill array for 08:00 to 22:00
        for ($i = 8; $i <= 22; $i++) {
            $hours[] = sprintf('%02d:00', $i);
            $data[] = isset($salesByHour[$i]) ? (float) $salesByHour[$i] : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(67, 86, 99, 0.15)',
                    'borderColor' => '#435663',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => '#313647',
                    'pointBorderColor' => '#FFF8D4',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $hours,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
