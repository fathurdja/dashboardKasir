<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('username')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('password')
                ->requiredMapping()
                ->rules(['required', 'min:6']),
            ImportColumn::make('role')
                ->rules(['nullable', 'in:admin,kasir,delivery']),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Try to find the user by username to update, or return a new user.
        return User::firstOrNew([
            'username' => $this->data['username'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    protected function beforeSave(): void
    {
        // Hash the password if it's set in the data
        if (isset($this->data['password'])) {
            $this->record->password = Hash::make($this->data['password']);
        }
        
        // Default role if not provided
        if (!isset($this->data['role'])) {
            $this->record->role = 'kasir';
        }
    }
}
