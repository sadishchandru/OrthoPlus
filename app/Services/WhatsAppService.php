<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsApp Service for sending appointment reminders via WhatsApp API
 * Supports Meta (Facebook) Cloud API and Twilio Programmable Messaging
 */
class WhatsAppService
{
    protected $provider = 'meta';
    protected $apiKey = null;
    protected $apiBaseUrl = 'https://graph.facebook.com/v18.0';

    public function __construct()
    {
        $this->configureProvider();
    }

    private function configureProvider(): void
    {
        // Support multiple providers: Meta, Twilio, 360dialog
        $provider = env('WHATSAPP_PROVIDER', 'meta');

        if ($provider === 'twilio') {
            $this->provider = 'twilio';
            $this->apiKey = env('TWILIO_ACCOUNT_SID');
        } else {
            // Meta Cloud API (default)
            $this->apiKey = env('META_APP_SECRET');
        }

        if (!$this->apiKey) {
            Log::warning('WhatsApp API not configured. Please configure in .env file.');
        }
    }

    /**
     * Send WhatsApp message to a phone number
     */
    public function send(string|int $phoneNumber, string $message): bool|string
    {
        // Validate phone number (E.164 format)
        $phone = preg_replace('/^\+/', '', (string)$phoneNumber);
        
        if ($this->provider === 'meta') {
            // Add country code for Meta API
            if (!Str::startsWith($phone, ['9', '1'])) {
                $phone = '91' . $phone;
            }
        }

        try {
            if ($this->provider === 'twilio') {
                return $this->sendViaTwilio($phoneNumber, $message);
            }

            return $this->sendViaMeta($phoneNumber, $message);

        } catch (\Exception $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage());
            
            // Return fallback for UI display
            return false;
        }
    }

    private function sendViaMeta(string $phone, string $message): bool|string
    {
        if (!$this->apiKey) {
            Log::error('Meta API key not configured');
            return false;
        }

        // Prepare message with proper formatting
        $payload = [
            'messaging_type' => 'text',
            'to' => $phone,
            'text' => ['body' => $message],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getMetaToken(),
            'Content-Type' => 'application/json',
        ])->post($this->apiBaseUrl . '/accounts/' . env('META_APP_ID') . '/messages', [
            'messaging_product' => 'whatsapp',
            ...$payload,
        ]);

        return $response->status() === 200;
    }

    private function sendViaTwilio(string $phoneNumber, string $message): bool|string
    {
        if (!$this->apiKey) {
            Log::error('Twilio API key not configured');
            return false;
        }

        // Twilio requires number to match exactly
        $response = Http::asForm()->withHeaders([
            'Authorization' => base64_encode(env('TWILIO_ACCOUNT_SID') . ':' . env('TWILIO_AUTH_TOKEN')),
            'Content-Type' => 'application/json',
        ])->post('https://api.twilio.com/2010-04-01/Accounts/' . env('TWILIO_ACCOUNT_SID') . '/Messages.json', [
            'From' => env('TWILIO_WHATSAPP_NUMBER'),
            'To' => $phoneNumber,
            'Body' => $message,
            'MessagingServiceSid' => env('TWILIO_MSG_SID'),
        ]);

        return $response->status() === 201;
    }

    /**
     * Send bulk WhatsApp messages (for appointment reminders)
     */
    public function sendBulk(array $phoneNumbers, string $message): array
    {
        if (empty($phoneNumbers)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                if ($this->send($phoneNumber, $message)) {
                    $results['success']++;
                    Log::info("WhatsApp sent to: " . $phoneNumber);
                } else {
                    $results['failed']++;
                    $results['details'][$phoneNumber] = 'Failed to send';
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][$phoneNumber] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get Meta OAuth token (simplified for this implementation)
     */
    private function getMetaToken(): string
    {
        // In production, implement proper OAuth flow with Meta
        // For now, use app secret as fallback (not recommended for production)
        $token = session()->get('meta_access_token');

        if (!$token || is_expired($token)) {
            $token = env('META_APP_SECRET') ?: '';
        }

        return $token;
    }
}
