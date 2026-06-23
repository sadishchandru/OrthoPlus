<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\ClinicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q') ?? $request->get('search');
        $patients = Patient::query()
            ->withCount('clinicalRecords')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', like_operator(), "%$q%")
                      ->orWhere('phone', like_operator(), "%$q%")
                      ->orWhere('op_number', like_operator(), "%$q%");
            }))
            ->orderByDesc('created_at')
            ->paginate(15);

        // new vs revisit from prior clinical records.
        $patients->getCollection()->transform(function ($p) {
            $p->visit_count = $p->clinical_records_count;
            $p->visit_type  = $p->clinical_records_count > 0 ? 'revisit' : 'new';
            return $p;
        });

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|max:20',
            'dob'    => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'photo'  => 'nullable|string',
            'documents' => 'nullable|array',
            'address'   => 'nullable|array',
        ]);

        // Duplicate check
        $opNumberCol = Schema::hasColumn('patients', 'op_number') ? 'op_number' : 'op_number_prefix';
        $dupe = Patient::where('phone', $data['phone'])
            ->orWhere(fn($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($data['name'])]))
            ->get(['id', 'name', 'phone', $opNumberCol]);

        $warning = null;
        if ($dupe->isNotEmpty()) {
            $warning = [
                'message'   => 'Possible duplicate patient found.',
                'patients'  => $dupe,
            ];
        }

        // Generate OP Number
        $opNo = $this->generateOpNo();

        $patient = Patient::create(array_merge($data, [
            'op_number'          => $opNo,
            'op_number_prefix'   => 'N',
            'op_number_counter'  => 1,
            'address'            => $data['address'] ?? [],
            'duplicate_detection_fields' => json_encode(['name', 'phone']),
        ]));

        return response()->json([
            'patient' => $patient,
            'warning' => $warning,
        ], 201);
    }

    public function show(Patient $patient)
    {
        $patient->load('appointments');
        $patient->setRelation('visits', ClinicalRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get());

        $patient->visit_count = $patient->visits->count();
        $patient->visit_type  = $patient->visit_count > 0 ? 'revisit' : 'new';

        return response()->json($patient);
    }

    public function visits(Patient $patient)
    {
        $records = ClinicalRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($records);
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $hasOpNumber = Schema::hasColumn('patients', 'op_number');
        $opCol = $hasOpNumber ? 'op_number' : 'op_number_prefix';

        $patients = Patient::where('name', like_operator(), "%$q%")
            ->orWhere('phone', like_operator(), "%$q%")
            ->orWhere($opCol, like_operator(), "%$q%")
            ->withCount('clinicalRecords')
            ->limit(10)
            ->get(array_filter(['id', 'name', 'phone', $opCol, 'gender', 'dob']))
            ->map(fn($p) => array_merge($p->toArray(), [
                'op_number'   => $p->$opCol,
                'visit_count' => $p->clinical_records_count,
                'visit_type'  => $p->clinical_records_count > 0 ? 'revisit' : 'new',
            ]));

        return response()->json($patients);
    }

    private function generateOpNo(): string
    {
        // Parse the numeric suffix in PHP (no SUBSTRING_INDEX) — driver-agnostic.
        $max = Patient::where('op_number', like_operator(), 'N-%')
            ->where('op_number', 'not like', '%-%-%') // exclude revisit OPNos (N-79-1)
            ->pluck('op_number')
            ->map(fn($op) => (int) (explode('-', $op)[1] ?? 0))
            ->max();

        return 'N-' . ((int) $max + 1);
    }

    public function generateRevisitOpNo(Patient $patient): string
    {
        // Count existing revisits
        $visitCount = ClinicalRecord::where('patient_id', $patient->id)->count();
        return $patient->op_number . '-' . ($visitCount + 1);
    }
}
