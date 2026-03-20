<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class WebsocketNotifier
{
    public static function send(string $type, array $data = [], ?string $audience = null): void
    {
        $payload = [
            'type' => $type,
            'data' => $data,
        ];

        if ($audience !== null) {
            $payload['audience'] = $audience;
        }

        try {
            Http::timeout(2)->post(
                env('WS_EVENT_URL', 'http://localhost:3000/event'),
                $payload
            );
        } catch (\Throwable $exception) {
            \Log::warning('Failed to send websocket event: ' . $exception->getMessage());
        }
    }
}
