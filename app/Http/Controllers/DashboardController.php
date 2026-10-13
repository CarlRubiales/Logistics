<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('stores')) {
            return view('welcome', [
                'metrics' => ['orders_today' => 128, 'to_be_packed' => 42, 'in_transit' => 76, 'exceptions' => 4],
                'recentShipments' => collect(),
                'dynamicDashboard' => false,
            ]);
        }

        $store = Store::query()->where('is_active', true)->first();
        $orders = $store ? $store->orders() : Order::query()->whereRaw('1 = 0');
        $shipments = $store
            ? Shipment::query()->whereHas('order', fn ($query) => $query->where('store_id', $store->id))
            : Shipment::query()->whereRaw('1 = 0');

        $metrics = [
            'orders_today' => (clone $orders)->whereDate('ordered_at', today())->count(),
            'to_be_packed' => (clone $shipments)->whereIn('status', ['pending', 'packed'])->count(),
            'in_transit' => (clone $shipments)->where('status', 'in_transit')->count(),
            'exceptions' => (clone $shipments)->where('status', 'exception')->count(),
        ];

        return view('welcome', [
            'metrics' => $metrics,
            'recentShipments' => (clone $shipments)->with('order')->latest()->limit(4)->get(),
            'dynamicDashboard' => true,
        ]);
    }

    public function storeShipment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'string', 'max:120'],
            'weight' => ['required', 'numeric', 'min:0'],
            'carrier' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::query()
            ->where('external_id', $data['order'])
            ->orWhere('order_number', $data['order'])
            ->first();

        if (! $order) {
            return back()->withErrors(['order' => 'We could not find that order in the connected storefront.'])->withInput();
        }

        $shipment = $order->shipments()->create([
            'carrier' => $data['carrier'],
            'service_level' => 'Standard',
            'status' => 'pending',
            'weight' => $data['weight'],
            'destination_city' => data_get($order->shipping_address, 'city'),
            'destination_state' => data_get($order->shipping_address, 'state'),
            'destination_country' => data_get($order->shipping_address, 'country', 'US'),
            'exception_note' => $data['note'],
        ]);

        $shipment->trackingEvents()->create([
            'status' => 'pending',
            'label' => 'Shipment draft created',
            'description' => 'Draft created from the operations console.',
            'occurred_at' => now(),
            'source' => 'portal',
        ]);

        return back()->with('status', "Shipment draft {$shipment->id} created.");
    }
}
