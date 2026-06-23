<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalPeriod;
use App\Models\Patient;
use Illuminate\Http\Request;

class GlobalPeriodController extends Controller
{
    /** All global (90-day) billing periods for a patient. */
    public function index(Patient $patient)
    {
        $periods = GlobalPeriod::query()
            ->with('surgery:id,surgery_name,scheduled_date')
            ->where('patient_id', $patient->id)
            ->orderByDesc('global_start')
            ->get()
            ->map(function ($p) {
                $p->is_active = $p->global_end_90days
                    ? now()->lte($p->global_end_90days)
                    : false;
                $p->days_remaining = $p->global_end_90days
                    ? max(0, now()->startOfDay()->diffInDays($p->global_end_90days, false))
                    : null;
                return $p;
            });

        return response()->json($periods);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'surgery_id'   => 'nullable|exists:surgeries,id',
            'cpt_code'     => 'nullable|string|max:20',
            'global_start' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        // 90-day global period per CPT global surgical package.
        $data['global_end_90days'] = \Carbon\Carbon::parse($data['global_start'])->addDays(90)->toDateString();
        $data['visits_in_period']  = [];

        return response()->json(GlobalPeriod::create($data), 201);
    }
}
