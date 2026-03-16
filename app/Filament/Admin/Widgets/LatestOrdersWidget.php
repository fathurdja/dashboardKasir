<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'default' => 2,
        'lg' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transaksi Terbaru (Hari Ini)')
            ->query(
                Order::query()
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No. Resi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('idr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
