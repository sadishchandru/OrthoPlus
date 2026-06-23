<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::query()
            ->with('staff:id,staff_id,name,role')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('staff_id'), fn($q) => $q->where('staff_id', $request->staff_id))
            ->orderByDesc('from_date')
            ->get();

        return response()->json($leaves);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id'  => 'required|exists:staff,id',
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'type'      => 'required|in:sick,casual,annual',
            'reason'    => 'nullable|string',
        ]);

        $data['status'] = 'pending';
        return response()->json(LeaveRequest::create($data)->load('staff:id,staff_id,name'), 201);
    }

    public function show(LeaveRequest $leave_request)
    {
        return response()->json($leave_request->load('staff:id,staff_id,name,role'));
    }

    public function update(Request $request, LeaveRequest $leave_request)
    {
        $data = $request->validate([
            'from_date' => 'sometimes|required|date',
            'to_date'   => 'sometimes|required|date|after_or_equal:from_date',
            'type'      => 'nullable|in:sick,casual,annual',
            'reason'    => 'nullable|string',
        ]);

        $leave_request->update($data);
        return response()->json($leave_request);
    }

    public function destroy(LeaveRequest $leave_request)
    {
        $leave_request->delete();
        return response()->json(['deleted' => true]);
    }

    /** Approve or reject a leave request. */
    public function approve(Request $request, LeaveRequest $leave_request)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leave_request->update([
            'status'      => $data['status'],
            'approved_by' => auth()->id(),
        ]);

        return response()->json($leave_request);
    }
}
