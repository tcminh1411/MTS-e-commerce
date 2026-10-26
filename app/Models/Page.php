<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Đại diện dữ liệu trang tĩnh và các quan hệ liên quan. */
class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'body',
        'meta_title',
        'meta_description',
        'is_active',
        'show_in_footer',
        'footer_sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_footer' => 'boolean',
        'footer_sort' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: chỉ lấy page hiển thị ở footer.
     */
    public function scopeFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true);
    }

    public static function slugify(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'page';
        }

        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
