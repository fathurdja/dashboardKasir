<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
     protected $fillable = [
        'name',
        'kdklp',
        'price',
        'stock',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            if ($model->wasChanged('stock') && !empty($model->kdklp)) {
                static::withoutEvents(function () use ($model) {
                    static::where('kdklp', $model->kdklp)
                        ->where('id', '!=', $model->id)
                        ->update(['stock' => $model->stock]);

                    MasterStock::withoutEvents(function () use ($model) {
                        MasterStock::where('kdklp', $model->kdklp)
                            ->update(['stock' => $model->stock, 'tglup' => now()]);
                    });
                });
            }
        });
    }
}
