<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'status',
        'total_orders',
        'total_sales',
        'total_collected',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_sales' => 'decimal:2',
            'total_collected' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Recalculate totals from linked orders.
     */
    public function recalculate(): self
    {
        $orders = $this->orders()->where('status', 'completed')->get();
        $this->update([
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total_price'),
        ]);
        return $this;
    }
}
