<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffShift;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $staff = Staff::query()
            ->when($request->filled('role'), fn($query) => $query->where('role', $request->role))
            ->when($request->filled('department'), fn($query) => $query->where('department', $request->department))
            ->when($request->boolean('active_only'), fn($query) => $query->where('is_active', true))
            ->when($q, fn($query) => $query->where(function ($w) use ($q) {
                $w->where('name', like_operator(), "%$q%")
                  ->orWhere('staff_id', like_operator(), "%$q%")
                  ->orWhere('phone', like_operator(), "%$q%");
            }))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return response()->json($staff);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'role'           => 'required|in:doctor,nurse,technician,admin,housekeeping',
            'department'     => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'qualification'  => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'join_date'      => 'nullable|date',
            'salary_type'    => 'nullable|in:fixed,hourly',
            'salary'         => 'nullable|numeric|min:0',
            'shift_default'  => 'nullable|string|max:50',
            'is_active'      => 'boolean',
            'photo'          => 'nullable|string',
            'documents'      => 'nullable|array',
        ]);

        $data['staff_id'] = $this->generateStaffId();
        return response()->json(Staff::create($data), 201);
    }

    public function show(Staff $staff)
    {
        return response()->json($staff->load([
            'shifts' => fn($q) => $q->latest('date')->limit(30),
            'leaveRequests' => fn($q) => $q->latest('from_date')->limit(20),
        ]));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'role'           => 'sometimes|required|in:doctor,nurse,technician,admin,housekeeping',
            'department'     => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'qualification'  => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'join_date'      => 'nullable|date',
            'salary_type'    => 'nullable|in:fixed,hourly',
            'salary'         => 'nullable|numeric|min:0',
            'shift_default'  => 'nullable|string|max:50',
            'is_active'      => 'boolean',
            'photo'          => 'nullable|string',
            'documents'      => 'nullable|array',
        ]);

        $staff->update($data);
        return response()->json($staff);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return response()->json(['deleted' => true]);
    }

    /** Schedule a shift for a staff member. */
    public function assignShift(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'date'       => 'required|date',
            'shift'      => 'required|in:morning,afternoon,night,custom',
            'start_time' => 'nullable',
            'end_time'   => 'nullable',
            'ward_id'    => 'nullable|exists:wards,id',
            'status'     => 'nullable|in:scheduled,attended,absent,leave',
        ]);

        $data['staff_id'] = $staff->id;
        $data['status'] = $data['status'] ?? 'scheduled';

        $shift = StaffShift::create($data);
        return response()->json($shift, 201);
    }

    /** Staff on duty for a date (defaults to today). */
    public function onDuty(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $shifts = StaffShift::query()
            ->with(['staff:id,staff_id,name,role,department', 'ward:id,name'])
            ->whereDate('date', $date)
            ->whereIn('status', ['scheduled', 'attended'])
            ->orderBy('shift')
            ->get();

        return response()->json([
            'date'   => $date,
            'shifts' => $shifts,
            'by_shift' => $shifts->groupBy('shift'),
        ]);
    }

    private function generateStaffId(): string
    {
        $max = Staff::where('staff_id', 'like', 'STF-%')
            ->pluck('staff_id')
            ->map(fn($s) => (int) (explode('-', $s)[1] ?? 0))
            ->max();

        return sprintf('STF-%03d', (int) $max + 1);
    }
}
