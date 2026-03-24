<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class DailyStockReportWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Laporan Stok Harian — ' . Carbon::today()->format('d M Y'))
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
                    ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
                    ->withSum([
                        'stockTransactions as sold_today' => fn($q) =>
                            $q->where('type', 'out')->whereDate('created_at', Carbon::today())
                    ], 'quantity')
                    ->withSum([
                        'stockTransactions as added_today' => fn($q) =>
                            $q->where('type', 'in')->whereDate('created_at', Carbon::today())
                    ], 'quantity')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opening_stock')
                    ->label('Stok Awal')
                    ->state(function (Product $record): int {
                        $currentStock = ($record->stock_in ?? 0) - ($record->stock_out ?? 0);
                        $soldToday = $record->sold_today ?? 0;
                        $addedToday = $record->added_today ?? 0;
                        return max(0, $currentStock + $soldToday - $addedToday);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('added_today')
                    ->label('Masuk')
                    ->state(fn (Product $record): int => $record->added_today ?? 0)
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('sold_today')
                    ->label('Terjual')
                    ->state(fn (Product $record): int => $record->sold_today ?? 0)
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('closing_stock')
                    ->label('Stok Akhir')
                    ->state(fn (Product $record): int =>
                        max(0, ($record->stock_in ?? 0) - ($record->stock_out ?? 0))
                    )
                    ->alignCenter()
                    ->badge()
                    ->color(function (int $state): string {
                        if ($state <= 0) return 'danger';
                        if ($state <= 5) return 'warning';
                        return 'success';
                    }),
            ])
            ->defaultSort('name')
            ->paginated(true)
            ->defaultPaginationPageOption(10);
    }
}
