<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu giỏ hàng và các quan hệ liên quan. */
class Cart extends Model
{
    protected $fillable = ['user_id', 'session_token', 'currency', 'coupon_code', 'note', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->unit_price * $i->quantity);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
