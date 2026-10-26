<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu banner quảng cáo và các quan hệ liên quan. */
class Banner extends Model
{
    use HasFactory;

    public const POSITIONS = [
        'home_top' => 'Đầu trang chủ',
        'home_middle' => 'Giữa trang chủ',
        'home_bottom' => 'Cuối trang chủ',
    ];

    protected $fillable = [
        'title',
        'image',
        'link',
        'position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    // ===== Accessors =====

    /**
     * Trả URL ảnh — giống pattern ProductImage.
     */
    public function getImageUrlAttribute(): string
    {
        $v = (string) $this->image;

        return str_starts_with($v, 'http') ? $v : asset('storage/'.$v);
    }

    public function getPositionLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }
}
