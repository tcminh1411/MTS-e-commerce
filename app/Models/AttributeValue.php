<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu giá trị thuộc tính và các quan hệ liên quan. */
class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value', 'slug', 'color_code', 'sort_order'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
