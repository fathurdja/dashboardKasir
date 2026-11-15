<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\StockTransaction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustStock')
                ->label('Adjust Stock')
                ->color('warning')
                ->icon('heroicon-o-adjustments-horizontal')
                ->form([
                    Select::make('type')
                        ->label('Tipe Transaksi')
                        ->options([
                            'in' => 'Masuk (Tambah Stok)',
                            'out' => 'Keluar (Kurangi Stok)',
                        ])
                        ->required()
                        ->default('in'),
                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->maxLength(255),
                ])
                ->action(function (array $data, Action $action) {
                    StockTransaction::create([
                        'product_id' => $this->record->id,
                        'type' => $data['type'],
                        'quantity' => $data['quantity'],
                        'notes' => $data['notes'],
                    ]);
                })
                ->modalWidth('md'),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
