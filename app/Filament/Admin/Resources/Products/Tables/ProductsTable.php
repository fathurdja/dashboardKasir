<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Thumbnail')
                    ->square(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->state(fn (Product $record) => $record->getCurrentStock())
                    ->badge()
                    ->color(function ($state): string {
                        if ($state <= 0) return 'danger';
                        if ($state <= 5) return 'warning';
                        return 'success';
                    }),
                TextColumn::make('purchase_price')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'out_of_stock' => 'Stok Habis (0)',
                        'low_stock' => 'Stok Menipis (<= 5)',
                        'in_stock' => 'Tersedia (> 5)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        
                        $sql = '(COALESCE((SELECT SUM(quantity) FROM stock_transactions WHERE product_id = products.id AND type = "in"), 0) - COALESCE((SELECT SUM(quantity) FROM stock_transactions WHERE product_id = products.id AND type = "out"), 0))';
                        
                        return match ($data['value']) {
                            'out_of_stock' => $query->whereRaw("$sql <= 0"),
                            'low_stock' => $query->whereRaw("$sql > 0 AND $sql <= 5"),
                            'in_stock' => $query->whereRaw("$sql > 5"),
                            default => $query,
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
