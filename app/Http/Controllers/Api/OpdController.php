<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdQueue;
use App\Models\ClinicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'patient_id'      => 'required|exists:patients,id',
            'doctor_id'       => 'nullable|integer',
            'priority'        => 'nullable|in:normal,urgent,emergency',
            'date'            => 'nullable|date',
            'chief_complaint' => 'nullable|string',
            'vitals'          => 'nullable|array',
        ]);

        $date = $data['date'] ?? now()->toDateString();

        // Block the same patient being queued twice while still active today.
        $existing = OpdQueue::whereDate('date', $date)
            ->where('patient_id', $data['patient_id'])
            ->whereIn('status', ['waiting', 'in-progress'])
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'This patient is already in today\'s queue (Token #'
                    . str_pad($existing->token_number, 3, '0', STR_PAD_LEFT) . ', ' . $existing->status . ').',
            ], 422);
        }

        // Atomic token allocation (row lock) → no duplicate token numbers under concurrency.
        $entry = DB::transaction(function () use ($data, $date) {
            $nextToken = (OpdQueue::whereDate('date', $date)->lockForUpdate()->max('token_number') ?? 0) + 1;
            return OpdQueue::create([
                'patient_id'      => $data['patient_id'],
                'token_number'    => $nextToken,
                'date'            => $date,
                'doctor_id'       => $data['doctor_id'] ?? null,
                'priority'        => $data['priority'] ?? 'normal',
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'vitals'          => $data['vitals'] ?? null,
                'status'          => 'waiting',
                'arrival_time'    => now(),
            ]);
        });

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

    /** Complete the consult: mark token done + spin off a clinical record. */
    public function saveConsultation(Request $request, OpdQueue $opd_queue)
    {
        $data = $request->validate([
            'chief_complaint'  => 'nullable|string',
            'vitals'           => 'nullable|array',
            'soap_notes'       => 'nullable|array',
            'body_map'         => 'nullable|array',
            'vas_score'        => 'nullable|numeric',
            'rom'              => 'nullable|array',
            'ortho_tests'      => 'nullable|array',
            'outcome_measures' => 'nullable|array',
        ]);

        $opd_queue->update([
            'chief_complaint' => $data['chief_complaint'] ?? $opd_queue->chief_complaint,
            'vitals'          => $data['vitals'] ?? $opd_queue->vitals,
            'status'          => 'completed',
            'seen_time'       => $opd_queue->seen_time ?? now(),
        ]);

        $record = ClinicalRecord::create([
            'patient_id'       => $opd_queue->patient_id,
            'soap_notes'       => $data['soap_notes'] ?? [],
            'body_map'         => $data['body_map'] ?? [],
            'vas_score'        => $data['vas_score'] ?? 0,
            'rom'              => $data['rom'] ?? [],
            'ortho_tests'      => $data['ortho_tests'] ?? [],
            'outcome_measures' => $data['outcome_measures'] ?? [],
            'created_by'       => auth()->id(),
        ]);

        return response()->json([
            'queue'           => $opd_queue,
            'clinical_record' => $record,
        ]);
    }

    /** Emergency first, then urgent, then normal — driver-agnostic CASE ordering. */
    private function priorityOrderSql(): string
    {
        return "CASE priority WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END";
    }
}
