<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'tax_id',
        'tax_rate',
        'currency',
        'xendit_public_key',
        'xendit_secret_key',
        'xendit_webhook_token',
        'store_code',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
        ];
    }

    /**
     * Get the singleton store settings.
     */
    public static function current(): ?self
    {
        return static::first();
    }
}
