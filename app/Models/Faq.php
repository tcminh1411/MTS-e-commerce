<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu câu hỏi thường gặp và các quan hệ liên quan. */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
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

    // ===== Helpers =====

    /**
     * Danh sách category cố định (dùng cho dropdown form).
     */
    public static function categories(): array
    {
        return [
            'order' => 'Đơn hàng',
            'payment' => 'Thanh toán',
            'shipping' => 'Vận chuyển',
            'return' => 'Đổi trả / Hoàn tiền',
            'account' => 'Tài khoản',
            'other' => 'Khác',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? 'Khác';
    }
}
