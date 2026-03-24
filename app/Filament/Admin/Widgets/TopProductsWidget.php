<?php

namespace App\Filament\Admin\Widgets;

use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = [
        'default' => 2,
        'lg' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('🏆 Produk Terlaris')
            ->query(
                // We use DB::table to avoid Eloquent SoftDeletes appending "where order_items.deleted_at"
                // to the outer paginated wrapper query, which breaks the subquery.
                OrderItem::query()->withoutGlobalScopes()
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable()
                    ->icon('heroicon-o-fire')
                    ->iconColor('danger'),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Terjual')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('idr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_count')
                    ->label('Order')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->modifyQueryUsing(function ($query) {
                // By using modifyQueryUsing, we bypass the need for fromSub() and its scoping bugs
                // We must use order_items.id in group by to satisfy only_full_group_by when defaultSort kicks in
                $query->select(
                    DB::raw('MAX(order_items.id) as id'),
                    'order_items.product_id',
                    'order_items.product_name',
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.subtotal) as total_revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as order_count')
                )
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', '!=', 'canceled')
                ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.id');
            })
            ->defaultSort('total_qty', 'desc')
            ->paginated(true)
            ->defaultPaginationPageOption(5);
    }
}
