<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\Treatment;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Surgery;
use App\Models\IpBill;
use App\Models\ImplantUsage;
use App\Models\StaffShift;
use App\Models\DischargeSummary;
use App\Models\GlobalPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        // Cache 2 min — dashboard is read-heavy and tolerates slight staleness.
        return response()->json(
            Cache::remember('dashboard_stats_' . now()->toDateString(), 120, fn() => $this->buildDashboard())
        );
    }

    private function buildDashboard(): array
    {
        $today = now()->toDateString();

        $todayPatients = Appointment::where('scheduled_date', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $todayRevenue = Invoice::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('total');

        $pendingInvoices = Invoice::where('status', 'pending')->count();

        $totalPatients = Patient::count();

        $todayAppointments = Appointment::with(['patient:id,name,op_number,phone'])
            ->where('scheduled_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_time')
            ->get();

        $lowStock = DB::table('medicines_stock')
            ->join('medicines', 'medicines.id', '=', 'medicines_stock.medicine_id')
            ->where('medicines_stock.quantity_in_stock', '<=', DB::raw('COALESCE(medicines_stock.reorder_quantity, 10)'))
            ->select('medicines.id', 'medicines.name', 'medicines_stock.quantity_in_stock')
            ->limit(10)
            ->get();

        return [
            'today_patients'      => $todayPatients,
            'today_revenue'       => $todayRevenue,
            'pending_invoices'    => $pendingInvoices,
            'total_patients'      => $totalPatients,
            'today_appointments'  => $todayAppointments,
            'low_stock_medicines' => $lowStock,
        ];
    }

    public function therapist(Request $request)
    {
        $request->validate([
            'therapist_id' => 'required|exists:therapists,id',
            'from'         => 'required|date',
            'to'           => 'required|date',
        ]);

        $treatments = Treatment::with(['catalog', 'patient:id,name,op_number'])
            ->where('therapist_id', $request->therapist_id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$request->from, $request->to . ' 23:59:59'])
            ->get();

        $totalCommission = $treatments->sum(fn($t) =>
            ($t->catalog?->price ?? 0) * ($t->commission_pct / 100)
        );

        return response()->json([
            'treatments'       => $treatments,
            'count'            => $treatments->count(),
            'total_commission' => round($totalCommission, 2),
        ]);
    }

    // ===================================================================
    //  Hospital (in-patient) reports
    // ===================================================================

    /** Current in-patient census — who is admitted right now. */
    public function ipCensus(Request $request)
    {
        $admissions = Admission::with([
                'patient:id,name,op_number,gender,dob',
                'ward:id,name,type',
                'bed:id,bed_number',
            ])
            ->where('status', 'admitted')
            ->orderBy('ward_id')
            ->get();

        return response()->json([
            'as_of'      => now()->toDateTimeString(),
            'total'      => $admissions->count(),
            'by_ward'    => $admissions->groupBy(fn($a) => $a->ward?->name ?? 'Unassigned')
                                        ->map->count(),
            'admissions' => $admissions,
        ]);
    }

    /** Bed occupancy overview, per ward and overall. */
    public function bedOccupancy(Request $request)
    {
        $beds = Bed::with('ward:id,name')->where('is_active', true)->get();

        $byWard = $beds->groupBy(fn($b) => $b->ward?->name ?? 'Unassigned')->map(function ($wardBeds) {
            $total    = $wardBeds->count();
            $occupied = $wardBeds->where('status', 'occupied')->count();
            return [
                'total'        => $total,
                'occupied'     => $occupied,
                'available'    => $wardBeds->where('status', 'available')->count(),
                'maintenance'  => $wardBeds->where('status', 'maintenance')->count(),
                'reserved'     => $wardBeds->where('status', 'reserved')->count(),
                'occupancy_pct'=> $total ? round($occupied / $total * 100, 1) : 0,
            ];
        });

        $total    = $beds->count();
        $occupied = $beds->where('status', 'occupied')->count();

        return response()->json([
            'total_beds'    => $total,
            'occupied'      => $occupied,
            'available'     => $beds->where('status', 'available')->count(),
            'occupancy_pct' => $total ? round($occupied / $total * 100, 1) : 0,
            'by_ward'       => $byWard,
        ]);
    }

    /** Surgeries in a date range (defaults to the last 30 days). */
    public function surgeryList(Request $request)
    {
        [$from, $to] = $this->range($request, 30);

        $surgeries = Surgery::with(['patient:id,name,op_number'])
            ->whereBetween('scheduled_date', [$from, $to])
            ->orderBy('scheduled_date')
            ->get();

        return response()->json([
            'from'      => $from,
            'to'        => $to,
            'count'     => $surgeries->count(),
            'by_status' => $surgeries->groupBy('status')->map->count(),
            'by_type'   => $surgeries->groupBy('surgery_type')->map->count(),
            'surgeries' => $surgeries,
        ]);
    }

    /** In-patient revenue from ip_bills over a date range. */
    public function revenueIp(Request $request)
    {
        [$from, $to] = $this->range($request, 30);

        $bills = IpBill::whereBetween('bill_date', [$from, $to])->get();

        return response()->json([
            'from'          => $from,
            'to'            => $to,
            'bill_count'    => $bills->count(),
            'total_billed'  => round($bills->sum('total'), 2),
            'total_paid'    => round($bills->sum('paid'), 2),
            'total_balance' => round($bills->sum('balance'), 2),
            'by_status'     => $bills->groupBy('status')->map->count(),
            'breakdown'     => [
                'room'     => round($bills->sum('room_charges'), 2),
                'pharmacy' => round($bills->sum('pharmacy_charges'), 2),
                'surgery'  => round($bills->sum('surgery_charges'), 2),
                'implant'  => round($bills->sum('implant_charges'), 2),
                'misc'     => round($bills->sum('misc_charges'), 2),
            ],
        ]);
    }

    /** Implant consumption over a date range. */
    public function implantUsage(Request $request)
    {
        [$from, $to] = $this->range($request, 30);

        $usage = ImplantUsage::with('implant:id,name,ref_number,manufacturer')
            ->whereBetween('used_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->get();

        $byImplant = $usage->groupBy('implant_id')->map(fn($rows) => [
            'implant'      => $rows->first()->implant?->name,
            'ref_number'   => $rows->first()->implant?->ref_number,
            'qty_used'     => $rows->sum('quantity_used'),
            'total_cost'   => round($rows->sum('total_cost'), 2),
        ])->values();

        return response()->json([
            'from'        => $from,
            'to'          => $to,
            'total_qty'   => $usage->sum('quantity_used'),
            'total_cost'  => round($usage->sum('total_cost'), 2),
            'by_implant'  => $byImplant,
        ]);
    }

    /** Staff attendance from shifts over a date range. */
    public function staffAttendance(Request $request)
    {
        [$from, $to] = $this->range($request, 7);

        $shifts = StaffShift::with('staff:id,staff_id,name,role')
            ->whereBetween('date', [$from, $to])
            ->get();

        $byStaff = $shifts->groupBy('staff_id')->map(fn($rows) => [
            'staff'     => $rows->first()->staff?->name,
            'staff_id'  => $rows->first()->staff?->staff_id,
            'scheduled' => $rows->count(),
            'attended'  => $rows->where('status', 'attended')->count(),
            'absent'    => $rows->where('status', 'absent')->count(),
            'leave'     => $rows->where('status', 'leave')->count(),
        ])->values();

        return response()->json([
            'from'     => $from,
            'to'       => $to,
            'by_staff' => $byStaff,
        ]);
    }

    /** Discharges over a date range. */
    public function dischargeSummary(Request $request)
    {
        [$from, $to] = $this->range($request, 30);

        $discharges = DischargeSummary::with([
                'patient:id,name,op_number',
                'admission:id,ip_number,admission_date,discharge_date',
            ])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'from'         => $from,
            'to'           => $to,
            'count'        => $discharges->count(),
            'by_condition' => $discharges->groupBy('discharge_condition')->map->count(),
            'discharges'   => $discharges,
        ]);
    }

    /** Active global (90-day) billing periods across patients. */
    public function globalPeriods(Request $request)
    {
        $periods = GlobalPeriod::with(['patient:id,name,op_number', 'surgery:id,surgery_name'])
            ->when($request->boolean('active_only', true), fn($q) =>
                $q->whereDate('global_end_90days', '>=', now()->toDateString()))
            ->orderBy('global_end_90days')
            ->get();

        return response()->json([
            'count'   => $periods->count(),
            'periods' => $periods,
        ]);
    }

    /** Parse from/to query params, defaulting to the last $days days. */
    private function range(Request $request, int $days): array
    {
        $from = $request->get('from', now()->subDays($days)->toDateString());
        $to   = $request->get('to', now()->toDateString());
        return [$from, $to];
    }
}
