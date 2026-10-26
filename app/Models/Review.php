<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu đánh giá sản phẩm và các quan hệ liên quan. */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
    ];

    // ===== Relations =====

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // ===== Scopes =====

    // ===== Accessors =====

    /**
     * Trả về chuỗi sao cho rating (vd: rating=4 → "★★★★☆").
     */
    public function getStarsAttribute(): string
    {
        $r = max(0, min(5, (int) $this->rating));

        return str_repeat('★', $r).str_repeat('☆', 5 - $r);
    }
}
