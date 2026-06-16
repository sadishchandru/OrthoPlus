<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Appointment;
use App\Services\SmsServiceInterface;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    protected SmsServiceInterface $smsService;

    public function __construct(SmsServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send appointment reminders for today's appointments
     */
    public function sendTodaysReminders(Request $request, string $method = 'sms')
    {
        try {
            // Get appointments for today with patients and doctors
            $appointments = Appointment::whereDate('date', now()->toDateString())
                ->with(['patient:phone,get_full_name', 'doctor'])
                ->get();

            foreach ($appointments as $appointment) {
                $appointment->patient->load('image_path'); // For photo
                
                if (!$appointment->patient->phone) continue;

                // Determine locale for patient
                $locale = $request->input('locale', 'en');
                
                // Prepare SMS message with doctor and appointment details
                $doctorName = $appointment->doctor?->name ?? 'Dr. TBA';
                $opNo = (string)($appointment->patient->getOPNumberWithSuffix() ?? '');

                $messageTemplate = "Dear %s (OP: %s), you have an appointment with Dr. %s on %s at %s:00 %s.\nPlease arrive 15 minutes early.";
                
                // Handle unicode for phone messages - replace common emojis
                $message = preg_replace('/\x{F0}\x{9F\x{[0-9A-F]{2}}\x{9F}[0-9A-Fa-f]{2}}/u', 'EMOJI_PLACEHOLDER', $messageTemplate);
                $message = sprintf($message, 
                    str_replace('\\\\', '\\\\', $appointment->patient->getFullName($locale)),
                    $opNo,
                    str_replace('\\\\', '\\\\', $doctorName),
                    $appointment->date,
                    $appointment->startTime['hour'],
                    $appointment->startTime['ampm'] ?? 'AM'
                );

                // If WhatsApp is selected and phone number format is valid
                if ($method === 'whatsapp') {
                    // Replace EMOJI_PLACEHOLDER with actual emojis for WhatsApp
                    $message = str_replace('EMOJI_PLACEHOLDER', "❤️", $message);
                    
                    $this->whatsappService->send(
                        $appointment->patient->phone,
                        trim($message)
                    );
                } else {
                    // Send regular SMS
                    $this->smsService->send(
                        $appointment->patient->phone,
                        trim($message)
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reminders sent successfully',
                'total_sent' => count($appointments)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send appointment reminders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send some reminders',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Send payment reminders for overdue invoices
     */
    public function sendPaymentReminders(Request $request)
    {
        try {
            // Get invoices with due payments
            $invoices = Invoice::where('status', 'pending')
                ->whereDate('due_date', '<=', now()->toDateString())
                ->with(['patient:phone'])
                ->get();

            foreach ($invoices as $invoice) {
                if (!$invoice->patient->phone) continue;

                $message = sprintf(
                    "Dear %s, your payment of ₹%s is overdue. Please settle your dues at your earliest convenience.\nThank you!",
                    str_replace('\\\\', '\\\\', $invoice->patient->getFullName($request->locale ?? 'en')),
                    number_format((float)$invoice->amount, 2)
                );

                // Try WhatsApp first if available, fall back to SMS
                $this->whatsappService?->send(
                    $invoice->patient->phone,
                    trim($message)
                ) ?: true;

                $this->smsService->send(
                    $invoice->patient->phone,
                    trim($message)
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment reminders sent',
                'total_sent' => count($invoices)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send payment reminders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send some reminders',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Send bulk notifications (SMS/WhatsApp)
     */
    public function sendBulkNotifications(Request $request)
    {
        try {
            $method = $request->input('method', 'sms'); // 'sms' or 'whatsapp'
            $message = $request->input('message');
            
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message is required'
                ], 400);
            }

            // Get all patients with phone numbers
            $patients = Patient::whereNotNull('phone')
                ->withCount('appointments as appointment_count')
                ->orderBy('last_visit_at', 'desc')
                ->get();

            $results = [];

            foreach ($patients as $patient) {
                try {
                    // Replace emojis for SMS compatibility
                    if ($method === 'sms') {
                        $message = preg_replace('/\x{F0}\x{9F\x{[0-9A-F]{2}}\x{9F}[0-9A-Fa-f]{2}}/u', 'EMOJI_PLACEHOLDER', $message);
                    } else {
                        // Keep emojis for WhatsApp
                        $message = str_replace('EMOJI_PLACEHOLDER', '❤️', $message);
                    }

                    // Send message
                    if ($method === 'whatsapp') {
                        $this->whatsappService->send($patient->phone, trim($message));
                    } else {
                        $this->smsService->send($patient->phone, trim($message));
                    }

                    $results[] = [
                        'patient_id' => $patient->id,
                        'phone' => $patient->phone,
                        'name' => str_replace('\\\\', '\\\\', (string)$patient->getFullName($request->locale ?? 'en')),
                        'status' => 'sent'
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'patient_id' => $patient->id,
                        'phone' => $patient->phone,
                        'name' => str_replace('\\\\', '\\\\', (string)$patient->getFullName($request->locale ?? 'en')),
                        'status' => 'failed',
                        'error' => config('app.debug') ? $e->getMessage() : null
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk notifications sent',
                'data' => $results,
                'summary' => [
                    'total' => count($patients),
                    'sent' => count(array_filter($results, fn($r) => $r['status'] === 'sent')),
                    'failed' => count(array_filter($results, fn($r) => $r['status'] === 'failed'))
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send bulk notifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send some notifications',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}