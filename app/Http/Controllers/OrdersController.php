<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdersController extends Controller
{
    public function index(Request $request): View
    {
        $store = Store::query()->where('is_active', true)->first();
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $base = Order::query()->when($store, fn ($query) => $query->where('store_id', $store->id));
        $orders = (clone $base)
            ->with(['items', 'shipments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('external_id', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('ordered_at')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'all' => (clone $base)->count(),
            'paid' => (clone $base)->where('status', 'paid')->count(),
            'processing' => (clone $base)->whereIn('status', ['processing', 'fulfilled'])->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        return view('orders.index', compact('orders', 'stats', 'status', 'search', 'store'));
    }

    public function show(Order $order): View
    {
        $store = Store::query()->where('is_active', true)->first();

        abort_unless(! $store || $order->store_id === $store->id, 404);

        $order->load(['items', 'shipments.trackingEvents', 'store']);

        return view('orders.show', compact('order'));
    }
}
