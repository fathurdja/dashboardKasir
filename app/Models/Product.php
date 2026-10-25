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
        'kuota',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            // Sync kuota across the group if kuota changed
            if ($model->wasChanged('kuota') && !empty($model->kdklp)) {
                static::withoutEvents(function () use ($model) {
                    static::where('kdklp', $model->kdklp)
                        ->where('id', '!=', $model->id)
                        ->update(['kuota' => $model->kuota]);

                    MasterStock::withoutEvents(function () use ($model) {
                        MasterStock::where('kdklp', $model->kdklp)
                            ->update(['kuota' => $model->kuota]);
                    });
                });
            }

            // Sync individual stock & price with MasterStock (1-to-1 by code or name)
            if ($model->wasChanged('stock') || $model->wasChanged('price') || $model->wasChanged('name')) {
                $code = sprintf('BV%02d', $model->id);
                MasterStock::withoutEvents(function () use ($model, $code) {
                    $m = MasterStock::find($code) ?? MasterStock::where('NTYUNIT', $model->name)->first();
                    if ($m) {
                        $m->update([
                            'stock' => $model->stock,
                            'hjual' => $model->price,
                            'NTYUNIT' => $model->name,
                            'kdklp' => $model->kdklp,
                            'tglup' => now(),
                        ]);
                    }
                });
            }
        });
    }
}
