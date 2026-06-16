<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Medicine;
use App\Models\Invoice;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PharmacyController extends Controller
{
    /** Search patient by OP number / name / phone. */
    public function searchPatient(Request $request)
    {
        $q = $request->get('q', '');
        $opCol = Schema::hasColumn('patients', 'op_number') ? 'op_number' : 'op_number_prefix';

        $patients = Patient::where('name', 'like', "%$q%")
            ->orWhere('phone', 'like', "%$q%")
            ->orWhere($opCol, 'like', "%$q%")
            ->limit(10)
            ->get(array_filter(['id', 'name', 'phone', $opCol]))
            ->map(fn($p) => array_merge($p->toArray(), ['op_number' => $p->$opCol]));

        return response()->json($patients);
    }

    /** Active prescriptions for a patient, items resolved to medicine rows for billing. */
    public function prescriptions(Patient $patient)
    {
        $prescriptions = Prescription::where('patient_id', $patient->id)
            ->where(fn($q) => $q->where('status', 'active')->orWhereNull('status'))
            ->orderByDesc('created_at')
            ->get();

        $medIds = collect($prescriptions)->flatMap(fn($p) =>
            collect($p->items ?? [])->pluck('medicine_id')
        )->filter()->unique();
        $meds = Medicine::whereIn('id', $medIds)->get()->keyBy('id');

        $result = $prescriptions->map(function ($p) use ($meds) {
            $items = collect($p->items ?? [])->map(function ($it) use ($meds) {
                $med = $meds->get($it['medicine_id'] ?? null);
                return [
                    'medicine_id'   => $it['medicine_id'] ?? null,
                    'medicine_name' => $med?->name ?? ($it['medicine_name'] ?? ''),
                    'unit'          => $med?->unit,
                    'qty'           => 1,
                    'unit_price'    => $med?->sell_price ?? 0,
                    'available'     => $med?->quantity ?? 0,
                    'dose'          => $it['dose'] ?? null,
                    'frequency'     => $it['freq'] ?? ($it['frequency'] ?? null),
                ];
            })->values();
            return [
                'id'         => $p->id,
                'date'       => $p->prescription_date,
                'notes'      => $p->notes,
                'items'      => $items,
            ];
        });

        return response()->json($result);
    }

    /**
     * Create a pharmacy invoice (series PH), deduct stock, log inventory.
     * items: [{medicine_id?, medicine_name, qty, unit_price}]  (medicine_id null = ad-hoc/walk-in line)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'         => 'nullable|exists:patients,id',
            'items'              => 'required|array|min:1',
            'items.*.medicine_id'=> 'nullable|exists:medicines,id',
            'items.*.medicine_name' => 'required|string',
            'items.*.qty'        => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount'           => 'nullable|numeric|min:0',
            'tax'                => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $items = collect($data['items'])->map(fn($it) => [
                'medicine_id' => $it['medicine_id'] ?? null,
                'description' => $it['medicine_name'],
                'qty'         => $it['qty'],
                'rate'        => $it['unit_price'],
                'amount'      => $it['qty'] * $it['unit_price'],
            ])->all();

            $subtotal = collect($items)->sum('amount');
            $discount = $data['discount'] ?? 0;
            $tax      = $data['tax'] ?? 0;
            $total    = $subtotal - $discount + $tax;

            $invoice = Invoice::create([
                'patient_id'     => $data['patient_id'] ?? null,
                'series'         => 'PH',
                'type'           => 'pharmacy',
                'invoice_no'     => Invoice::generateInvoiceNo('PH'),
                'items'          => $items,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total'          => $total,
                'status'         => 'paid',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'created_by'     => Auth::id(),
            ]);

            // Deduct stock + inventory log for each medicine line
            foreach ($items as $line) {
                if (empty($line['medicine_id'])) continue;
                $med = Medicine::lockForUpdate()->find($line['medicine_id']);
                if (!$med) continue;
                $med->decrement('quantity', (int) $line['qty']);
                InventoryLog::create([
                    'medicine_id' => $med->id,
                    'type'        => 'out',
                    'qty'         => (int) $line['qty'],
                    'reason'      => 'Pharmacy sale ' . $invoice->invoice_no,
                    'ref_id'      => $invoice->id,
                    'created_by'  => Auth::id(),
                ]);
            }

            return response()->json($invoice->load('patient'), 201);
        });
    }
}
