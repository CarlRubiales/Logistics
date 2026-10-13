<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items', 'shipments.trackingEvents'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($nested) use ($search) {
                    $nested->where('external_id', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->latest('ordered_at')
            ->paginate(min($request->integer('per_page', 25), 100));

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['items', 'shipments.trackingEvents', 'store']));
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'external_id' => ['required', 'string', 'max:120'],
            'order_number' => ['nullable', 'string', 'max:120'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_email' => ['nullable', 'email', 'max:320'],
            'shipping_address' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:40'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'ordered_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.external_id' => ['nullable', 'string', 'max:120'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $store = $data['store_id'] ?? Store::query()->where('is_active', true)->first()?->id;
        if (! $store) {
            return response()->json(['message' => 'No active store is configured.'], 422);
        }

        $order = DB::transaction(function () use ($data, $store) {
            $order = Order::updateOrCreate(
                ['store_id' => $store, 'external_id' => $data['external_id']],
                collect($data)->except('items')->merge(['store_id' => $store])->all(),
            );

            if (array_key_exists('items', $data)) {
                $order->items()->delete();
                $order->items()->createMany($data['items']);
            }

            return $order->load('items');
        });

        return response()->json(['data' => $order, 'created' => $order->wasRecentlyCreated], $order->wasRecentlyCreated ? 201 : 200);
    }
}
