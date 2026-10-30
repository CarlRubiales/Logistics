<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_orders(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_admin_can_search_orders_and_open_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::create(['name' => 'Lumora Beauty', 'platform' => 'custom', 'is_active' => true]);
        $order = Order::create([
            'store_id' => $store->id,
            'external_id' => 'ext-1001',
            'order_number' => 'LM-1001',
            'customer_name' => 'Alice Tan',
            'customer_email' => 'alice@example.com',
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'Manila', 'state' => 'NCR', 'postal_code' => '1000', 'country' => 'PH'],
            'status' => 'paid',
            'total' => 1299.00,
            'ordered_at' => now(),
        ]);
        $order->items()->create(['name' => 'Glow Serum', 'sku' => 'SERUM-01', 'quantity' => 2, 'unit_price' => 649.50]);

        $this->actingAs($user)
            ->get('/orders?search=LM-1001')
            ->assertOk()
            ->assertSee('LM-1001')
            ->assertSee('Alice Tan');

        $this->actingAs($user)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('Order items')
            ->assertSee('Alice Tan')
            ->assertSee('Glow Serum')
            ->assertSee('Manila');
    }

    public function test_status_filter_excludes_other_orders(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::create(['name' => 'Lumora Beauty', 'platform' => 'custom', 'is_active' => true]);
        Order::create(['store_id' => $store->id, 'external_id' => 'paid-1', 'order_number' => 'LM-PAID', 'customer_name' => 'Paid Customer', 'status' => 'paid', 'total' => 10, 'ordered_at' => now()]);
        Order::create(['store_id' => $store->id, 'external_id' => 'cancelled-1', 'order_number' => 'LM-CANCELLED', 'customer_name' => 'Cancelled Customer', 'status' => 'cancelled', 'total' => 10, 'ordered_at' => now()]);

        $this->actingAs($user)->get('/orders?status=paid')->assertOk()->assertSee('LM-PAID')->assertDontSee('LM-CANCELLED');
    }
}
