<?php

namespace App\Filament\Admin\Resources\DeliveryAssignments\Schemas;

use Filament\Schemas\Schema;

class DeliveryAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('Kurir')
                    ->relationship('user', 'name')
                    ->required(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ])
                    ->required()
                    ->default('pending'),
                \Filament\Forms\Components\DateTimePicker::make('assigned_at'),
                \Filament\Forms\Components\DateTimePicker::make('completed_at'),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
