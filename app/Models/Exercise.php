<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $fillable = ['name', 'category', 'instructions', 'image_url', 'video_url', 'tags', 'is_active'];

    protected $casts = ['tags' => 'array', 'is_active' => 'boolean'];
}
