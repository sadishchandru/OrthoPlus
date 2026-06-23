<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImagingController extends Controller
{
    public function index(Request $request)
    {
        $orders = ImagingOrder::query()
            ->with(['patient:id,name,op_number', 'studies'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('modality'), fn($q) => $q->where('modality', $request->modality))
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'modality'            => 'required|in:xray,mri,ct,ultrasound',
            'body_part'           => 'nullable|string|max:255',
            'clinical_indication' => 'nullable|string',
            'status'              => 'nullable|in:ordered,scheduled,completed,reported',
        ]);

        $data['ordered_by'] = auth()->id();
        $data['ordered_at'] = now();
        $data['status']     = $data['status'] ?? 'ordered';

        return response()->json(ImagingOrder::create($data)->load('patient:id,name,op_number'), 201);
    }

    public function show(ImagingOrder $imaging_order)
    {
        return response()->json($imaging_order->load(['patient', 'studies']));
    }

    public function update(Request $request, ImagingOrder $imaging_order)
    {
        $data = $request->validate([
            'modality'            => 'nullable|in:xray,mri,ct,ultrasound',
            'body_part'           => 'nullable|string|max:255',
            'clinical_indication' => 'nullable|string',
            'status'              => 'nullable|in:ordered,scheduled,completed,reported',
        ]);

        $imaging_order->update($data);
        return response()->json($imaging_order);
    }

    public function destroy(ImagingOrder $imaging_order)
    {
        $imaging_order->delete();
        return response()->json(['deleted' => true]);
    }

    /** Attach study images (and optional report) to an order → creates a study. */
    public function uploadImages(Request $request, ImagingOrder $imaging_order)
    {
        $data = $request->validate([
            'study_date'        => 'nullable|date',
            'technician_id'     => 'nullable|integer',
            'images'            => 'required|array|min:1',
            'images.*.url'      => 'required|string',
            'images.*.type'     => 'nullable|string',
            'images.*.view'     => 'nullable|string',
            'report_url'        => 'nullable|string',
            'radiologist_notes' => 'nullable|string',
        ]);

        $study = DB::transaction(function () use ($imaging_order, $data) {
            $study = ImagingStudy::create([
                'imaging_order_id'  => $imaging_order->id,
                'patient_id'        => $imaging_order->patient_id,
                'study_date'        => $data['study_date'] ?? now()->toDateString(),
                'technician_id'     => $data['technician_id'] ?? null,
                'images'            => $data['images'],
                'report_url'        => $data['report_url'] ?? null,
                'radiologist_notes' => $data['radiologist_notes'] ?? null,
                'reported_at'       => !empty($data['radiologist_notes']) ? now() : null,
            ]);

            $imaging_order->update([
                'status' => !empty($data['radiologist_notes']) ? 'reported' : 'completed',
            ]);

            return $study;
        });

        return response()->json($study, 201);
    }

    /** All imaging studies for a patient, newest first. */
    public function patientImages(Patient $patient)
    {
        $studies = ImagingStudy::query()
            ->with('order:id,modality,body_part,status,ordered_at')
            ->where('patient_id', $patient->id)
            ->orderByDesc('study_date')->orderByDesc('id')
            ->get();

        return response()->json($studies);
    }
}
