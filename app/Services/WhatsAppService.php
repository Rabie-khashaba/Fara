<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
     * Format phone number in international format (E.164) for all countries.
     * Examples:
     * - +2010xxxxxxx
     * - 002010xxxxxxx
     * - 2010xxxxxxx
     */


    // private function formatPhone($phone)
    // {
    //     // إزالة أي مسافات أو رموز
    //     $phone = preg_replace('/[^0-9]/', '', $phone);

    //     // إزالة الصفر من البداية
    //     $phone = ltrim($phone, '0');

    //     // إزالة +20 أو 20 لو موجودين
    //     $phone = preg_replace('/^(20|\+20)/', '', $phone);

    //     // إضافة 20
    //     return '20' . $phone;
    // }
    private function formatPhone(string $phone): string
    {
        $phone = trim($phone);

        // Support Arabic numerals in input.
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        // Remove separators while keeping a possible leading '+'.
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // If local Egyptian format is provided (e.g. 010xxxxxxxx),
        // convert to international digits required by the provider (20xxxxxxxxxx).
        if (preg_match('/^0\d{8,14}$/', $phone)) {
            return '20' . ltrim($phone, '0');
        }

        // Accept already-normalized international digits without '+' for non-Egypt codes
        // (e.g. 971501578185). Keep rejecting 20... without '+' to avoid 20100... inputs.
        if (preg_match('/^[1-9]\d{7,14}$/', $phone)) {
            if (str_starts_with($phone, '20')) {
                throw new InvalidArgumentException('Egypt numbers in international format must start with +20 or 0020.');
            }

            return $phone;
        }

        // Enforce explicit international prefix for remaining formats:
        // accepted only if starts with '+' or '00'.
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        } elseif (! str_starts_with($phone, '+')) {
            throw new InvalidArgumentException('Phone must start with + or 00 country code prefix.');
        }

        // E.164: +[country code][subscriber number], 8-15 digits.
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw new InvalidArgumentException('Invalid phone format. Use country code, e.g. +2010xxxxxxx');
        }

        // Most providers expect the phone number without '+'.
        return ltrim($phone, '+');
    }

    public function send($phone, $message)
    {
        try {
            $formattedPhone = $this->formatPhone((string) $phone);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(90)
                ->retry(2, 100)
                ->post($this->apiUrl . '/api/send-message', [
                    'phone' => $formattedPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'phone' => $formattedPhone,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('WhatsApp send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'phone' => $formattedPhone,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send message',
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'Invalid phone number. Use local Egypt format (010...), +/00 country code, or international digits like 971... (except 20... without +).',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WhatsApp Connection Error', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'error' => 'Connection error while contacting WhatsApp service',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp Unexpected Error', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected error occurred',
            ];
        }
    }
}
