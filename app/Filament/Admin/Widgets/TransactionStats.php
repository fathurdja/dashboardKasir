<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TransactionStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Transaction::sum('total');

        return [
            Stat::make('Total Transaksi', Transaction::count())
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Total Omzet', Number::format($total))
                ->description('IDR')
                ->descriptionIcon('heroicon-o-banknotes')
                ->extraAttributes([
                    'class' => 'text-lg', // kecilkan font value
                ])
                ->columnSpan(2), // ⬅ kasih ruang lebih lebar

            Stat::make('LUNAS', Transaction::where('status', 'LUNAS')->count())
                ->color('success'),

            Stat::make('BON', Transaction::where('status', 'BON')->count())
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // grid 4 kolom
    }
}
