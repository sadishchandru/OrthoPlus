<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicalRecordController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'soap_notes'       => 'nullable|array',
            'body_map'         => 'nullable|array',
            'vas_score'        => 'nullable|numeric|min:0|max:10',
            'rom'              => 'nullable|array',
            'ortho_tests'      => 'nullable|array',
            'outcome_measures' => 'nullable|array',
            'gait_video'       => 'nullable|string',
        ]);

        $visitNo = ClinicalRecord::where('patient_id', $data['patient_id'])->count() + 1;

        $record = ClinicalRecord::create(array_merge($data, [
            'visit_no'   => $visitNo,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));

        return response()->json($record, 201);
    }

    public function update(Request $request, ClinicalRecord $clinicalRecord)
    {
        $data = $request->validate([
            'soap_notes'       => 'nullable|array',
            'body_map'         => 'nullable|array',
            'vas_score'        => 'nullable|numeric|min:0|max:10',
            'rom'              => 'nullable|array',
            'ortho_tests'      => 'nullable|array',
            'outcome_measures' => 'nullable|array',
            'gait_video'       => 'nullable|string',
        ]);

        $clinicalRecord->update(array_merge($data, ['updated_by' => Auth::id()]));

        return response()->json($clinicalRecord);
    }

    public function show(ClinicalRecord $clinicalRecord)
    {
        return response()->json($clinicalRecord->load(['treatments.catalog', 'exercisePrescriptions', 'prescriptions']));
    }
}
