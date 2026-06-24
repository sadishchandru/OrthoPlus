<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Admission;
use App\Models\OpdQueue;
use App\Models\StaffShift;
use App\Models\Surgery;
use Illuminate\Support\Facades\Cache;

class HospitalReportController extends Controller
{
    /** KPI cards for the hospital dashboard (cached 60s). */
    public function dashboard()
    {
        return response()->json(Cache::remember('hospital_dashboard', 60, function () {
            $totalBeds = Bed::where('is_active', true)->count();
            $occupied  = Bed::where('status', 'occupied')->count();

            return [
                'total_beds'        => $totalBeds,
                'occupied_beds'     => $occupied,
                'available_beds'    => Bed::where('status', 'available')->count(),
                'admissions_today'  => Admission::whereDate('admission_date', today())->count(),
                'discharges_today'  => Admission::whereDate('discharge_date', today())->count(),
                'surgeries_today'   => Surgery::whereDate('scheduled_date', today())->count(),
                'opd_today'         => OpdQueue::whereDate('date', today())->count(),
                'opd_waiting'       => OpdQueue::whereDate('date', today())->where('status', 'waiting')->count(),
                'staff_on_duty'     => StaffShift::whereDate('date', today())->whereIn('status', ['scheduled', 'attended'])->count(),
                'bed_occupancy_pct' => $totalBeds > 0 ? round($occupied / $totalBeds * 100) : 0,
            ];
        }));
    }

    /** Bed occupancy broken down per ward. */
    public function bedOccupancy()
    {
        $beds = Bed::with('ward:id,name')->where('is_active', true)->get();

        $byWard = $beds->groupBy(fn($b) => $b->ward?->name ?? 'Unassigned')->map(function ($wb) {
            $total = $wb->count();
            $occ   = $wb->where('status', 'occupied')->count();
            return [
                'total'         => $total,
                'occupied'      => $occ,
                'available'     => $wb->where('status', 'available')->count(),
                'occupancy_pct' => $total ? round($occ / $total * 100, 1) : 0,
            ];
        });

        $total = $beds->count();
        $occ   = $beds->where('status', 'occupied')->count();

        return response()->json([
            'total_beds'    => $total,
            'occupied'      => $occ,
            'available'     => $beds->where('status', 'available')->count(),
            'occupancy_pct' => $total ? round($occ / $total * 100, 1) : 0,
            'by_ward'       => $byWard,
        ]);
    }

    /** Current in-patient census. */
    public function census()
    {
        $admissions = Admission::with(['patient:id,name,op_number,gender', 'ward:id,name', 'bed:id,bed_number'])
            ->where('status', 'admitted')
            ->orderBy('ward_id')
            ->get();

        return response()->json([
            'total'      => $admissions->count(),
            'by_ward'    => $admissions->groupBy(fn($a) => $a->ward?->name ?? 'Unassigned')->map->count(),
            'admissions' => $admissions,
        ]);
    }
}
