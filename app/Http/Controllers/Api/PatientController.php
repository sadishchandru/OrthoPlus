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
        // List only the light columns — never the base64 photo / documents / intake JSON.
        $patients = Patient::query()
            ->select(['id', 'op_number', 'name', 'phone', 'dob', 'gender', 'status', 'created_at'])
            ->withCount('clinicalRecords')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', like_operator(), "%$q%")
                      ->orWhere('phone', like_operator(), "%$q%")
                      ->orWhere('op_number', like_operator(), "%$q%");
            }))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

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
            'phone'  => ['required', 'regex:/^\d{10}$/'],
            'country_code' => 'nullable|string|max:6',
            'dob'    => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'photo'  => 'nullable|string',
            'documents' => 'nullable|array',
            'address'   => 'nullable|array',
        ], [
            'phone.regex' => 'Phone must be exactly 10 digits (numbers only).',
        ]);
        $data['country_code'] = $data['country_code'] ?? '+91';

        // Hard duplicate = same name + same phone (same person). Shared family
        // phone with a different name is allowed.
        $dupe = Patient::whereRaw('LOWER(name) = ?', [strtolower(trim($data['name']))])
            ->where('phone', $data['phone'])
            ->first(['id', 'name', 'phone', 'op_number']);

        if ($dupe) {
            return response()->json([
                'message' => "{$dupe->name} ({$dupe->phone}) is already registered as {$dupe->op_number}.",
                'errors'  => ['phone' => ['Patient with this name and phone already exists.']],
            ], 422);
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
        ], 201);
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'name'   => 'sometimes|required|string|max:255',
            'phone'  => ['sometimes', 'required', 'regex:/^\d{10}$/'],
            'country_code' => 'nullable|string|max:6',
            'dob'    => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'photo'  => 'nullable|string',
            'documents' => 'nullable|array',
            'address'   => 'nullable|array',
        ], [
            'phone.regex' => 'Phone must be exactly 10 digits (numbers only).',
        ]);

        // Duplicate guard (exclude self): same name + phone as another patient.
        if (isset($data['name']) || isset($data['phone'])) {
            $name  = strtolower(trim($data['name'] ?? $patient->name));
            $phone = $data['phone'] ?? $patient->phone;
            $dupe = Patient::whereKeyNot($patient->id)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->where('phone', $phone)
                ->first(['id', 'name', 'op_number']);
            if ($dupe) {
                return response()->json([
                    'message' => "Another patient ({$dupe->op_number}) already has this name and phone.",
                    'errors'  => ['phone' => ['Name + phone already used by another patient.']],
                ], 422);
            }
        }

        $patient->update($data); // files in patient_files are untouched → preserved
        return response()->json(['patient' => $patient]);
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
