<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimeEventService
{
    public static function dispatch(string $type, array $data, array $recipients = []): void
    {
        $endpoint = trim((string) env('WS_EVENT_URL', ''));
        if ($endpoint === '') {
            return;
        }

        $recipientIds = collect($recipients)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        try {
            Http::timeout(2)->post($endpoint, [
                'type' => $type,
                'data' => $data,
                'recipients' => $recipientIds,
            ])->throw();
        } catch (Throwable $exception) {
            Log::warning('Realtime event dispatch failed.', [
                'type' => $type,
                'endpoint' => $endpoint,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
