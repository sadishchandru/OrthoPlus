<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use App\Models\Therapist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreatmentController extends Controller
{
    public function assign(Request $request)
    {
        $data = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'clinical_record_id' => 'nullable|exists:clinical_records,id',
            'treatment_id'       => 'required|exists:treatment_catalog,id',
            'therapist_id'       => 'nullable|exists:therapists,id',
            'commission_pct'     => 'nullable|numeric|min:0|max:100',
            'notes'              => 'nullable|string',
        ]);

        // Use therapist's default commission if not provided
        if (empty($data['commission_pct']) && !empty($data['therapist_id'])) {
            $therapist = Therapist::find($data['therapist_id']);
            $data['commission_pct'] = $therapist?->commission_pct ?? 0;
        }

        $treatment = Treatment::create(array_merge($data, [
            'status'     => 'assigned',
            'created_by' => Auth::id(),
        ]));

        return response()->json($treatment->load(['catalog', 'therapist']), 201);
    }

    public function complete(Request $request, Treatment $treatment)
    {
        $treatment->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json($treatment);
    }

    public function index(Request $request)
    {
        $treatments = Treatment::with(['catalog', 'therapist', 'patient:id,name,op_number'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($treatments);
    }
}
