<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surgery;
use Illuminate\Http\Request;

class SurgeryController extends Controller
{
    public function index(Request $request)
    {
        $surgeries = Surgery::query()
            ->with(['patient:id,name,op_number', 'admission:id,ip_number'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->filled('date'), fn($q) => $q->whereDate('scheduled_date', $request->date))
            ->orderByDesc('scheduled_date')->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($surgeries);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'admission_id'        => 'nullable|exists:admissions,id',
            'surgery_name'        => 'required|string|max:255',
            'surgery_type'        => 'nullable|in:elective,emergency,revision',
            'scheduled_date'      => 'nullable|date',
            'scheduled_time'      => 'nullable',
            'duration_mins'       => 'nullable|integer|min:0',
            'surgeon_id'          => 'nullable|integer',
            'anesthetist_id'      => 'nullable|integer',
            'scrub_nurse_id'      => 'nullable|integer',
            'or_room'             => 'nullable|string|max:50',
            'implants_required'   => 'nullable|array',
            'pre_op_instructions' => 'nullable|string',
            'status'              => 'nullable|in:scheduled,in-progress,completed,cancelled',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'scheduled';
        return response()->json(Surgery::create($data)->load('patient:id,name,op_number'), 201);
    }

    public function show(Surgery $surgery)
    {
        return response()->json($surgery->load([
            'patient', 'admission', 'preOpPlan',
            'implantUsages.implant:id,name,ref_number',
        ]));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $data = $request->validate([
            'surgery_name'        => 'sometimes|required|string|max:255',
            'surgery_type'        => 'nullable|in:elective,emergency,revision',
            'scheduled_date'      => 'nullable|date',
            'scheduled_time'      => 'nullable',
            'duration_mins'       => 'nullable|integer|min:0',
            'surgeon_id'          => 'nullable|integer',
            'anesthetist_id'      => 'nullable|integer',
            'scrub_nurse_id'      => 'nullable|integer',
            'or_room'             => 'nullable|string|max:50',
            'implants_required'   => 'nullable|array',
            'pre_op_instructions' => 'nullable|string',
            'status'              => 'nullable|in:scheduled,in-progress,completed,cancelled',
            'post_op_notes'       => 'nullable|string',
            'complications'       => 'nullable|string',
        ]);

        $surgery->update($data);
        return response()->json($surgery);
    }

    public function destroy(Surgery $surgery)
    {
        $surgery->delete();
        return response()->json(['deleted' => true]);
    }

    /** OR schedule for a date (defaults to today), grouped by OR room. */
    public function schedule(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $surgeries = Surgery::query()
            ->with('patient:id,name,op_number')
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', ['scheduled', 'in-progress'])
            ->orderBy('or_room')->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'date'      => $date,
            'surgeries' => $surgeries,
            'by_room'   => $surgeries->groupBy('or_room'),
        ]);
    }
}
