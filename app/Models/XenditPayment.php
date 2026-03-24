<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XenditPayment extends Model
{
    protected $fillable = [
        'order_id',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_string',
        'amount',
        'status',
        'paid_at',
        'expires_at',
        'xendit_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'xendit_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
