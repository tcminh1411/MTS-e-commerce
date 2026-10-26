<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Đại diện yêu cầu trả hàng và các quan hệ liên quan. */
class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'status',
        'admin_note',
        'refund_amount',
        'refund_method',
        'processed_by_user_id',
        'requested_at',
        'processed_at',
        'refunded_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Chờ xử lý',
        'approved' => 'Đã chấp nhận',
        'rejected' => 'Đã từ chối',
        'refunded' => 'Đã hoàn tiền',
    ];

    public const REFUND_METHODS = [
        'cash' => 'Tiền mặt',
        'bank_transfer' => 'Chuyển khoản',
        'vnpay' => 'VNPay',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
