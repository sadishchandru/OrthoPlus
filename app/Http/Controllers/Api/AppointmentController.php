<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'therapist_id'     => 'nullable|exists:therapists,id',
            'room_id'          => 'nullable|exists:rooms,id',
            'scheduled_date'   => 'required|date',
            'scheduled_time'   => 'required',
            'duration_minutes' => 'nullable|in:15,30,45,60',
            'recurrence_rule'  => 'nullable|array',
            'notes'            => 'nullable|string',
        ]);

        $dur = (int) ($data['duration_minutes'] ?? 30);

        // Therapist double-book.
        if (!empty($data['therapist_id']) && $this->slotConflict(
            'therapist_id', $data['therapist_id'], $data['scheduled_date'], $data['scheduled_time'], $dur
        )) {
            return response()->json(['error' => 'Therapist has a conflicting appointment in this time range.', 'field' => 'scheduled_time'], 422);
        }

        // Patient double-book.
        if ($this->slotConflict(
            'patient_id', $data['patient_id'], $data['scheduled_date'], $data['scheduled_time'], $dur
        )) {
            return response()->json(['error' => 'Patient already has an appointment in this time range.', 'field' => 'scheduled_time'], 422);
        }

        // Daily sequential queue token (per scheduled_date), shared new + revisit.
        $token = (int) Appointment::where('scheduled_date', $data['scheduled_date'])->max('token_number') + 1;

        // New vs revisit derived from prior clinical records (no manual flag).
        $visitType = \App\Models\ClinicalRecord::where('patient_id', $data['patient_id'])->exists() ? 'revisit' : 'new';

        $appointment = Appointment::create(array_merge($data, [
            'appointment_number' => 'APT-' . strtoupper(uniqid()),
            'token_number'       => $token,
            'is_recurring'       => !empty($data['recurrence_rule']),
        ]));

        $appointment->visit_type = $visitType;

        return response()->json($appointment, 201);
    }

    public function calendar(Request $request)
    {
        $request->validate([
            'start'        => 'required|date',
            'end'          => 'required|date',
            'therapist_id' => 'nullable|exists:therapists,id',
        ]);

        $appointments = Appointment::with(['patient:id,name,op_number,phone'])
            ->whereBetween('scheduled_date', [$request->start, $request->end])
            ->when($request->therapist_id, fn($q) => $q->where('therapist_id', $request->therapist_id))
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(function ($a) {
                // scheduled_date is cast to date (Carbon) — must format to Y-m-d,
                // else "2026-06-16 00:00:00" concatenates into an invalid ISO string
                // and FullCalendar silently drops the event.
                $date = $a->scheduled_date->format('Y-m-d');
                $time = date('H:i:s', strtotime($a->scheduled_time));
                $end  = date('H:i:s', strtotime($a->scheduled_time) + ($a->duration_minutes ?? 30) * 60);
                return [
                'id'       => $a->id,
                'title'    => ($a->token_number ? '#' . $a->token_number . ' ' : '') . ($a->patient->name ?? 'Unknown'),
                'start'    => $date . 'T' . $time,
                'end'      => $date . 'T' . $end,
                'extendedProps' => [
                    'patient'      => $a->patient,
                    'therapist_id' => $a->therapist_id,
                    'status'       => $a->status,
                    'notes'        => $a->notes,
                    'token_number' => $a->token_number,
                ],
                ];
            });

        return response()->json($appointments);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'scheduled_date'   => 'nullable|date',
            'scheduled_time'   => 'nullable',
            'duration_minutes' => 'nullable|in:15,30,45,60',
            'status'           => 'nullable|in:scheduled,completed,cancelled,no_show',
            'therapist_id'     => 'nullable|exists:therapists,id',
            'notes'            => 'nullable|string',
        ]);

        // Re-check conflicts only when the slot/therapist moves (not on status/notes
        // edits). Effective values fall back to the row's current ones; exclude self.
        $movesSlot = array_intersect_key($data, array_flip(['scheduled_date', 'scheduled_time', 'duration_minutes', 'therapist_id']));
        if ($movesSlot && ($data['status'] ?? $appointment->status) !== 'cancelled') {
            $date = $data['scheduled_date'] ?? $appointment->scheduled_date->format('Y-m-d');
            $time = $data['scheduled_time'] ?? $appointment->scheduled_time;
            $dur  = (int) ($data['duration_minutes'] ?? $appointment->duration_minutes ?? 30);
            $ther = $data['therapist_id']   ?? $appointment->therapist_id;

            if (!empty($ther) && $this->slotConflict('therapist_id', $ther, $date, $time, $dur, $appointment->id)) {
                return response()->json(['error' => 'Therapist has a conflicting appointment in this time range.', 'field' => 'scheduled_time'], 422);
            }
            if ($this->slotConflict('patient_id', $appointment->patient_id, $date, $time, $dur, $appointment->id)) {
                return response()->json(['error' => 'Patient already has an appointment in this time range.', 'field' => 'scheduled_time'], 422);
            }
        }

        $appointment->update($data);

        return response()->json($appointment);
    }

    public function checkAvailability(Request $request)
    {
        $data = $request->validate([
            'therapist_id'     => 'required|exists:therapists,id',
            'scheduled_date'   => 'required|date',
            'scheduled_time'   => 'required',
            'duration_minutes' => 'nullable|in:15,30,45,60',
            'exclude_id'       => 'nullable|integer',
        ]);

        $conflict = $this->slotConflict(
            'therapist_id',
            $data['therapist_id'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            (int) ($data['duration_minutes'] ?? 30),
            $data['exclude_id'] ?? null
        );

        return response()->json([
            'available' => !$conflict,
            'message'   => $conflict ? 'Slot already booked for this therapist' : 'Slot available',
        ]);
    }

    /**
     * Time-range overlap on a given day for a column (therapist_id|patient_id),
     * computed in PHP so it works on any DB driver (no ADDTIME/SEC_TO_TIME).
     * Overlap when newStart < existingEnd AND newEnd > existingStart.
     */
    private function slotConflict(string $column, $id, string $date, string $time, int $dur, $excludeId = null): bool
    {
        $newStart = strtotime($time);
        $newEnd   = $newStart + $dur * 60;

        return Appointment::where($column, $id)
            ->where('scheduled_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get(['scheduled_time', 'duration_minutes'])
            ->contains(function ($a) use ($newStart, $newEnd) {
                $es = strtotime($a->scheduled_time);
                $ee = $es + ((int) ($a->duration_minutes ?? 30)) * 60;
                return $newStart < $ee && $newEnd > $es;
            });
    }
}
