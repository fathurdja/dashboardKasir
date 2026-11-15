<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'device_id',
        'direction',
        'records_synced',
        'status',
        'error_message',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
