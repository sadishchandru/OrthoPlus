<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Medicine;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'           => 'required|exists:patients,id',
            'clinical_record_id'   => 'nullable|exists:clinical_records,id',
            'items'                => 'required|array|min:1',
            'items.*.medicine_id'  => 'required|exists:medicines,id',
            'items.*.medicine_name'=> 'nullable|string',
            'items.*.dose'         => 'required|string',
            'items.*.frequency'    => 'required|string',
            'items.*.duration'     => 'nullable|string',
            'items.*.qty'          => 'nullable|numeric|min:0',
            'items.*.unit_price'   => 'nullable|numeric|min:0',
            // free-text service lines (not from catalog)
            'services'             => 'nullable|array',
            'services.*.service_name' => 'required_with:services|string',
            'services.*.qty'       => 'nullable|numeric|min:0',
            'services.*.unit_price'=> 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
        ]);

        $items = collect($data['items'])->map(function ($it) {
            $qty   = $it['qty'] ?? 1;
            $price = $it['unit_price'] ?? 0;
            return array_merge($it, ['qty' => $qty, 'unit_price' => $price, 'amount' => $qty * $price]);
        })->all();

        $services = collect($data['services'] ?? [])->map(function ($s) {
            $qty   = $s['qty'] ?? 1;
            $price = $s['unit_price'] ?? 0;
            return array_merge($s, ['qty' => $qty, 'unit_price' => $price, 'amount' => $qty * $price]);
        })->all();

        $estimatedTotal = collect($items)->sum('amount') + collect($services)->sum('amount');

        $prescription = Prescription::create([
            'patient_id'         => $data['patient_id'],
            'clinical_record_id' => $data['clinical_record_id'] ?? null,
            'items'              => $items,
            'medications'        => $items, // backwards compat
            'services'           => $services,
            'estimated_total'    => $estimatedTotal,
            'notes'              => $data['notes'] ?? null,
            'prescription_date'  => now()->toDateString(),
            'doctor_id'          => Auth::id() ?? 1,
            'type'               => 'initial',
            'status'             => 'active',
            'created_by'         => Auth::id(),
        ]);

        return response()->json($prescription, 201);
    }

    /** Prescriptions for a patient (Rx history). */
    public function index(Request $request)
    {
        $prescriptions = Prescription::query()
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($prescriptions);
    }

    /** Rx-pad print payload (clinic header + patient + items). */
    public function print(Prescription $prescription)
    {
        $prescription->load('patient');
        return response()->json([
            'prescription' => $prescription,
            'clinic'       => config('clinic', [
                'name'    => 'OrthoPlus Clinic',
                'address' => '',
                'phone'   => '',
            ]),
        ]);
    }

    public function medicineSearch(Request $request)
    {
        $q = $request->get('q', '');
        $medicines = Medicine::where('status', 'active')
            ->where(fn($qq) => $qq->where('name', 'like', "%$q%")
                ->orWhere('generic_name', 'like', "%$q%"))
            ->limit(15)
            ->get(['id', 'name', 'generic_name', 'unit', 'strength', 'quantity', 'sell_price']);

        return response()->json($medicines);
    }
}
