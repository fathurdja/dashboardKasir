<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transaksi';
    protected $primaryKey = 'id_transaksi';   // ✅ WAJIB
    public $incrementing = false;             // ✅ karena bukan auto increment
    protected $keyType = 'string';            // ✅ karena id_transaksi string
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

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'id_transaksi', 'id_transaksi');
    }
}
