<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function index(Request $request)
    {
        $beds = Bed::query()
            ->with(['ward:id,name,type', 'currentAdmission.patient:id,name,op_number'])
            ->when($request->filled('ward_id'), fn($q) => $q->where('ward_id', $request->ward_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('ward_id')->orderBy('bed_number')
            ->get();

        return response()->json($beds);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ward_id'      => 'required|exists:wards,id',
            'bed_number'   => 'required|string|max:50',
            'bed_type'     => 'nullable|string|max:50',
            'status'       => 'nullable|string|max:50',
            'daily_charge' => 'nullable|numeric|min:0',
            'features'     => 'nullable|array',
            'is_active'    => 'boolean',
        ]);

        return response()->json(Bed::create($data), 201);
    }

    public function show(Bed $bed)
    {
        return response()->json($bed->load('ward:id,name,type'));
    }

    public function update(Request $request, Bed $bed)
    {
        $data = $request->validate([
            'ward_id'      => 'sometimes|required|exists:wards,id',
            'bed_number'   => 'sometimes|required|string|max:50',
            'bed_type'     => 'nullable|string|max:50',
            'status'       => 'nullable|string|max:50',
            'daily_charge' => 'nullable|numeric|min:0',
            'features'     => 'nullable|array',
            'is_active'    => 'boolean',
        ]);

        $bed->update($data);
        return response()->json($bed);
    }

    public function destroy(Bed $bed)
    {
        $bed->delete();
        return response()->json(['deleted' => true]);
    }

    /** Patch only the bed status (available/occupied/maintenance/reserved). */
    public function updateStatus(Request $request, Bed $bed)
    {
        $data = $request->validate([
            'status' => 'required|in:available,occupied,maintenance,reserved',
        ]);
        $bed->update($data);
        return response()->json($bed);
    }

    /** All currently-available, active beds (for admission/transfer pickers). */
    public function available(Request $request)
    {
        $beds = Bed::query()
            ->with('ward:id,name,type')
            ->where('status', 'available')
            ->where('is_active', true)
            ->when($request->filled('ward_id'), fn($q) => $q->where('ward_id', $request->ward_id))
            ->orderBy('ward_id')->orderBy('bed_number')
            ->get();

        return response()->json($beds);
    }
}
