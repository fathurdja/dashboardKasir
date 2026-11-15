<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'default' => 2,
        'lg' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Produk Stok Menipis (<= 5)')
            ->query(
                // Mengambil semua produk aktif dan menghitung stok berdasarkan transaksi masuk dan keluar
                Product::query()
                    ->where('is_active', true)
                    ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
                    ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Sisa Stok')
                    ->state(function (Product $record): int {
                        $in = $record->stock_in ?? 0;
                        $out = $record->stock_out ?? 0;
                        return $in - $out;
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state <= 0) return 'danger';
                        if ($state <= 5) return 'warning';
                        return 'success';
                    }),
            ])
            // Filter dilakukan via collection setelah query dijalankan. Filament akan 
            // mendapatkan seluruh data sesuai per page jika kita tidak filter di query builder.
            // Sebagai alternatif, kita menggunakan subquery WHERE raw:
            ->modifyQueryUsing(function ($query) {
                // Berfungsi disemua driver db relasional standar
                $query->whereRaw('(
                    COALESCE((SELECT SUM(quantity) FROM stock_transactions WHERE product_id = products.id AND type = "in"), 0) -
                    COALESCE((SELECT SUM(quantity) FROM stock_transactions WHERE product_id = products.id AND type = "out"), 0)
                ) <= 5');
            })
            ->paginated(true)
            ->defaultPaginationPageOption(5);
    }
}
