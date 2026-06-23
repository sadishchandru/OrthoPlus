<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreOpPlan;
use Illuminate\Http\Request;

class PreOpPlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = PreOpPlan::query()
            ->with('patient:id,name,op_number')
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->filled('surgery_id'), fn($q) => $q->where('surgery_id', $request->surgery_id))
            ->orderByDesc('id')
            ->get();

        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'surgery_id'         => 'nullable|exists:surgeries,id',
            'patient_id'         => 'required|exists:patients,id',
            'template_type'      => 'nullable|in:hip,knee,shoulder,spine,fracture',
            'plan_data'          => 'nullable|array',
            'xray_refs'          => 'nullable|array',
            'simulation_notes'   => 'nullable|string',
            'implant_selections' => 'nullable|array',
        ]);

        $data['created_by'] = auth()->id();
        return response()->json(PreOpPlan::create($data), 201);
    }

    public function show(PreOpPlan $pre_op_plan)
    {
        return response()->json($pre_op_plan->load(['patient', 'surgery']));
    }

    public function update(Request $request, PreOpPlan $pre_op_plan)
    {
        $data = $request->validate([
            'template_type'      => 'nullable|in:hip,knee,shoulder,spine,fracture',
            'plan_data'          => 'nullable|array',
            'xray_refs'          => 'nullable|array',
            'simulation_notes'   => 'nullable|string',
            'implant_selections' => 'nullable|array',
        ]);

        $pre_op_plan->update($data);
        return response()->json($pre_op_plan);
    }

    public function destroy(PreOpPlan $pre_op_plan)
    {
        $pre_op_plan->delete();
        return response()->json(['deleted' => true]);
    }
}
