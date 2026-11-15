<?php

namespace App\Filament\Admin\Widgets;

use App\Models\DeliveryAssignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class DeliveryPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Performa Delivery (Hari Ini)')
            ->query(
                DeliveryAssignment::query()
                    ->with('user')
                    ->whereDate('date', Carbon::today())
                    ->orderByDesc('total_sales')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Delivery Person')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-truck'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '-'),
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Selesai')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '-'),
                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Penjualan')
                    ->money('idr')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_collected')
                    ->label('Cash Dikumpulkan')
                    ->money('idr')
                    ->sortable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada delivery hari ini')
            ->emptyStateDescription('Delivery person belum memulai shift')
            ->emptyStateIcon('heroicon-o-truck');
    }
}
