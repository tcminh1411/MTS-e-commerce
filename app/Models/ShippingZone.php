<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Đại diện dữ liệu khu vực vận chuyển và các quan hệ liên quan. */
class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'provinces',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'provinces' => 'array',
        'is_active' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }

    /** Scope: chỉ zone đang active, sắp theo sort_order. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
