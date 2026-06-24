<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientFile extends Model
{
    protected $fillable = [
        'patient_id', 'module', 'original_name', 'path', 'mime', 'category', 'size', 'uploaded_by',
    ];

    protected $appends = ['url'];

    /** Relative URL → browser resolves against its own origin (avoids APP_URL host/port drift). */
    public function getUrlAttribute(): string
    {
        return '/storage/' . ltrim($this->path, '/');
    }

    public function patient() { return $this->belongsTo(Patient::class); }
}
