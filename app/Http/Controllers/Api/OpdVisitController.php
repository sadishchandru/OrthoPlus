<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdVisit;
use Illuminate\Http\Request;

class OpdVisitController extends Controller
{
    public function index(Request $request)
    {
        $visits = OpdVisit::query()
            ->with('patient:id,name,op_number')
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->filled('date'), fn($q) => $q->whereDate('visit_date', $request->date))
            ->orderByDesc('visit_date')->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($visits);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'clinical_record_id' => 'nullable|exists:clinical_records,id',
            'visit_date'         => 'required|date',
            'doctor_id'          => 'nullable|integer',
            'chief_complaint'    => 'nullable|string',
            'vitals'             => 'nullable|array',
            'referral_from'      => 'nullable|string',
            'referral_to'        => 'nullable|string',
            'follow_up_date'     => 'nullable|date',
            'visit_type'         => 'nullable|in:new,follow-up,emergency',
            'token_number'       => 'nullable|integer',
        ]);

        return response()->json(OpdVisit::create($data)->load('patient:id,name,op_number'), 201);
    }

    public function show(OpdVisit $opd_visit)
    {
        return response()->json($opd_visit->load(['patient', 'clinicalRecord']));
    }

    public function update(Request $request, OpdVisit $opd_visit)
    {
        $data = $request->validate([
            'visit_date'      => 'sometimes|required|date',
            'doctor_id'       => 'nullable|integer',
            'chief_complaint' => 'nullable|string',
            'vitals'          => 'nullable|array',
            'referral_from'   => 'nullable|string',
            'referral_to'     => 'nullable|string',
            'follow_up_date'  => 'nullable|date',
            'visit_type'      => 'nullable|in:new,follow-up,emergency',
        ]);

        $opd_visit->update($data);
        return response()->json($opd_visit);
    }

    public function destroy(OpdVisit $opd_visit)
    {
        $opd_visit->delete();
        return response()->json(['deleted' => true]);
    }
}
