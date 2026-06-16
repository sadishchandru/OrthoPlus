<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Controller for managing WhatsApp message sending operations
 */
class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send WhatsApp message (protected - requires authentication)
     */
    public function send(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:4800',
        ], [
            'phone.required' => 'Phone number is required',
            'message.required' => 'Message content is required',
            'message.max' => 'Message too long (max 4800 characters)',
        ]);

        // Get authenticated user
        $user = Auth::user();

        // Check if user has permission to send WhatsApp messages
        if (!$this->canSendMessage($user)) {
            return response()->json([
                'message' => 'Unauthorized - Admin privileges required',
                'status_code' => 403,
            ], 403);
        }

        // Log message content for audit trail
        Log::channel('whatsapp')->info(
            'WhatsApp Message Sent',
            [
                'user_id' => $user->id,
                'phone' => $request->phone,
                'message_preview' => substr($request->message, 0, 100) . '...',
            ]
        );

        // Send message
        $result = $this->whatsappService->send(
            $request->phone,
            $request->message
        );

        return response()->json([
            'message' => 'WhatsApp message sent successfully',
            'success' => $result === true,
            'status_code' => 200,
        ], $result === true ? 200 : 400);
    }

    /**
     * Send bulk WhatsApp messages (for appointment reminders)
     */
    public function sendBulk(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'phone_numbers' => 'required|array|min:1',
            'message' => 'required|string|max:4800',
            'schedule_date' => 'nullable|date',
        ]);

        // Check permissions
        if (!$this->canSendMessage(Auth::user())) {
            return response()->json([
                'message' => 'Unauthorized',
                'status_code' => 403,
            ], 403);
        }

        // Send bulk messages
        $results = $this->whatsappService->sendBulk(
            $request->phone_numbers,
            $request->message
        );

        return response()->json([
            'message' => 'Bulk WhatsApp messages processed',
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
            'total_sent' => count($request->phone_numbers),
            'details' => $results['details'] ?? [],
        ]);
    }

    /**
     * Get WhatsApp service status (for admin debugging)
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'provider' => env('WHATSAPP_PROVIDER', 'meta'),
            'configured' => !empty(env('META_APP_ID')),
            'app_id' => env('META_APP_ID'),
            'api_version' => 'v18.0',
        ]);
    }

    /**
     * Send appointment reminder via WhatsApp
     */
    public function sendAppointmentReminder(Request $request): JsonResponse
    {
        // Validate appointment data
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'patient_phone' => 'required|string',
            'reminder_type' => 'in:daily,weekly,imminent',
        ]);

        // Get appointment details
        $appointment = Appointment::findOrFail($validated['appointment_id']);
        
        // Schedule based on reminder type
        $sentTime = now();
        
        // Check if appointment is confirmed and not in past
        if (!$appointment->isConfirmed() || $appointment->startTime()->isPast()) {
            return response()->json([
                'message' => 'Appointment is not eligible for reminder',
                'status_code' => 400,
            ], 400);
        }

        // Prepare message template
        $template = [
            ':patient_name' => $appointment->patient?.fullName ?? 'Patient',
            ':appointment_date' => \Carbon\Carbon::parse($appointment->startTime)->format('d M Y'),
            ':appointment_time' => \Carbon\Carbon::parse($appointment->startTime)->format('h:i A'),
            ':location' => $this->getAppointmentLocation($appointment),
        ];

        // Generate message based on reminder type
        switch ($validated['reminder_type']) {
            case 'daily':
                $message = "Hello {{:patient_name}}, don't forget your appointment on {{:appointment_date}} at {{:appointment_time}}. Location: {{:location}}.";
                break;

            case 'weekly':
                $message = "Hi {{:patient_name}}, this is a friendly reminder about your upcoming appointment scheduled for {{:appointment_date}} at {{:appointment_time}} at {{:location}}. We look forward to seeing you!";
                break;

            case 'imminent':
                $message = "URGENT: Your appointment is TOMORROW/TONIGHT at {{:appointment_time}}. Location: {{:location}}. Please confirm your attendance or contact us.";
                break;
        }

        // Send message
        $result = $this->whatsappService->send(
            $validated['patient_phone'],
            str_replace($template, [], $message)
        );

        // Update appointment log
        AppointmentReminderLog::create([
            'appointment_id' => $appointment->id,
            'recipient_phone' => $validated['patient_phone'],
            'message_sent_at' => now(),
            'reminder_type' => $validated['reminder_type'],
            'status' => $result === true ? 'sent' : 'failed',
            'attempt_count' => AppointmentReminderLog::where('appointment_id', $appointment->id)
                ->where('reminder_type', $validated['reminder_type'])
                ->whereBetween('message_sent_at', now()->subDay(), now())
                ->count(),
        ]);

        return response()->json([
            'message' => 'Appointment reminder sent',
            'status_code' => 200,
        ], $result === true ? 201 : 202);
    }

    /**
     * Get appointment location details
     */
    private function getAppointmentLocation($appointment): string
    {
        if ($appointment->location_name) {
            return env('WHATSAPP_LOCATION', 'OrthoPlus Clinic').' - ' . $appointment->location_name;
        }

        return env('WHATSAPP_LOCATION', 'OrthoPlus Clinic');
    }

    /**
     * Check if user has permission to send WhatsApp messages
     */
    private function canSendMessage($user): bool
    {
        // Only admins and specific roles can send WhatsApp messages
        $allowedRoles = ['admin', 'front_desk_manager', 'billing_manager'];
        
        return in_array($user->role, $allowedRoles);
    }

    /**
     * Get scheduled appointments needing reminders (for bulk sending)
     */
    public function getPendingReminders(Request $request): JsonResponse
    {
        // Only admins can access this
        if (!$this->canSendMessage(Auth::user())) {
            return response()->json([
                'message' => 'Unauthorized',
                'status_code' => 403,
            ], 403);
        }

        $perPage = request('per_page', 50);
        $offset = request('offset', 0);

        // Query appointments needing reminders
        $appointments = Appointment::with(['patient'])
            ->where('status', 'confirmed')
            ->whereBetween('startTime', now(), now()->addWeek())
            ->whereNull('cancelledAt')
            ->orderBy('startTime')
            ->paginate($perPage, ['*'], 'offset', $offset);

        // Get phone numbers for these appointments
        $phoneNumbers = [];
        foreach ($appointments->items() as $appointment) {
            if ($appointment->patient && $appointment->patient->phone) {
                $phoneNumbers[] = preg_replace('/^\+/', '', preg_replace('/[^0-9]/', '', (string)$appointment->patient->phone));
            }
        }

        // Remove duplicates
        $uniquePhoneNumbers = array_unique($phoneNumbers);

        return response()->json([
            'message' => 'Pending appointments for reminder',
            'appointments_count' => $appointments->total(),
            'unique_patient_numbers' => count($uniquePhoneNumbers),
            'sample_appointments' => $appointments->items() ?? [],
            'phone_numbers' => $uniquePhoneNumbers,
        ]);
    }
}