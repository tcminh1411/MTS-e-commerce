<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Đại diện dữ liệu điều kiện mã giảm giá và các quan hệ liên quan. */
class CouponCondition extends Model
{
    protected $fillable = [
        'coupon_id',
        'condition_type',
        'condition_id',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
