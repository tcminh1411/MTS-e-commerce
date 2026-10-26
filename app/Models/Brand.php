<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu thương hiệu và các quan hệ liên quan. */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_url', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * URL đầy đủ của logo — hỗ trợ cả URL external (http) và path storage local.
     */
    public function getLogoUrlFullAttribute(): ?string
    {
        $v = $this->logo_url;
        if (empty($v)) {
            return null;
        }

        return str_starts_with($v, 'http') ? $v : asset('storage/'.$v);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
