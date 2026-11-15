<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class SlowMovingProductsWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = [
        'default' => 2,
        'lg' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('🐌 Produk Kurang Laku')
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
                    ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
                    ->withCount(['stockTransactions as sale_count' => fn($q) =>
                        $q->where('type', 'out')
                            ->where('created_at', '>=', now()->subDays(30))
                    ])
                    ->orderBy('sale_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-archive-box')
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
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
                Tables\Columns\TextColumn::make('sale_count')
                    ->label('Transaksi (30hr)')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('idr')
                    ->sortable(),
            ])
            ->paginated(true)
            ->defaultPaginationPageOption(5);
    }
}
