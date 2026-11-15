<?php

namespace App\Models;

<<<<<<< HEAD
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
=======
// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
<<<<<<< HEAD
    use HasApiTokens, HasFactory, Notifiable;
=======
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Allow all users in the database for now to fix the 403 error.
    }
>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
<<<<<<< HEAD
        'email',
        'role',
=======

>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

<<<<<<< HEAD
    /**
     * Determine if user is admin
     */
    public function isAdmin(): bool
    {
        return strtolower($this->role ?? '') === 'admin';
    }

    /**
     * Filament Panel access control: only admin can access admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * User's transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

    /**
     * User's allocated stock per variant
     */
    public function stocks()
    {
        return $this->hasMany(UserStock::class, 'user_id', 'id');
=======
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDelivery(): bool
    {
        return $this->role === 'delivery';
    }

    public function deliveryAssignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_user_id');
>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e
    }
}
