<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Đại diện dữ liệu đơn hàng và các quan hệ liên quan. */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'total',
        'coupon_code',
        'shipping_zone_id',
        'shipping_rate_id',
        'shipping_rate_name',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'shipping_address',
        'note',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'subtotal' => 'integer',
        'discount_amount' => 'integer',
        'shipping_fee' => 'integer',
        'total' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function returnRequest(): HasOne
    {
        return $this->hasOne(ReturnRequest::class);
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    protected static function booted(): void
    {
        // Tự động cộng/trừ sold_count khi trạng thái đơn hàng thay đổi
        static::updated(function ($order) {
            // Nếu đơn hàng chuyển sang "Hoàn thành" (completed)
            if ($order->wasChanged('status') && $order->status === 'completed') {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        \App\Models\Product::where('id', $item->product_id)->increment('sold_count', $item->quantity);
                    }
                }
            }

            // Nếu đơn hàng bị chuyển sang "Đã hủy" (cancelled) thì trừ đi
            if ($order->wasChanged('status') && $order->status === 'cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        \App\Models\Product::where('id', $item->product_id)->decrement('sold_count', $item->quantity);
                    }
                }
            }
        });
    }
}
