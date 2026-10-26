<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Đại diện dữ liệu sản phẩm và các quan hệ liên quan. */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'category_id',
        'brand_id',
        'product_type',
        'short_description',
        'description',
        'price',
        'sale_price',
        'cost_price',
        'currency',
        'weight_gram',
        'length_cm',
        'width_cm',
        'height_cm',
        'status',
        'is_featured',
        'published_at',
        'meta_title',
        'meta_description',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight_gram' => 'integer',
        'is_featured' => 'boolean',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'sold_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function getThumbnailAttribute(): ?string
    {
        $images = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        $img = $images->firstWhere('is_primary', true) ?? $images->first();

        return $img?->url;
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Tính lại rating_avg + rating_count từ các đánh giá đang hiển thị.
     */
    public function recalculateRating(): void
    {
        $stats = $this->reviews()
            ->where('is_visible', true)
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')
            ->first();

        // Direct assign — không phụ thuộc $fillable
        $this->rating_count = (int) ($stats->c ?? 0);
        $this->rating_avg = round((float) ($stats->a ?? 0), 2);
        $this->save();
    }
}
