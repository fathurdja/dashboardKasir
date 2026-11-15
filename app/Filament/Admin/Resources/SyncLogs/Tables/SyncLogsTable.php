<?php

namespace App\Filament\Admin\Resources\SyncLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('device.name')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sync_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pull' => 'info',
                        'push' => 'success',
                        default => 'primary',
                    }),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                \Filament\Tables\Columns\TextColumn::make('records_processed')->numeric(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Sinkronisasi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
