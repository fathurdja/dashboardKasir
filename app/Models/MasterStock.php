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
        'stock',
        'cmodule',
        'userup',
        'tglup'
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            if ($model->wasChanged('stock') && !empty($model->kdklp)) {
                static::withoutEvents(function () use ($model) {
                    static::where('kdklp', $model->kdklp)
                        ->where('TYUNIT', '!=', $model->TYUNIT)
                        ->update(['stock' => $model->stock, 'tglup' => now()]);

                    Product::withoutEvents(function () use ($model) {
                        Product::where('kdklp', $model->kdklp)
                            ->update(['stock' => $model->stock]);
                    });
                });
            }
        });
    }

    public $timestamps = false; // Karena menggunakan tglup manual

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'tyunit', 'TYUNIT');
    }
}
