<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagingOrder extends Model
{
    protected $fillable = [
        'patient_id', 'ordered_by', 'modality', 'body_part',
        'clinical_indication', 'ordered_at', 'status',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function studies() { return $this->hasMany(ImagingStudy::class); }
}
