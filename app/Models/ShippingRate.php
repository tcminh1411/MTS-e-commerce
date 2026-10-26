<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Đại diện dữ liệu mức phí vận chuyển và các quan hệ liên quan. */
class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'name',
        'min_weight_gram',
        'max_weight_gram',
        'min_order_value',
        'max_order_value',
        'fee',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    /**
     * Filter các rate khả dụng cho subtotal nhất định.
     * Rate có min_order_value NULL hoặc <= subtotal → pass.
     * Rate có max_order_value NULL hoặc >= subtotal → pass.
     */
    public function scopeForSubtotal($query, int $subtotal)
    {
        return $query->where(function ($q) use ($subtotal) {
            $q->whereNull('min_order_value')
                ->orWhere('min_order_value', '<=', $subtotal);
        })->where(function ($q) use ($subtotal) {
            $q->whereNull('max_order_value')
                ->orWhere('max_order_value', '>=', $subtotal);
        });
    }

    /**
     * Filter các rate khả dụng cho tổng weight (gram) nhất định.
     */
    public function scopeForWeight($query, int $totalWeightGram)
    {
        return $query->where(function ($q) use ($totalWeightGram) {
            $q->whereNull('min_weight_gram')
                ->orWhere('min_weight_gram', '<=', $totalWeightGram);
        })->where(function ($q) use ($totalWeightGram) {
            $q->whereNull('max_weight_gram')
                ->orWhere('max_weight_gram', '>=', $totalWeightGram);
        });
    }
}
