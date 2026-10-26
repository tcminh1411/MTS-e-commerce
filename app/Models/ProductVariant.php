<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Đại diện dữ liệu biến thể sản phẩm và các quan hệ liên quan. */
class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'sale_price',
        'cost_price',
        'weight_gram',
        'image_url',
        'is_default',
        'stock',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_default' => 'boolean',
        'stock' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_attribute_values',
            'variant_id',
            'attribute_value_id'
        );
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    public function getDisplayNameAttribute(): string
    {
        $attrs = $this->relationLoaded('attributeValues')
            ? $this->attributeValues->pluck('value')->implode(' / ')
            : '';

        return $attrs ?: $this->name;
    }
}
