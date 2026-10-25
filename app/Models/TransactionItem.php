<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $table = 'transaksi_items';
    protected $primaryKey = 'id'; // kalau ada kolom id
    public $timestamps = false;

    protected $fillable = [
        'id_transaksi',
        'tyunit',
        'nama_barang',
        'harga',
        'quantity',
        'bonus',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaksi', 'id_transaksi');
    }
}
