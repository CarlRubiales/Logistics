<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ShipmentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Shipment::with('order:id,external_id,order_number,customer_name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('carrier'), fn ($q) => $q->where('carrier', $request->string('carrier')))
            ->latest()->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return response()->json($shipment->load(['order.items', 'trackingEvents', 'fulfillmentLocation']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'fulfillment_location_id' => ['nullable', 'integer', 'exists:fulfillment_locations,id'],
            'external_id' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:160'],
            'carrier' => ['nullable', 'string', 'max:80'],
            'service_level' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_merge(array_keys(ShipmentStatusService::STAGES), ShipmentStatusService::EXCEPTIONS))],
            'destination_city' => ['nullable', 'string', 'max:100'],
            'destination_state' => ['nullable', 'string', 'max:100'],
            'destination_country' => ['nullable', 'string', 'size:2'],
            'weight' => ['nullable', 'numeric', 'min:0'], 'metadata' => ['nullable', 'array'],
        ]);
        $shipment = DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'pending';
            $shipment = Shipment::create($data);
            $shipment->trackingEvents()->create([
                'status' => $shipment->status, 'label' => ShipmentStatusService::label($shipment->status),
                'description' => 'Shipment created in Lumora Logistics.', 'occurred_at' => now(), 'source' => 'portal',
            ]);
            return $shipment->load('trackingEvents');
        });
        return response()->json(['data' => $shipment], 201);
    }

    public function updateStatus(Request $request, Shipment $shipment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_merge(array_keys(ShipmentStatusService::STAGES), ShipmentStatusService::EXCEPTIONS))],
            'description' => ['nullable', 'string', 'max:500'], 'location' => ['nullable', 'string', 'max:160'],
            'exception_type' => ['nullable', 'string', 'max:120'], 'exception_note' => ['nullable', 'string', 'max:1000'],
        ]);
        return response()->json(['data' => app(ShipmentStatusService::class)->transition($shipment, $data)->load('trackingEvents')]);
    }
}