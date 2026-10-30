<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_shipments(): void
    {
        $this->get('/shipments')->assertRedirect('/login');
    }

    public function test_admin_can_open_shipments_dashboard_and_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::create(['name' => 'Lumora Beauty', 'platform' => 'custom', 'is_active' => true]);
        $order = Order::create([
            'store_id' => $store->id,
            'external_id' => 'order-2001',
            'order_number' => 'LM-2001',
            'customer_name' => 'Noah Kim',
            'customer_email' => 'noah@example.com',
            'status' => 'paid',
            'total' => 88.00,
            'ordered_at' => now(),
        ]);
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'external_id' => 'shipment-2001',
            'tracking_number' => 'TRK-2001',
            'carrier' => 'DHL Express',
            'service_level' => 'Express',
            'status' => 'in_transit',
            'destination_city' => 'Calamba City',
            'destination_state' => 'Laguna',
            'destination_country' => 'PH',
        ]);

        $this->actingAs($user)
            ->get('/shipments?search=TRK-2001')
            ->assertOk()
            ->assertSee($shipment->lumora_tracking_id)
            ->assertSee('<td>Lumora Express</td>', false)->assertDontSee('<td>DHL Express</td>', false)
            ->assertSee('Calamba City')
            ->assertSee('Track ID')
            ->assertDontSee('<th>Order</th>', false)
            ->assertDontSee('LM-2001');
        foreach ([$shipment->lumora_tracking_id, 'Noah', 'DHL', 'Lumora Express', 'lumora', 'Laguna', 'Calamba City, Laguna'] as $search) {
            $this->get('/shipments?'.http_build_query(['search' => $search]))
                ->assertOk()->assertSee($shipment->lumora_tracking_id);
        }
        $this->assertSame('TRK-2001', $shipment->fresh()->tracking_number);
        $this->get('/')->assertOk()->assertDontSee('>Orders</span>', false)
            ->assertDontSee('>Inventory</span>', false)->assertDontSee('>Exceptions</span>', false)
            ->assertDontSee('>Locations</span>', false)->assertDontSee('>Integrations</span>', false);

        $this->actingAs($user)
            ->get('/shipments/'.$shipment->id)
            ->assertOk()
            ->assertSee($shipment->lumora_tracking_id)
            ->assertSee('Journey so far')
            ->assertSee('Noah Kim');
    }

    public function test_shipment_status_filter_excludes_other_shipments(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::create(['name' => 'Lumora Beauty', 'platform' => 'custom', 'is_active' => true]);
        $order = fn (string $externalId) => Order::create(['store_id' => $store->id, 'external_id' => $externalId, 'customer_name' => 'Customer', 'status' => 'paid', 'total' => 10, 'ordered_at' => now()]);
        $inTransitOrder = $order('ship-order-1');
        $deliveredOrder = $order('ship-order-2');
        $inTransit = Shipment::create(['order_id' => $inTransitOrder->id, 'external_id' => 'ship-1', 'status' => 'in_transit']);
        $delivered = Shipment::create(['order_id' => $deliveredOrder->id, 'external_id' => 'ship-2', 'status' => 'delivered']);

        $this->actingAs($user)->get('/shipments?status=in_transit')->assertOk()->assertSee($inTransit->lumora_tracking_id)->assertDontSee($delivered->lumora_tracking_id);
    }

    public function test_grouped_filters_and_tracking_alias_preserve_carrier_tracking(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::create(['name' => 'Lumora Logistics', 'platform' => 'custom', 'is_active' => true]);
        $order = Order::create(['store_id' => $store->id, 'external_id' => 'group-test', 'customer_name' => 'Test Recipient', 'ordered_at' => now(), 'status' => 'paid', 'total' => 10]);
        $rows = [];
        foreach (['pending', 'packed', 'in_transit', 'out_for_delivery', 'delivered'] as $status) {
            $rows[$status] = Shipment::create([
                'order_id' => $order->id, 'external_id' => $status,
                'status' => $status, 'tracking_number' => 'CARRIER-'.$status,
            ]);
        }
        foreach (['pending' => ['pending', 'packed'], 'in_transit' => ['in_transit', 'out_for_delivery']] as $tab => $statuses) {
            $response = $this->actingAs($user)->get('/shipments?status='.$tab)->assertOk();
            $response->assertViewHas('shipments', fn ($page) => $page->total() === 2);
            foreach ($rows as $status => $shipment) {
                if (in_array($status, $statuses, true)) {
                    $response->assertSee($shipment->lumora_tracking_id);
                } else {
                    $response->assertDontSee($shipment->lumora_tracking_id);
                }
            }
        }
        // Isolate lookup from the pre-existing missing public tracking template.
        $finder = \Mockery::mock(\Illuminate\View\ViewFinderInterface::class);
        $finder->shouldReceive('find')->with('tracking.index')
            ->andReturn(resource_path('views/shipments/show.blade.php'));
        app('view')->setFinder($finder);
        $shipment = $rows['packed'];
        foreach ([$shipment->lumora_tracking_id, $shipment->tracking_number] as $number) {
            $view = app(\App\Http\Controllers\TrackingController::class)->index(
                \Illuminate\Http\Request::create('/track'), $number
            );
            $this->assertSame($shipment->id, $view->getData()['shipment']->id);
        }
    }

    public function test_localization_only_updates_identified_demo_records(): void
    {
        $store = Store::create(['external_store_id' => 'lumora-demo', 'name' => 'Lumora', 'platform' => 'custom', 'is_active' => true]);
        $demo = Order::create(['store_id' => $store->id, 'external_id' => 'LM-2187', 'metadata' => ['source' => 'lumora-demo-seeder'], 'customer_name' => 'Test Recipient', 'ordered_at' => now(), 'status' => 'paid', 'total' => 10]);
        $real = Order::create(['store_id' => $store->id, 'external_id' => 'LM-2186', 'customer_name' => 'Test Recipient', 'ordered_at' => now(), 'status' => 'paid', 'total' => 10]);
        $attributes = ['status' => 'delivered', 'carrier' => 'UPS Ground', 'destination_city' => 'Portland', 'destination_state' => 'OR', 'destination_country' => 'US'];
        $sample = $demo->shipments()->create($attributes + ['external_id' => 'SH-240912-081', 'tracking_number' => 'ORIGINAL']);
        $untouched = $real->shipments()->create($attributes + ['external_id' => 'SH-240912-080']);
        $before = $untouched->fresh()->getAttributes();
        $this->seed(\Database\Seeders\LocalizeLumoraDemoSeeder::class);
        $this->assertSame($before, $untouched->fresh()->getAttributes());
        $this->assertSame('PH', $sample->fresh()->destination_country);
        $this->assertSame('Makati City', $sample->fresh()->destination_city);
        $this->assertSame('ORIGINAL', $sample->fresh()->tracking_number);
        $this->assertSame('delivered', $sample->fresh()->status);
    }
}
