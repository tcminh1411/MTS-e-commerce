<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Đại diện dữ liệu lịch sử biến động kho và các quan hệ liên quan. */
class StockMovement extends Model
{
    public const TYPES = [
        'in' => 'Nhập kho',
        'out' => 'Xuất kho',
        'return' => 'Hoàn kho',
        'adjust' => 'Điều chỉnh',
    ];

    public const REFERENCE_TYPES = [
        'manual' => 'Thủ công',
        'order' => 'Đơn hàng',
        'return' => 'Trả hàng',
    ];

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
