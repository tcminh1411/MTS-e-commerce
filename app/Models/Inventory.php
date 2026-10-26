<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu tồn kho và các quan hệ liên quan. */
class Inventory extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}
