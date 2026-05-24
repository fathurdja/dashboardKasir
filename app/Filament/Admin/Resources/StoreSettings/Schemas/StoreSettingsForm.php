<?php

namespace App\Filament\Admin\Resources\StoreSettings\Schemas;

use Filament\Schemas\Schema;

class StoreSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nama Toko')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('store_code')
                    ->label('Kode Toko')
                    ->required()
                    ->maxLength(10),
                \Filament\Forms\Components\TextInput::make('phone')
                    ->label('No Telepon')
                    ->tel()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('tax_rate')
                    ->label('Persentase Pajak (%)')
                    ->numeric()
                    ->default(11.00),
                \Filament\Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Section::make('Xendit Settings')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('xendit_public_key')
                            ->password()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('xendit_secret_key')
                            ->password()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('xendit_webhook_token')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->collapsed(),
            ]);
    }
}
