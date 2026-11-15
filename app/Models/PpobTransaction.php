<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpobTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ref_id',
        'customer_number',
        'product_code',
        'price',
        'iak_price',
        'status',
        'sn',
        'iak_response',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
