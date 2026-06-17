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

        // Conflict check for therapist — time-range overlap, computed in PHP so it
        // works on any DB driver (no ADDTIME/SEC_TO_TIME). Overlap when
        // newStart < existingEnd AND newEnd > existingStart.
        if (!empty($data['therapist_id'])) {
            $dur      = (int) ($data['duration_minutes'] ?? 30);
            $newStart = strtotime($data['scheduled_time']);
            $newEnd   = $newStart + $dur * 60;

            $sameDay = Appointment::where('therapist_id', $data['therapist_id'])
                ->where('scheduled_date', $data['scheduled_date'])
                ->where('status', '!=', 'cancelled')
                ->get(['scheduled_time', 'duration_minutes']);

            foreach ($sameDay as $a) {
                $es = strtotime($a->scheduled_time);
                $ee = $es + ((int) ($a->duration_minutes ?? 30)) * 60;
                if ($newStart < $ee && $newEnd > $es) {
                    return response()->json(['error' => 'Therapist has a conflicting appointment in this time range.'], 422);
                }
            }
        }

        $appointment = Appointment::create(array_merge($data, [
            'appointment_number' => 'APT-' . strtoupper(uniqid()),
            'is_recurring'       => !empty($data['recurrence_rule']),
        ]));

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
                'title'    => $a->patient->name ?? 'Unknown',
                'start'    => $date . 'T' . $time,
                'end'      => $date . 'T' . $end,
                'extendedProps' => [
                    'patient'      => $a->patient,
                    'therapist_id' => $a->therapist_id,
                    'status'       => $a->status,
                    'notes'        => $a->notes,
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

        $appointment->update($data);

        return response()->json($appointment);
    }
}
