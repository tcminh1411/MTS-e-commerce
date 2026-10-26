<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Đại diện dữ liệu thuộc tính và các quan hệ liên quan. */
class Attribute extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'sort_order'];

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }
}
