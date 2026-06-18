<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title_ar', 'title_en',
        'subtitle_ar', 'subtitle_en',
        'image', 'link',
        'btn_text_ar', 'btn_text_en',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
