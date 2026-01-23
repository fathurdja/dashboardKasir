<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterStock extends Model
{
    protected $table = 'munit';
    protected $primaryKey = 'TYUNIT';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'TYUNIT',
        'NTYUNIT',
        'kdklp',
        'hjual',
        'cmodule',
        'userup',
        'tglup'
    ];

    public $timestamps = false; // Karena menggunakan tglup manual

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'tyunit', 'TYUNIT');
    }
}
