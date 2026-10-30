<?php

namespace App\Http\Controllers;

use App\Models\FulfillmentLocation;
use App\Models\InventoryItem;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $store = Store::query()->where('is_active', true)->first();
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $locations = $store?->locations()->where('is_active', true)->orderBy('name')->get() ?? collect();

        if (! Schema::hasTable('inventory_items')) {
            return view('inventory.index', [
                'items' => new LengthAwarePaginator(collect(), 0, 12, 1, ['path' => route('inventory.index')]),
                'stats' => ['all' => 0, 'available' => 0, 'low' => 0, 'out' => 0],
                'search' => $search,
                'status' => $status,
                'locations' => $locations,
                'store' => $store,
                'inventoryReady' => false,
            ]);
        }

        $base = InventoryItem::query()->when($store, fn ($query) => $query->where('store_id', $store->id));
        $items = (clone $base)
            ->with('fulfillmentLocation')
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('sku', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('inventory.index', [
            'items' => $items,
            'stats' => [
                'all' => (clone $base)->count(),
                'available' => (clone $base)->where('status', 'in_stock')->count(),
                'low' => (clone $base)->where('status', 'low_stock')->count(),
                'out' => (clone $base)->where('status', 'out_of_stock')->count(),
            ],
            'search' => $search,
            'status' => $status,
            'locations' => $locations,
            'store' => $store,
            'inventoryReady' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = Store::query()->where('is_active', true)->firstOrFail();
        $data = $request->validate([
            'fulfillment_location_id' => ['nullable', 'integer', Rule::exists('fulfillment_locations', 'id')->where(fn ($query) => $query->where('store_id', $store->id))],
            'sku' => ['required', 'string', 'max:100', Rule::unique('inventory_items', 'sku')->where(fn ($query) => $query->where('store_id', $store->id))],
            'name' => ['required', 'string', 'max:160'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'quantity_reserved' => ['nullable', 'integer', 'min:0', 'lte:quantity_on_hand'],
            'reorder_point' => ['required', 'integer', 'min:0'],
        ]);

        $data['store_id'] = $store->id;
        $data['quantity_reserved'] = $data['quantity_reserved'] ?? 0;
        $data['status'] = $this->statusFor($data['quantity_on_hand'], $data['quantity_reserved'], $data['reorder_point']);
        InventoryItem::create($data);

        return back()->with('status', 'Inventory item added successfully.');
    }

    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $data = $request->validate([
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'quantity_reserved' => ['required', 'integer', 'min:0', 'lte:quantity_on_hand'],
            'reorder_point' => ['required', 'integer', 'min:0'],
        ]);

        $data['status'] = $this->statusFor($data['quantity_on_hand'], $data['quantity_reserved'], $data['reorder_point']);
        $inventoryItem->update($data);

        return back()->with('status', 'Inventory quantities updated.');
    }

    private function statusFor(int $onHand, int $reserved, int $reorderPoint): string
    {
        $available = max(0, $onHand - $reserved);
        return $available === 0 ? 'out_of_stock' : ($available <= $reorderPoint ? 'low_stock' : 'in_stock');
    }
}
