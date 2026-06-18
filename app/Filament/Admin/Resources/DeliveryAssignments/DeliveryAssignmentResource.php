<?php

namespace App\Filament\Admin\Resources\DeliveryAssignments;

use App\Filament\Admin\Resources\DeliveryAssignments\Pages\CreateDeliveryAssignment;
use App\Filament\Admin\Resources\DeliveryAssignments\Pages\EditDeliveryAssignment;
use App\Filament\Admin\Resources\DeliveryAssignments\Pages\ListDeliveryAssignments;
use App\Filament\Admin\Resources\DeliveryAssignments\Schemas\DeliveryAssignmentForm;
use App\Filament\Admin\Resources\DeliveryAssignments\Tables\DeliveryAssignmentsTable;
use App\Models\DeliveryAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryAssignmentResource extends Resource
{
    protected static ?string $model = DeliveryAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static \UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DeliveryAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryAssignments::route('/'),
            'create' => CreateDeliveryAssignment::route('/create'),
            'edit' => EditDeliveryAssignment::route('/{record}/edit'),
        ];
    }
}

