<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends Model
{
    use HasFactory;

    protected $table = 'stock_entries';

    protected $fillable = [
        'tanggal',
        'kdklp',
        'tyunit',
        'tipe',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'user_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'      => 'date:Y-m-d',
        'jumlah'       => 'integer',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
