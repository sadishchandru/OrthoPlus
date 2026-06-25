<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Admission;
use App\Models\OpdQueue;
use App\Models\StaffShift;
use App\Models\Surgery;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\IpBill;
use Illuminate\Support\Facades\Cache;

class HospitalReportController extends Controller
{
    /** KPI cards for the hospital dashboard (cached 60s). */
    public function dashboard()
    {
        return response()->json(Cache::remember('hospital_dashboard', 60, function () {
            $today     = today();
            $totalBeds = Bed::where('is_active', true)->count();
            $occupied  = Bed::where('status', 'occupied')->count();

            // Revisit = patients in today's queue who registered before today.
            $queuePatientIds  = OpdQueue::whereDate('date', $today)->pluck('patient_id')->unique();
            $revisitToday     = Patient::whereIn('id', $queuePatientIds)->whereDate('created_at', '<', $today)->count();
            $newPatientsToday = Patient::whereDate('created_at', $today)->count();

            // Registration trends — daily(7d) / weekly(8w) / monthly(12mo) for the chart toggle.
            $trend = collect(range(6, 0))->map(function ($d) use ($today) {
                $date = $today->copy()->subDays($d);
                return ['date' => $date->format('d M'), 'count' => Patient::whereDate('created_at', $date)->count()];
            })->values();
            $weekly = collect(range(7, 0))->map(function ($w) use ($today) {
                $start = $today->copy()->subWeeks($w)->startOfWeek();
                $end   = $start->copy()->endOfWeek();
                return ['date' => $start->format('d M'), 'count' => Patient::whereBetween('created_at', [$start, $end])->count()];
            })->values();
            $monthly = collect(range(11, 0))->map(function ($mo) use ($today) {
                $d = $today->copy()->subMonths($mo);
                return ['date' => $d->format('M'), 'count' => Patient::whereYear('created_at', $d->year)->whereMonth('created_at', $d->month)->count()];
            })->values();

            $revenueToday = (float) Invoice::whereDate('created_at', $today)->where('status', 'paid')->sum('total')
                + (float) IpBill::whereDate('bill_date', $today)->sum('paid');
            $pendingBills = Invoice::where('status', 'pending')->count() + IpBill::where('balance', '>', 0)->count();

            return [
                // --- legacy keys (kept for backward compatibility) ---
                'total_beds'        => $totalBeds,
                'occupied_beds'     => $occupied,
                'available_beds'    => Bed::where('status', 'available')->count(),
                'discharges_today'  => Admission::whereDate('discharge_date', $today)->count(),
                'surgeries_today'   => Surgery::whereDate('scheduled_date', $today)->count(),
                'opd_waiting'       => OpdQueue::whereDate('date', $today)->where('status', 'waiting')->count(),
                'staff_on_duty'     => StaffShift::whereDate('date', $today)->whereIn('status', ['scheduled', 'attended'])->count(),
                'bed_occupancy_pct' => $totalBeds > 0 ? round($occupied / $totalBeds * 100) : 0,

                // --- redesigned dashboard KPIs ---
                'new_patients_today' => $newPatientsToday,
                'revisit_today'      => $revisitToday,
                'total_patients'     => Patient::count(),
                'opd_today'          => OpdQueue::whereDate('date', $today)->count(),
                'admissions_today'   => Admission::whereDate('admission_date', $today)->count(),
                'admitted_now'       => Admission::where('status', 'admitted')->count(),
                'appointments_today' => Appointment::where('scheduled_date', $today->toDateString())->count(),
                'revenue_today'      => round($revenueToday, 2),
                'pending_bills'      => $pendingBills,

                // --- chart + widget data (one payload, no extra calls) ---
                'registration_trend'         => $trend,
                'registration_trend_weekly'  => $weekly,
                'registration_trend_monthly' => $monthly,
                'recent_patients'    => Patient::latest('id')->take(5)->get(['id', 'name', 'op_number', 'gender', 'created_at']),
                'today_queue'        => OpdQueue::whereDate('date', $today)
                    ->with('patient:id,name,op_number')->orderByDesc('id')->take(8)
                    ->get(['id', 'patient_id', 'token_number', 'status', 'priority']),
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
