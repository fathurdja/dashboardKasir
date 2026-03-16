<?php

namespace App\Filament\Admin\Resources\Orders\Tables;


use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\StockTransaction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('receipt_number')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'primary',
                    })
                    ->searchable(),
                TextColumn::make('order_type')
                    ->searchable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jml Item')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->recordActions([
                Action::make('PrintBill')
                    ->label('Cetak Struk')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('orders.print', $record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                Action::make('changeStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'canceled' => 'Canceled',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status)
                    ])
                    ->action(function ($record, array $data): void {
                        $oldStatus = $record->status;
                        $newStatus = $data['status'];
                        
                        if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                            foreach ($record->items as $item) {
                                StockTransaction::create([
                                    'product_id' => $item->product_id,
                                    'variant_id' => $item->variant_id,
                                    'type' => 'out',
                                    'quantity' => $item->quantity,
                                    'reference_id' => $record->id,
                                    'notes' => 'Penjualan (Ubah Status): ' . $record->receipt_number,
                                ]);
                            }
                        }
                        
                        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                            foreach ($record->items as $item) {
                                StockTransaction::create([
                                    'product_id' => $item->product_id,
                                    'variant_id' => $item->variant_id,
                                    'type' => 'in',
                                    'quantity' => $item->quantity,
                                    'reference_id' => $record->id,
                                    'notes' => 'Return (Ubah Status): ' . $record->receipt_number,
                                ]);
                            }
                        }
                        
                        $record->update(['status' => $newStatus]);
                    }),
            ])
            ->bulkActions([
                // Read-only
            ]);
    }
}
