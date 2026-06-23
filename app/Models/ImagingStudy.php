<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagingStudy extends Model
{
    protected $fillable = [
        'imaging_order_id', 'patient_id', 'study_date', 'technician_id',
        'images', 'report_url', 'radiologist_notes', 'reported_at',
    ];

    protected $casts = [
        'images'      => 'array',
        'study_date'  => 'date',
        'reported_at' => 'datetime',
    ];

    public function order()   { return $this->belongsTo(ImagingOrder::class, 'imaging_order_id'); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
