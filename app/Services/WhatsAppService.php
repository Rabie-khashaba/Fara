<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $token;
    private $apiUrl;

    public function __construct()
    {
        $this->token = env('WHATSAPP_API_TOKEN');
        $this->apiUrl = env('WHATSAPP_API_URL');
    }

    /**
     * تنسيق رقم الهاتف المصري
     */
    private function formatPhone($phone)
    {
        // إزالة أي مسافات أو رموز
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // إزالة الصفر من البداية
        $phone = ltrim($phone, '0');

        // إزالة +20 أو 20 لو موجودين
        $phone = preg_replace('/^(20|\+20)/', '', $phone);

        // إضافة 20
        return '20' . $phone;
    }

    /**
     * إرسال رسالة واتساب
     */
    public function send($phone, $message)
    {
        $formattedPhone = $this->formatPhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(90)
            ->retry(2, 100)
            ->post($this->apiUrl . '/api/send-message', [
                'phone' => $formattedPhone,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'phone' => $formattedPhone,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::error('WhatsApp send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone' => $formattedPhone
                ]);

                return [
                    'success' => false,
                    'error' => 'فشل إرسال الرسالة'
                ];
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WhatsApp Connection Error', [
                'error' => $e->getMessage(),
                'phone' => $formattedPhone
            ]);

            return [
                'success' => false,
                'error' => 'مشكلة في الاتصال بخدمة WhatsApp'
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Unexpected Error', [
                'error' => $e->getMessage(),
                'phone' => $formattedPhone
            ]);

            return [
                'success' => false,
                'error' => 'حدث خطأ غير متوقع'
            ];
        }
    }

}