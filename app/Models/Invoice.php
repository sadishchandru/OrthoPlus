<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'series', 'type', 'invoice_no', 'items',
        'subtotal', 'discount', 'tax', 'total',
        'status', 'payment_method', 'insurance_claim', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'insurance_claim' => 'array',
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }

    public static function generateInvoiceNo(string $series = 'INV'): string
    {
        $last = self::withTrashed()->where('series', $series)->orderByDesc('id')->first();
        $num = $last ? (intval(substr($last->invoice_no, strlen($series) + 1)) + 1) : 1;
        return $series . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
