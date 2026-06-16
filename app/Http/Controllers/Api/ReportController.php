<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard(Request $request)
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

        return response()->json([
            'today_patients'      => $todayPatients,
            'today_revenue'       => $todayRevenue,
            'pending_invoices'    => $pendingInvoices,
            'total_patients'      => $totalPatients,
            'today_appointments'  => $todayAppointments,
            'low_stock_medicines' => $lowStock,
        ]);
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
}
