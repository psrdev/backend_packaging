<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

        ];
    }
    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function packedOrders()
    {
        return $this->hasMany(Order::class, 'packer_id');
    }

    public function packingPhotos()
    {
        return $this->hasMany(PackingPhoto::class, 'uploaded_by');
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'changed_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPacker(): bool
    {
        return $this->role === 'packer';
    }
}
