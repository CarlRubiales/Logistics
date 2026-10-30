<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\ShipmentStatusService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTimelineTest extends TestCase
{
    use RefreshDatabase;

    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $store = Store::create(['name' => 'Lumora', 'platform' => 'custom', 'is_active' => true]);
        $order = Order::create(['store_id' => $store->id, 'external_id' => 'timeline', 'customer_name' => 'Juan Dela Cruz', 'status' => 'paid', 'total' => 0]);
        $this->shipment = $order->shipments()->create(['status' => 'pending', 'carrier' => 'LBC Express', 'destination_city' => 'Santa Cruz', 'destination_state' => 'Laguna', 'destination_country' => 'PH']);
        $this->shipment->trackingEvents()->create(['status' => 'pending', 'label' => 'Parcel received', 'occurred_at' => now(), 'source' => 'sorting_center']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_full_flow_updates_one_record_history_filters_and_counters(): void
    {
        $id = $this->shipment->lumora_tracking_id;
        foreach (['packed', 'in_transit', 'out_for_delivery', 'delivered'] as $index => $status) {
            $this->post('/shipments/'.$this->shipment->id.'/status', [
                'status' => $status, 'location' => 'Santa Cruz, Laguna', 'description' => 'Actual processing note '.$index,
            ])->assertRedirect('/shipments/'.$this->shipment->id)->assertSessionHasNoErrors();
            $this->shipment->refresh();
            $this->assertSame($status, $this->shipment->status);
            $this->assertSame($id, $this->shipment->lumora_tracking_id);
            $this->assertSame($status, $this->shipment->trackingEvents()->first()->status);
            $this->assertSame($index + 2, $this->shipment->trackingEvents()->count());
            $this->get('/shipments/'.$this->shipment->id)->assertOk()
                ->assertSee('Lumora Express')->assertDontSee('LBC Express')
                ->assertSee('Actual processing note '.$index)->assertSee('aria-current="step"', false);
        }
        $this->assertNotNull($this->shipment->delivered_at);
        $this->get('/shipments?status=delivered')->assertOk()->assertSee($id)
            ->assertViewHas('stats', fn ($s) => $s['delivered'] === 1 && $s['in_transit'] === 0);
        $this->get('/shipments?status=in_transit')->assertViewHas('shipments', fn ($p) => $p->total() === 0);
        $this->get('/reports')->assertViewHas('statusTotals', fn ($s) => $s['delivered'] === 1);
    }

    public function test_refresh_retry_and_backwards_transitions_do_not_add_events(): void
    {
        $url = '/shipments/'.$this->shipment->id.'/status';
        $this->post($url, ['status' => 'in_transit'])->assertSessionHasNoErrors();
        $this->post($url, ['status' => 'in_transit'])->assertSessionHasNoErrors();
        $this->get('/shipments/'.$this->shipment->id)->assertOk();
        $this->get('/shipments/'.$this->shipment->id)->assertOk();
        $this->post($url, ['status' => 'packed'])->assertSessionHasErrors('status');
        $this->assertSame(2, $this->shipment->trackingEvents()->count());
        $this->assertSame('in_transit', $this->shipment->fresh()->status);
    }

    public function test_api_and_direct_model_updates_record_exactly_one_event(): void
    {
        $this->shipment->update(['status' => 'packed']);
        $this->assertSame(2, $this->shipment->trackingEvents()->count());
        $this->withHeader('X-Integration-Key', 'demo-logistics-key')
            ->postJson('/api/v1/shipments/'.$this->shipment->id.'/status', ['status' => 'out_for_delivery'])->assertOk();
        $this->assertSame(3, $this->shipment->trackingEvents()->count());
        $this->withHeader('X-Integration-Key', 'demo-logistics-key')
            ->postJson('/api/v1/shipments/'.$this->shipment->id.'/status', ['status' => 'pending'])->assertUnprocessable();
    }

    public function test_edit_authorization_and_store_boundaries(): void
    {
        foreach (['support', 'rider'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post('/shipments/'.$this->shipment->id.'/status', ['status' => 'packed'])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $other = Store::create(['name' => 'Other', 'platform' => 'custom', 'is_active' => true]);
        $order = Order::create(['store_id' => $other->id, 'external_id' => 'other', 'customer_name' => 'Other', 'status' => 'paid', 'total' => 0]);
        $shipment = $order->shipments()->create(['status' => 'pending']);
        $this->post('/shipments/'.$shipment->id.'/status', ['status' => 'packed'])->assertNotFound();
        $this->post('/shipments/999999/status', ['status' => 'packed'])->assertNotFound();
    }

    public function test_event_failure_rolls_back_status_change(): void
    {
        TrackingEvent::creating(fn () => throw new \RuntimeException('Simulated event write failure'));
        try {
            app(ShipmentStatusService::class)->transition($this->shipment, ['status' => 'packed']);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('pending', $this->shipment->fresh()->status);
            $this->assertSame(1, $this->shipment->trackingEvents()->count());
        } finally {
            TrackingEvent::flushEventListeners();
        }
    }

    public function test_exception_and_empty_states_do_not_fake_progress(): void
    {
        app(ShipmentStatusService::class)->transition($this->shipment, ['status' => 'exception']);
        $this->get('/shipments/'.$this->shipment->id)->assertOk()
            ->assertSee('normal delivery progress is paused')->assertDontSee('aria-current="step"', false);
        $this->shipment->trackingEvents()->delete();
        $this->get('/shipments/'.$this->shipment->id)->assertOk()->assertSee('No tracking activity yet.');
    }

    public function test_details_render_real_notes_safely_and_in_timestamp_order(): void
    {
        app(ShipmentStatusService::class)->transition($this->shipment, ['status' => 'in_transit', 'description' => '<script>unsafe()</script>', 'location' => 'Lumora Sorting Hub — Laguna']);
        $response = $this->get('/shipments/'.$this->shipment->id)->assertOk()
            ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)->assertDontSee('<script>unsafe()</script>', false)
            ->assertSee('shipment-history-event', false);
        $events = $response->viewData('shipment')->trackingEvents;
        $this->assertSame('in_transit', $events->first()->status);
        if ($path = getenv('LUMORA_RENDER_PATH')) {
            $html = $response->getContent();
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
            $html = preg_replace('/<link\b[^>]*>/i', '', $html);
            $html = preg_replace('/<script\b[^>]*src=[^>]*><\/script>/i', '', $html);
            $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
            $html = str_replace('</body>', '<script>'.file_get_contents(resource_path('js/app.js')).'</script></body>', $html);
            file_put_contents($path, $html);
        }
    }
}