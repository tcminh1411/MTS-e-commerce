<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu hình ảnh sản phẩm và các quan hệ liên quan. */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'variant_id', 'url', 'alt', 'sort_order', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getUrlAttribute(mixed $v): string
    {
        return str_starts_with((string) $v, 'http') ? $v : asset('storage/'.$v);
    }
}
