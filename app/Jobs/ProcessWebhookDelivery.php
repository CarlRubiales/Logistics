<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\OrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(OrderSyncService $syncService): void
    {
        $delivery = WebhookDelivery::findOrFail($this->deliveryId);
        $delivery->increment('attempts');

        if (in_array($delivery->event_type, ['order.created', 'order.updated', 'order.paid'], true)) {
            $syncService->upsertFromWebhook($delivery->payload);
        }

        $delivery->update(['status' => 'processed', 'processed_at' => now(), 'error' => null]);
    }

    public function failed(\Throwable $exception): void
    {
        WebhookDelivery::whereKey($this->deliveryId)->update([
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);
    }
}
