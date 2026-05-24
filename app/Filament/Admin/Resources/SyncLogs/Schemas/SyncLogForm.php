<?php

namespace App\Filament\Admin\Resources\SyncLogs\Schemas;

use Filament\Schemas\Schema;

class SyncLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('device_id')
                    ->relationship('device', 'name')
                    ->required(),
                \Filament\Forms\Components\Select::make('sync_type')
                    ->options([
                        'pull' => 'Pull',
                        'push' => 'Push',
                    ])
                    ->required(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->required(),
                \Filament\Forms\Components\TextInput::make('records_processed')
                    ->numeric()
                    ->default(0),
                \Filament\Forms\Components\Textarea::make('error_message')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
