<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'guard_name', 'page_access'];

    protected $casts = ['page_access' => 'array'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}
