<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\DestinationArea;
use App\Services\ParcelIntakeService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Store::create(['name' => 'Inactive', 'platform' => 'custom', 'is_active' => false]);
        Store::create(['name' => 'Lumora', 'platform' => 'custom', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'barcode' => '123456789012', 'recipient_name' => 'Juan Dela Cruz',
            'recipient_phone' => '09171234567', 'address_line' => '123 Rizal Street',
            'city' => 'Santa Cruz', 'province' => 'Laguna', 'postal_code' => '4009',
        ], $overrides);
    }

    public function test_receive_creates_one_shared_shipment_and_updates_search_counters_and_tracking(): void
    {
        $this->from('/sorting-center')->post('/sorting-center/receive', $this->payload(['parcel_number' => 'FORGED']))
            ->assertRedirect('/sorting-center')->assertSessionHas('success', 'Parcel received successfully. Track ID: LM-TRK-000001');
        $shipment = Shipment::sole();
        $parcel = Parcel::sole();
        $this->assertSame($shipment->id, $parcel->shipment_id);
        $this->assertSame($shipment->order_id, $parcel->order_id);
        $this->assertSame(Store::where('is_active', true)->value('id'), $parcel->store_id);
        $this->assertSame('LM-TRK-000001', $parcel->parcel_number);
        $this->assertSame('123456789012', $parcel->barcode);
        $this->assertSame('Lumora Express', $shipment->carrier);
        $this->assertSame('pending', $shipment->status);
        $this->assertSame('PH', $shipment->destination_country);
        $this->assertSame('123 Rizal Street', $shipment->order->shipping_address['line1']);
        $this->assertDatabaseHas('tracking_events', ['shipment_id' => $shipment->id, 'label' => 'Parcel received']);
        $this->get('/shipments')->assertOk()->assertSee($shipment->lumora_tracking_id)
            ->assertSee('Santa Cruz, Laguna')
            ->assertViewHas('stats', fn ($stats) => $stats['all'] === 1 && $stats['pending'] === 1);
        foreach ([$shipment->lumora_tracking_id, 'Juan', $parcel->barcode, $parcel->recipient_phone, 'Santa Cruz, Laguna'] as $search) {
            $this->get('/shipments?'.http_build_query(['search' => $search]))
                ->assertOk()->assertSee($shipment->lumora_tracking_id)
                ->assertViewHas('shipments', fn ($page) => $page->total() === 1);
        }
        foreach (['pending' => 1, 'in_transit' => 0, 'delivered' => 0] as $status => $count) {
            $this->get('/shipments?status='.$status)->assertOk()
                ->assertViewHas('shipments', fn ($page) => $page->total() === $count);
        }
        $this->get('/shipments/'.$shipment->id)->assertOk()->assertSee('09171234567')->assertSee('123 Rizal Street')->assertSee('123456789012');
        $this->get('/track/'.$shipment->lumora_tracking_id)->assertOk()->assertSee('Parcel received')->assertDontSee('09171234567');
        $this->get('/reports')->assertOk()->assertViewHas('statusTotals', fn ($totals) => $totals['pending'] === 1);
        $this->post('/sorting-center/receive', $this->payload(['barcode' => 'SECOND']))->assertSessionHasNoErrors();
        $this->assertSame('LM-TRK-000002', Shipment::latest('id')->first()->lumora_tracking_id);
    }

    public function test_duplicate_barcode_and_required_fields_do_not_create_partial_records(): void
    {
        $this->post('/sorting-center/receive', $this->payload())->assertSessionHasNoErrors();
        $this->post('/sorting-center/receive', $this->payload())->assertSessionHasErrors(['barcode' => 'This barcode is already registered to a shipment.']);
        $this->post('/sorting-center/receive', [])->assertSessionHasErrors(['recipient_name', 'recipient_phone', 'address_line', 'city', 'province']);
        $this->post('/sorting-center/receive', $this->payload(['barcode' => 'NEW', 'recipient_phone' => '123']))->assertSessionHasErrors('recipient_phone');
        $this->assertDatabaseCount('shipments', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('parcels', 1);
    }

    public function test_database_constraint_rejects_duplicate_even_without_form_validation(): void
    {
        $service = app(ParcelIntakeService::class);
        $service->receive($this->payload(), auth()->id());
        try {
            $service->receive($this->payload(), auth()->id());
            $this->fail('Duplicate barcode must fail at the database layer.');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            $this->assertDatabaseCount('shipments', 1);
            $this->assertDatabaseCount('orders', 1);
            $this->assertDatabaseCount('parcels', 1);
            $this->assertDatabaseCount('tracking_events', 1);
        }
    }

    public function test_scan_sort_and_delivery_keep_track_id_and_statuses_synchronized(): void
    {
        $storeId = Store::where('is_active', true)->value('id');
        $area = DestinationArea::create(['store_id' => $storeId, 'name' => 'Santa Cruz', 'code' => 'SC', 'cities' => ['Santa Cruz'], 'is_active' => true]);
        $this->post('/sorting-center/receive', $this->payload())->assertSessionHasNoErrors();
        $parcel = Parcel::sole();
        $trackId = $parcel->shipment->lumora_tracking_id;
        $this->post('/sorting-center/'.$parcel->id.'/scan', [])->assertSessionHasNoErrors();
        $this->assertSame($area->id, $parcel->fresh()->destination_area_id);
        $this->post('/sorting-center/'.$parcel->id.'/sort', ['destination_area_id' => $area->id])->assertSessionHasNoErrors();
        $this->assertSame('packed', $parcel->shipment->fresh()->status);
        $admin = auth()->user();
        $riderUser = User::factory()->create(['role' => 'rider']);
        $rider = \App\Models\RiderProfile::create(['user_id' => $riderUser->id, 'registration_status' => 'approved', 'availability' => 'available']);
        \App\Models\RiderAreaAssignment::create(['rider_profile_id' => $rider->id, 'destination_area_id' => $area->id, 'is_active' => true, 'is_primary' => true]);
        $this->post('/sorting-center/'.$parcel->id.'/assign', ['assigned_rider_id' => $rider->id])->assertSessionHasNoErrors();
        $this->actingAs($riderUser)->post('/parcels/'.$parcel->id.'/accept')->assertRedirect();
        $this->actingAs($admin);
        $this->assertSame('in_transit', $parcel->shipment->fresh()->status);
        $this->withHeader('X-Integration-Key', 'demo-logistics-key')
            ->postJson('/api/v1/shipments/'.$parcel->shipment_id.'/status', ['status' => 'delivered'])->assertOk();
        $this->assertSame('delivered', $parcel->fresh()->status);
        $this->assertSame($trackId, $parcel->shipment->fresh()->lumora_tracking_id);
        $this->get('/shipments?status=delivered')->assertOk()->assertSee($trackId);
        $this->get('/track/'.$trackId)->assertOk()->assertSee('Delivered');
    }

    public function test_area_override_and_international_phone_format_are_saved(): void
    {
        $area = DestinationArea::create(['store_id' => Store::where('is_active', true)->value('id'), 'name' => 'Override', 'code' => 'OV', 'is_active' => true]);
        $this->post('/sorting-center/receive', $this->payload(['destination_area_id' => $area->id, 'recipient_phone' => '+639171234567']))
            ->assertSessionHasNoErrors();
        $this->assertSame($area->id, Parcel::sole()->destination_area_id);
        $this->assertSame('+639171234567', Parcel::sole()->recipient_phone);
    }

    public function test_failure_at_last_write_rolls_back_the_entire_intake(): void
    {
        ParcelScan::creating(fn () => throw new \RuntimeException('Simulated write failure'));
        try {
            app(ParcelIntakeService::class)->receive($this->payload(), auth()->id());
            $this->fail('Expected simulated failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated write failure', $exception->getMessage());
            foreach (['orders', 'shipments', 'parcels', 'tracking_events', 'parcel_scans'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
        } finally {
            ParcelScan::flushEventListeners();
        }
    }
}