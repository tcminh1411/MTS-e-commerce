<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/** Đại diện dữ liệu tài khoản người dùng và các quan hệ liên quan. */
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'email',
        'phone',
        'password',
        'full_name',
        'avatar_url',
        'gender',
        'birthday',
        'status',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'birthday' => 'date',
    ];

    /* ---------- Relations ---------- */

    /* ---------- Helpers ---------- */
    /**
     * URL đầy đủ của avatar — hỗ trợ cả URL external (http) và path storage local.
     */
    public function getAvatarUrlFullAttribute(): ?string
    {
        $v = $this->avatar_url;
        if (empty($v)) {
            return null;
        }

        return str_starts_with($v, 'http') ? $v : asset('storage/'.$v);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
