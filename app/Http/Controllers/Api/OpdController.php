<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdQueue;
use Illuminate\Http\Request;

class OpdController extends Controller
{
    /** Today's queue, ordered by priority then token. */
    public function todayQueue(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $queue = OpdQueue::query()
            ->with('patient:id,name,op_number,phone,gender,dob')
            ->whereDate('date', $date)
            ->when($request->filled('doctor_id'), fn($q) => $q->where('doctor_id', $request->doctor_id))
            ->orderByRaw($this->priorityOrderSql())
            ->orderBy('token_number')
            ->get();

        return response()->json([
            'date'  => $date,
            'queue' => $queue,
            'stats' => [
                'waiting'     => $queue->where('status', 'waiting')->count(),
                'in_progress' => $queue->where('status', 'in-progress')->count(),
                'completed'   => $queue->where('status', 'completed')->count(),
                'total'       => $queue->count(),
            ],
        ]);
    }

    public function addToQueue(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id'  => 'nullable|integer',
            'priority'   => 'nullable|in:normal,urgent,emergency',
            'date'       => 'nullable|date',
        ]);

        $date = $data['date'] ?? now()->toDateString();

        $nextToken = (OpdQueue::whereDate('date', $date)->max('token_number') ?? 0) + 1;

        $entry = OpdQueue::create([
            'patient_id'   => $data['patient_id'],
            'token_number' => $nextToken,
            'date'         => $date,
            'doctor_id'    => $data['doctor_id'] ?? null,
            'priority'     => $data['priority'] ?? 'normal',
            'status'       => 'waiting',
            'arrival_time' => now(),
        ]);

        return response()->json($entry->load('patient:id,name,op_number'), 201);
    }

    public function updateStatus(Request $request, OpdQueue $opd_queue)
    {
        $data = $request->validate([
            'status' => 'required|in:waiting,in-progress,completed,cancelled',
        ]);

        $opd_queue->status = $data['status'];
        if ($data['status'] === 'in-progress' && !$opd_queue->seen_time) {
            $opd_queue->seen_time = now();
        }
        $opd_queue->save();

        return response()->json($opd_queue);
    }

    /** Emergency first, then urgent, then normal — driver-agnostic CASE ordering. */
    private function priorityOrderSql(): string
    {
        return "CASE priority WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END";
    }
}
