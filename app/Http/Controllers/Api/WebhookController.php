<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookDelivery;
use App\Models\Store;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $eventId = (string) ($request->header('X-Event-Id') ?: data_get($request->all(), 'id'));
        $eventType = (string) ($request->header('X-Event-Type') ?: data_get($request->all(), 'type', 'order.updated'));
        $signature = (string) $request->header('X-Webhook-Signature', '');
        $secret = (string) env('LOGISTICS_WEBHOOK_SECRET', 'demo-webhook-secret');

        if ($eventId === '') {
            return response()->json(['message' => 'X-Event-Id is required.'], 422);
        }

        $expected = hash_hmac('sha256', $rawPayload, $secret);
        if ($signature !== '' && ! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $delivery = WebhookDelivery::firstOrCreate(
            ['event_id' => $eventId],
            [
                'store_id' => Store::query()->where('is_active', true)->value('id'),
                'event_type' => $eventType,
                'signature' => $signature,
                'payload' => $request->all(),
                'status' => 'received',
                'attempts' => 0,
            ],
        );

        if (! $delivery->wasRecentlyCreated) {
            return response()->json(['accepted' => true, 'duplicate' => true, 'delivery_id' => $delivery->id]);
        }

        ProcessWebhookDelivery::dispatch($delivery->id);

        return response()->json(['accepted' => true, 'duplicate' => false, 'delivery_id' => $delivery->id], 202);
    }
}
