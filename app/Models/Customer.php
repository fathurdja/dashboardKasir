<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'alamat',
        'telepon',
        'keterangan',
    ];

    protected $appends = [
        'customer_name',
    ];

    /**
     * Alias customer_name for frontend compatibility
     */
    public function getCustomerNameAttribute()
    {
        return $this->name;
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('alamat', 'LIKE', "%{$search}%")
              ->orWhere('telepon', 'LIKE', "%{$search}%");
        });
    }
}
