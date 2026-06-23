<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedTransfer;
use App\Models\DischargeSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $admissions = Admission::query()
            ->with(['patient:id,name,op_number,phone,gender', 'ward:id,name,type', 'bed:id,bed_number'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('ward_id'), fn($q) => $q->where('ward_id', $request->ward_id))
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('current'), fn($q) => $q->where('status', 'admitted'))
            ->orderByDesc('admission_date')->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($admissions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'admission_date'      => 'required|date',
            'ward_id'             => 'nullable|exists:wards,id',
            'bed_id'              => 'nullable|exists:beds,id',
            'admitting_doctor_id' => 'nullable|integer',
            'diagnosis'           => 'nullable|string',
            'admission_type'      => 'nullable|in:elective,emergency',
            'surgery_planned'     => 'boolean',
            'surgery_date'        => 'nullable|date',
            'deposit_amount'      => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $admission = DB::transaction(function () use ($data) {
            $data['ip_number']  = $this->generateIpNumber();
            $data['status']     = 'admitted';
            $data['created_by'] = auth()->id();

            $admission = Admission::create($data);

            // Occupy the assigned bed.
            if (!empty($data['bed_id'])) {
                Bed::where('id', $data['bed_id'])->update(['status' => 'occupied']);
            }

            return $admission;
        });

        return response()->json($admission->load(['patient', 'ward', 'bed']), 201);
    }

    public function show(Admission $admission)
    {
        $admission->load([
            'patient', 'ward', 'bed',
            'transfers' => fn($q) => $q->latest('transferred_at'),
            'surgeries', 'dischargeSummary', 'bills',
        ]);

        return response()->json($admission);
    }

    public function update(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'admission_date'      => 'sometimes|required|date',
            'ward_id'             => 'nullable|exists:wards,id',
            'admitting_doctor_id' => 'nullable|integer',
            'diagnosis'           => 'nullable|string',
            'admission_type'      => 'nullable|in:elective,emergency',
            'surgery_planned'     => 'boolean',
            'surgery_date'        => 'nullable|date',
            'deposit_amount'      => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
            'status'              => 'nullable|in:admitted,discharged,transferred',
        ]);

        $admission->update($data);
        return response()->json($admission);
    }

    public function destroy(Admission $admission)
    {
        // Free the bed if still occupied by this admission.
        if ($admission->bed_id) {
            Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
        }
        $admission->delete();
        return response()->json(['deleted' => true]);
    }

    /** Move a patient to a different bed; logs the transfer + flips bed statuses. */
    public function transferBed(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'to_bed_id' => 'required|exists:beds,id',
            'reason'    => 'nullable|string',
        ]);

        $transfer = DB::transaction(function () use ($admission, $data) {
            $fromBedId = $admission->bed_id;

            $transfer = BedTransfer::create([
                'admission_id'   => $admission->id,
                'from_bed_id'    => $fromBedId,
                'to_bed_id'      => $data['to_bed_id'],
                'transferred_at' => now(),
                'reason'         => $data['reason'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            if ($fromBedId) {
                Bed::where('id', $fromBedId)->update(['status' => 'available']);
            }
            Bed::where('id', $data['to_bed_id'])->update(['status' => 'occupied']);

            $newWardId = Bed::find($data['to_bed_id'])?->ward_id;
            $admission->update([
                'bed_id'  => $data['to_bed_id'],
                'ward_id' => $newWardId ?? $admission->ward_id,
            ]);

            return $transfer;
        });

        return response()->json([
            'transfer'  => $transfer,
            'admission' => $admission->fresh(['ward', 'bed']),
        ], 201);
    }

    /** Discharge: writes the summary, frees the bed, closes the admission. */
    public function discharge(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'discharge_date'         => 'nullable|date',
            'diagnosis_final'        => 'nullable|string',
            'procedures_done'        => 'nullable|array',
            'discharge_condition'    => 'nullable|in:stable,critical,absconded,lama,death',
            'follow_up_date'         => 'nullable|date',
            'discharge_instructions' => 'nullable|string',
            'discharge_medications'  => 'nullable|array',
        ]);

        $summary = DB::transaction(function () use ($admission, $data) {
            $dischargeDate = $data['discharge_date'] ?? now()->toDateString();

            $summary = DischargeSummary::create([
                'admission_id'           => $admission->id,
                'patient_id'             => $admission->patient_id,
                'diagnosis_final'        => $data['diagnosis_final'] ?? $admission->diagnosis,
                'procedures_done'        => $data['procedures_done'] ?? [],
                'discharge_condition'    => $data['discharge_condition'] ?? 'stable',
                'follow_up_date'         => $data['follow_up_date'] ?? null,
                'discharge_instructions' => $data['discharge_instructions'] ?? null,
                'discharge_medications'  => $data['discharge_medications'] ?? [],
                'created_by'             => auth()->id(),
            ]);

            if ($admission->bed_id) {
                Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
            }

            $admission->update([
                'status'         => 'discharged',
                'discharge_date' => $dischargeDate,
            ]);

            return $summary;
        });

        return response()->json([
            'discharge_summary' => $summary,
            'admission'         => $admission->fresh(),
        ], 201);
    }

    private function generateIpNumber(): string
    {
        $year = date('Y');
        $max = Admission::where('ip_number', 'like', "IP-$year-%")
            ->pluck('ip_number')
            ->map(fn($n) => (int) (explode('-', $n)[2] ?? 0))
            ->max();

        return sprintf('IP-%s-%03d', $year, (int) $max + 1);
    }
}
