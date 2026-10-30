<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LogisticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_service_status(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('service', 'lumora-logistics')
            ->assertJsonPath('status', 'ok');
    }

    public function test_integration_api_requires_an_integration_key(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_storefront_can_upsert_an_order_and_items(): void
    {
        $store = Store::create(['name' => 'Test Store', 'platform' => 'custom', 'is_active' => true]);

        $response = $this->withHeader('X-Integration-Key', 'demo-logistics-key')->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'external_id' => 'order-1001',
            'order_number' => 'LM-1001',
            'customer_name' => 'Taylor Reed',
            'customer_email' => 'taylor@example.com',
            'total' => 84.50,
            'status' => 'paid',
            'items' => [
                ['sku' => 'SERUM-01', 'name' => 'Velvet Glow Serum', 'quantity' => 2, 'unit_price' => 42.25],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.external_id', 'order-1001');
        $this->assertDatabaseHas('orders', ['external_id' => 'order-1001', 'store_id' => $store->id]);
        $this->assertDatabaseHas('order_items', ['sku' => 'SERUM-01', 'quantity' => 2]);
    }

    public function test_webhook_is_queued_and_duplicate_events_are_ignored(): void
    {
        Queue::fake();
        Store::create(['name' => 'Webhook Store', 'platform' => 'custom', 'is_active' => true]);
        $payload = ['id' => 'evt-1001', 'type' => 'order.created', 'data' => ['order' => ['id' => 'order-1001', 'customer' => ['name' => 'Taylor Reed']]]];

        $this->withHeaders([
            'X-Event-Id' => 'evt-1001',
            'X-Event-Type' => 'order.created',
            'X-Webhook-Signature' => hash_hmac('sha256', json_encode($payload), 'demo-webhook-secret'),
        ])->postJson('/api/v1/webhooks/order-events', $payload)->assertStatus(202)->assertJsonPath('duplicate', false);

        $this->withHeaders([
            'X-Event-Id' => 'evt-1001',
            'X-Event-Type' => 'order.created',
            'X-Webhook-Signature' => hash_hmac('sha256', json_encode($payload), 'demo-webhook-secret'),
        ])->postJson('/api/v1/webhooks/order-events', $payload)->assertOk()->assertJsonPath('duplicate', true);

        Queue::assertPushed(\App\Jobs\ProcessWebhookDelivery::class, 1);
    }
}
