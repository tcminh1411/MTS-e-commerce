<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu thuộc tính sản phẩm và các quan hệ liên quan. */
class ProductAttribute extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'attribute_id', 'is_variation', 'sort_order'];

    protected $casts = ['is_variation' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
