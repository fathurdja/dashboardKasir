<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transaksi';
    protected $primaryKey = 'id_transaksi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'customer_name',
        'alamat',
        'tanggal',
        'total',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];
    

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'id_transaksi', 'id_transaksi');
    }
}
