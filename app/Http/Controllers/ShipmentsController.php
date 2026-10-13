<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Store;
use App\Services\ShipmentStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentsController extends Controller
{
    public function index(Request $request): View
    {
        $store = Store::where('is_active', true)->first();
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $base = Shipment::query()->when($store, fn ($query) => $query->whereHas('order', fn ($q) => $q->where('store_id', $store->id)));
        $shipments = (clone $base)->with(['order', 'trackingEvents', 'fulfillmentLocation'])
            ->when($status, fn ($q) => $q->whereIn('status', match ($status) {
                'pending' => ['pending', 'packed'], 'in_transit' => ['in_transit', 'out_for_delivery'], default => [$status],
            }))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    if (str_contains('lumora express', strtolower($search))) {
                        $nested->orWhereRaw('1 = 1');
                    }
                    if (str_contains($search, ',')) {
                        [$city, $province] = array_map('trim', explode(',', $search, 2));
                        $nested->orWhere(fn ($q) => $q->where('destination_city', 'like', "%{$city}%")->where('destination_state', 'like', "%{$province}%"));
                    }
                    if (preg_match('/^LM-TRK-0*(\d+)$/i', $search, $matches)) {
                        $nested->orWhere('id', $matches[1]);
                    }
                    $nested->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('external_id', 'like', "%{$search}%")->orWhere('carrier', 'like', "%{$search}%")
                        ->orWhere('destination_city', 'like', "%{$search}%")->orWhere('destination_state', 'like', "%{$search}%")
                        ->orWhereHas('parcels', fn ($q) => $q->where('barcode', 'like', "%{$search}%")->orWhere('recipient_phone', 'like', "%{$search}%"))
                        ->orWhereHas('order', fn ($q) => $q->where('customer_name', 'like', "%{$search}%"));
                });
            })->orderByDesc('created_at')->orderByDesc('id')->paginate(12)->withQueryString();
        $stats = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->whereIn('status', ['pending', 'packed'])->count(),
            'in_transit' => (clone $base)->whereIn('status', ['in_transit', 'out_for_delivery'])->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
            'exceptions' => (clone $base)->whereIn('status', ['exception', 'failed'])->count(),
        ];
        return view('shipments.index', compact('shipments', 'stats', 'status', 'search', 'store'));
    }

    public function show(Shipment $shipment): View
    {
        $this->authorizeStore($shipment);
        $shipment->load(['order.items', 'trackingEvents', 'fulfillmentLocation', 'parcels.area']);
        $stages = ShipmentStatusService::STAGES;
        $currentStage = array_search($shipment->status, array_keys($stages), true);
        $allowedStatuses = collect($stages + array_combine(ShipmentStatusService::EXCEPTIONS, ['Exception', 'Failed Delivery', 'Cancelled']))
            ->filter(fn ($label, $status) => $status !== $shipment->status && ShipmentStatusService::allowed($shipment, $shipment->status, $status));
        return view('shipments.show', compact('shipment', 'stages', 'currentStage', 'allowedStatuses'));
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'operations_manager', 'warehouse_staff'], true), 403);
        $this->authorizeStore($shipment);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_merge(array_keys(ShipmentStatusService::STAGES), ShipmentStatusService::EXCEPTIONS))],
            'location' => ['nullable', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:500'],
        ]);
        app(ShipmentStatusService::class)->transition($shipment, $data);
        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated successfully.');
    }

    private function authorizeStore(Shipment $shipment): void
    {
        $store = Store::where('is_active', true)->first();
        abort_unless(! $store || $shipment->order?->store_id === $store->id, 404);
    }
}