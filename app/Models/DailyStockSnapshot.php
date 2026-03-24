<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStockSnapshot extends Model
{
    protected $fillable = [
        'product_id',
        'date',
        'opening_stock',
        'closing_stock',
        'sold_qty',
        'added_qty',
        'bonus_qty',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
