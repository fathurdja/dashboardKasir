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
        'kuota',
        'cmodule',
        'userup',
        'tglup'
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            // Sync kuota across the group if kuota changed
            if ($model->wasChanged('kuota') && !empty($model->kdklp)) {
                static::withoutEvents(function () use ($model) {
                    static::where('kdklp', $model->kdklp)
                        ->where('TYUNIT', '!=', $model->TYUNIT)
                        ->update(['kuota' => $model->kuota]);

                    Product::withoutEvents(function () use ($model) {
                        Product::where('kdklp', $model->kdklp)
                            ->update(['kuota' => $model->kuota]);
                    });
                });
            }

            // Sync individual stock & price with Product 1-to-1
            if ($model->wasChanged('stock') || $model->wasChanged('hjual') || $model->wasChanged('NTYUNIT')) {
                Product::withoutEvents(function () use ($model) {
                    $prodId = str_starts_with($model->TYUNIT, 'BV') ? (int) substr($model->TYUNIT, 2) : null;
                    $p = $prodId ? Product::find($prodId) : Product::where('name', $model->NTYUNIT)->first();
                    if ($p) {
                        $p->update([
                            'stock' => $model->stock,
                            'price' => $model->hjual,
                            'name'  => $model->NTYUNIT,
                            'kdklp' => $model->kdklp,
                        ]);
                    }
                });
            }
        });
    }

    public $timestamps = false; // Karena menggunakan tglup manual

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'tyunit', 'TYUNIT');
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("
            FIELD(kdklp, 'GALON', 'BOTOL_600', 'BOTOL_1500', 'GELAS'),
            CASE 
                WHEN NTYUNIT LIKE '%isi saja%' THEN 1 
                WHEN NTYUNIT LIKE '%+ isi%' OR NTYUNIT LIKE '%+%' THEN 2 
                ELSE 3 
            END,
            hjual ASC
        ");
    }
}
