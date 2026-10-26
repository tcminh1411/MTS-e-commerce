<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Đại diện dữ liệu mã giảm giá và các quan hệ liên quan. */
class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'max_discount_amount' => 'integer',
        'min_order_amount' => 'integer',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(CouponCondition::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Coupon đang trong thời gian hiệu lực và còn lượt dùng.
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /**
     * Coupon này có giới hạn theo category/product/brand không?
     */
    public function hasConditions(): bool
    {
        return $this->conditions()->exists();
    }

    /**
     * Tính số tiền được giảm từ subtotal (đã thoả min_order_amount).
     */
    public function calcDiscount(int $subtotal): int
    {
        if ($subtotal < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = (int) floor($subtotal * $this->value / 100);

            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }

            return $discount;
        }

        // type = 'fixed'
        return min($this->value, $subtotal);
    }
}
