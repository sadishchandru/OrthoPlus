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

        // Conflict check for therapist
        if (!empty($data['therapist_id'])) {
            $conflict = Appointment::where('therapist_id', $data['therapist_id'])
                ->where('scheduled_date', $data['scheduled_date'])
                ->where('scheduled_time', $data['scheduled_time'])
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($conflict) {
                return response()->json(['error' => 'Therapist has a conflicting appointment at this time.'], 422);
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
            ->map(fn($a) => [
                'id'       => $a->id,
                'title'    => $a->patient->name ?? 'Unknown',
                'start'    => $a->scheduled_date . 'T' . $a->scheduled_time,
                'end'      => $a->scheduled_date . 'T' . date('H:i', strtotime($a->scheduled_time) + ($a->duration_minutes ?? 30) * 60),
                'extendedProps' => [
                    'patient'      => $a->patient,
                    'therapist_id' => $a->therapist_id,
                    'status'       => $a->status,
                    'notes'        => $a->notes,
                ],
            ]);

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
