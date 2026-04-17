<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsService
{
    public function send(string $to, string $message): array
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.from');

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Twilio configuration is missing.');
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Twilio SMS sending failed: '.$response->body());
        }

        $payload = $response->json();

        return [
            'id' => $payload['sid'] ?? null,
            'status' => $payload['status'] ?? 'sent',
        ];
    }
}

