<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'receipt_number',
        'status',
        'order_type',
        'total_price',
        'tax_amount',
        'discount_amount',
        'payment_method',
        'xendit_external_id',
        'xendit_invoice_url',
        'xendit_status',
        // Mobile integration fields
        'device_id',
        'received_amount',
        'change_amount',
        'customer_name',
        'customer_phone',
        'due_date',
        'notes',
        // Delivery fields
        'delivery_user_id',
        'delivery_assignment_id',
        'delivery_address',
        'delivery_status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function xenditPayment(): HasOne
    {
        return $this->hasOne(XenditPayment::class);
    }

    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class);
    }
}
