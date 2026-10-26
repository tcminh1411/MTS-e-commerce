<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Đại diện dữ liệu danh mục sản phẩm và các quan hệ liên quan. */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /**
     * URL đầy đủ của ảnh — hỗ trợ cả URL external (http) và path storage local.
     */
    public function getImageUrlFullAttribute(): ?string
    {
        $v = $this->image_url;
        if (empty($v)) {
            return null;
        }

        return str_starts_with($v, 'http') ? $v : asset('storage/'.$v);
    }

    public function scopeRoot($q)
    {
        return $q->whereNull('parent_id');
    }
}
