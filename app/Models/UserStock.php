<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStock extends Model
{
    use HasFactory;

    protected $table = 'user_stocks';

    protected $fillable = [
        'user_id',
        'tyunit',
        'kdklp',
        'kuota',
        'stock',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function masterStock()
    {
        return $this->belongsTo(MasterStock::class, 'tyunit', 'TYUNIT');
    }

    /**
     * Ensure all MasterStock products are seeded for this user
     */
    public static function initializeForUser($userId): void
    {
        $all = MasterStock::all();
        foreach ($all as $m) {
            self::firstOrCreate(
                [
                    'user_id' => $userId,
                    'tyunit'  => $m->TYUNIT,
                ],
                [
                    'kdklp' => $m->kdklp,
                    'kuota' => 0,
                    'stock' => 0,
                ]
            );
        }
    }

    /**
     * Synchronize all users with MasterStock
     */
    public static function syncAllFromMasterStock(): void
    {
        $all = MasterStock::all();
        $users = \App\Models\User::all();
        foreach ($users as $u) {
            foreach ($all as $m) {
                self::updateOrCreate(
                    [
                        'user_id' => $u->id,
                        'tyunit'  => $m->TYUNIT,
                    ],
                    [
                        'kdklp' => $m->kdklp,
                        'kuota' => (int) ($m->kuota ?? 0),
                        'stock' => (int) ($m->stock ?? 0),
                    ]
                );
            }
        }
    }
}
