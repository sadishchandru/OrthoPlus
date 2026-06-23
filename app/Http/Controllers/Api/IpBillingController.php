<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\IpBill;
use Illuminate\Http\Request;

class IpBillingController extends Controller
{
    /** Generate an in-patient bill, auto-computing room + implant charges. */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'admission_id'     => 'required|exists:admissions,id',
            'line_items'       => 'nullable|array',
            'pharmacy_charges' => 'nullable|numeric|min:0',
            'surgery_charges'  => 'nullable|numeric|min:0',
            'misc_charges'     => 'nullable|numeric|min:0',
            'discount'         => 'nullable|numeric|min:0',
            'gst'              => 'nullable|numeric|min:0',
            'paid'             => 'nullable|numeric|min:0',
            'insurance_claim'  => 'nullable|array',
        ]);

        $admission = Admission::with(['bed', 'surgeries.implantUsages'])->findOrFail($data['admission_id']);

        // Room charges = bed daily charge × whole nights stayed (min 1).
        // Carbon 3 diffInDays() returns a fractional float, so compare day-starts.
        $startDay = ($admission->admission_date ?? now())->copy()->startOfDay();
        $endDay   = ($admission->discharge_date ?? now())->copy()->startOfDay();
        $days     = max(1, (int) round($startDay->diffInDays($endDay)));
        $roomCharges = round(($admission->bed->daily_charge ?? 0) * $days, 2);

        // Implant charges = sum of implant usage across this admission's surgeries.
        $implantCharges = round($admission->surgeries
            ->flatMap->implantUsages
            ->sum('total_cost'), 2);

        $lineItems      = $data['line_items'] ?? [];
        $lineItemsTotal = collect($lineItems)->sum(fn($i) => (float) ($i['amount'] ?? 0));

        $pharmacy = (float) ($data['pharmacy_charges'] ?? 0);
        $surgery  = (float) ($data['surgery_charges'] ?? 0);
        $misc     = (float) ($data['misc_charges'] ?? 0);

        $subtotal = round($roomCharges + $pharmacy + $surgery + $implantCharges + $misc + $lineItemsTotal, 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $gst      = round((float) ($data['gst'] ?? 0), 2);
        $total    = round(max(0, $subtotal - $discount + $gst), 2);
        $paid     = round((float) ($data['paid'] ?? 0), 2);
        $balance  = round($total - $paid, 2);

        $bill = IpBill::create([
            'admission_id'     => $admission->id,
            'patient_id'       => $admission->patient_id,
            'bill_date'        => now()->toDateString(),
            'line_items'       => $lineItems,
            'room_charges'     => $roomCharges,
            'pharmacy_charges' => $pharmacy,
            'surgery_charges'  => $surgery,
            'implant_charges'  => $implantCharges,
            'misc_charges'     => $misc,
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'gst'              => $gst,
            'total'            => $total,
            'paid'             => $paid,
            'balance'          => $balance,
            'insurance_claim'  => $data['insurance_claim'] ?? null,
            'status'           => $this->statusFor($total, $paid),
        ]);

        return response()->json($bill, 201);
    }

    /** Bills for an admission (most recent first). */
    public function show(int $admission_id)
    {
        $bills = IpBill::query()
            ->with('patient:id,name,op_number')
            ->where('admission_id', $admission_id)
            ->orderByDesc('id')
            ->get();

        return response()->json($bills);
    }

    /** Lock the bill and record final payment. */
    public function finalize(Request $request, IpBill $ip_bill)
    {
        $data = $request->validate([
            'paid' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('paid', $data) && $data['paid'] !== null) {
            $ip_bill->paid    = $data['paid'];
            $ip_bill->balance = $ip_bill->total - $ip_bill->paid;
        }

        $ip_bill->status = $ip_bill->paid >= $ip_bill->total && $ip_bill->total > 0
            ? 'paid'
            : ($ip_bill->paid > 0 ? 'partially-paid' : 'final');
        $ip_bill->save();

        return response()->json($ip_bill);
    }

    private function statusFor(float $total, float $paid): string
    {
        if ($total > 0 && $paid >= $total) return 'paid';
        if ($paid > 0) return 'partially-paid';
        return 'draft';
    }
}
