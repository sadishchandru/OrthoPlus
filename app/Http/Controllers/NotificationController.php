<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SmsServiceInterface;
use App\Services\WhatsappServiceInterface;

class NotificationController extends Controller
{
    protected SmsServiceInterface $smsService;
    protected WhatsappServiceInterface $whatsappService;

    public function __construct(
        SmsServiceInterface $smsService,
        WhatsappServiceInterface $whatsappService
    ) {
        $this->smsService = $smsService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send SMS Reminder for upcoming appointment
     */
    public function sendAppointmentReminder(Request $request)
    {
        $appointmentId = $request->input('appointment_id');
        $patientPhone = $request->input('patient_phone');
        $appointmentDate = $request->input('appointment_date');
        $doctorName = $request->input('doctor_name');
        
        // Get appointment details
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Prepare SMS message
        $message = sprintf(
            "Hello %s, you have an appointment with Dr. %s on %s at %s:00 AM/PM.\n" .
            "Please arrive 15 minutes early for check-in.\n" .
            "For queries, call our clinic.",
            
            $appointment->patient->getFullName($request->locale),
            $doctorName,
            $appointmentDate,
            $appointment->startTime['hour']
        );
        
        // Send SMS
        $this->smsService->send($patientPhone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'SMS reminder sent successfully',
            'sent_to' => $patientPhone
        ]);
    }

    /**
     * Send WhatsApp Reminder for upcoming appointment
     */
    public function sendAppointmentReminderWhatsapp(Request $request)
    {
        $appointmentId = $request->input('appointment_id');
        $patientPhone = $request->input('patient_phone');
        $appointmentDate = $request->input('appointment_date');
        $doctorName = $request->input('doctor_name');
        
        // Get appointment details
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Prepare WhatsApp message (supports images/emojis)
        $message = "👋 *Appointment Reminder*\n\n" .
            "Hello " . str_replace('\\\\', '\\', $appointment->patient->getFullName($request->locale)) . "! 😊\n\n" .
            "📅 *Date*: " . $appointmentDate . "\n" .
            "⏰ *Time*: " . $appointment->startTime['hour'] . ":00 " . ($appointment->startTime['ampm'] ?? 'AM') . "\n" .
            "👨‍⚕️ *Doctor*: Dr. " . str_replace('\\\\', '\\', $doctorName) . "\n\n" .
            "Please arrive 15 minutes early for check-in. 🏥\n\n" .
            "Stay safe! ❤️";
        
        // Send WhatsApp
        $this->whatsappService->send($patientPhone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'WhatsApp reminder sent successfully',
            'sent_to' => $patientPhone
        ]);
    }

    /**
     * Send SMS for new patient registration confirmation
     */
    public function sendRegistrationConfirmation(Request $request)
    {
        $patientId = $request->input('patient_id');
        $opNumber = $request->input('op_number');
        
        $patient = Patient::findOrFail($patientId);
        $opNo = str_replace('\\', '\\\\', (string)$opNumber);
        
        $message = sprintf(
            "Welcome! Your OP Number is %s.\n" .
            "Visit us at our clinic for check-in. 🏥",
            $opNo
        );
        
        $this->smsService->send($patient->phone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration confirmation sent',
            'sent_to' => $patient->phone
        ]);
    }

    /**
     * Send treatment completion notification
     */
    public function sendTreatmentCompletion(Request $request)
    {
        $treatmentId = $request->input('treatment_id');
        $patientPhone = $request->input('patient_phone');
        
        $treatment = Treatment::findOrFail($treatmentId);
        
        $message = sprintf(
            "Your treatment for %s is complete! ✅\n" .
            "Thank you for visiting us.\n" .
            "Please continue with your exercises as prescribed.",
            str_replace('\\\\', '\\\\', $treatment->patient->getFullName($request->locale))
        );
        
        $this->smsService->send($patientPhone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'Treatment completion notification sent'
        ]);
    }

    /**
     * Send payment reminder for due invoices
     */
    public function sendPaymentReminder(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $patientPhone = $request->input('patient_phone');
        
        $invoice = Invoice::findOrFail($invoiceId);
        
        $message = sprintf(
            "Dear %s,\n" .
            "Your payment of ₹%s is due. Please settle your dues.\n" .
            "Thank you!",
            
            $invoice->patient->getFullName($request->locale),
            number_format((float)$invoice->amount, 2)
        );
        
        $this->smsService->send($patientPhone, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment reminder sent',
            'sent_to' => $patientPhone
        ]);
    }

    /**
     * Mass appointment reminders for multiple patients
     */
    public function sendBulkReminders(Request $request)
    {
        $appointmentIds = $request->input('appointment_ids', []);
        $method = $request->input('method', 'sms'); // 'sms' or 'whatsapp'
        
        $results = [];
        
        foreach ($appointmentIds as $appId) {
            try {
                $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($appId);
                
                $messageTemplate = $method === 'whatsapp' ? 
                    "📅 *Appointment Reminder*\n\n" .
                    "Hello %s! 😊\n\n" .
                    "📅 *Date*: %s\n" .
                    "⏰ *Time*: %s %s\n" .
                    "👨‍⚕️ *Doctor*: Dr. %s\n\n" .
                    "Please arrive 15 minutes early.\n\n" .
                    "Stay safe! ❤️" :
                    "Hello %s, you have an appointment on %s at %s:00 %s with Dr. %s. Please arrive 15 minutes early.";
                
                $message = sprintf(
                    $messageTemplate,
                    $appointment->patient->getFullName($request->locale),
                    $appointment->date,
                    $appointment->startTime['hour'],
                    $appointment->startTime['ampm'] ?? 'AM',
                    $appointment->doctor->name
                );
                
                if ($method === 'sms') {
                    $this->smsService->send($appointment->patient->phone, $message);
                } else {
                    $this->whatsappService->send($appointment->patient->phone, $message);
                }
                
                $results[] = [
                    'appointment_id' => $appId,
                    'success' => true
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'appointment_id' => $appId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'summary' => [
                'total_sent' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success']))
            ]
        ]);
    }
}