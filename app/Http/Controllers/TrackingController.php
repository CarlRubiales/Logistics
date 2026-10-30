<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(Request $request, ?string $trackingNumber = null): View|JsonResponse
    {
        $trackingNumber = trim((string) ($trackingNumber ?? $request->query('tracking', '')));
        $shipment = null;
        $parcel = null;
        $notFound = false;

        if ($trackingNumber !== '') {
            $shipment = Shipment::query()
                ->with(['order', 'trackingEvents', 'parcels.rider.user', 'parcels.scans'])
                ->where(function ($query) use ($trackingNumber) {
                    $query->where('tracking_number', $trackingNumber);
                    if (preg_match('/^LM-TRK-0*(\d+)$/i', $trackingNumber, $matches)) {
                        $query->orWhere('id', $matches[1]);
                    }
                })
                ->first();

            if (! $shipment) {
                // Check if search matches parcel barcode or parcel number
                $parcel = Parcel::with(['shipment.order', 'shipment.trackingEvents', 'rider.user', 'scans'])
                    ->where('barcode', $trackingNumber)
                    ->orWhere('parcel_number', $trackingNumber)
                    ->first();

                if ($parcel && $parcel->shipment) {
                    $shipment = $parcel->shipment;
                }
            } else {
                $parcel = $shipment->parcels->first();
            }

            $notFound = ($shipment === null && $parcel === null);
        }

        $effectiveStatus = $shipment?->status ?? $parcel?->status ?? 'pending';
        $currentStage = ($shipment || $parcel) ? $this->stageFor($effectiveStatus) : 0;
        $stages = [
            ['key' => 'pending', 'label' => 'Order Received'],
            ['key' => 'packed', 'label' => 'Sorting Center'],
            ['key' => 'in_transit', 'label' => 'In Transit'],
            ['key' => 'out_for_delivery', 'label' => 'Out for Delivery'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'tracking_number' => $trackingNumber,
                'found' => ! $notFound,
                'effective_status' => $effectiveStatus,
                'status_label' => ucwords(str_replace('_', ' ', $effectiveStatus)),
                'current_stage' => $currentStage,
                'is_delivered' => $effectiveStatus === 'delivered',
                'is_out_for_delivery' => $effectiveStatus === 'out_for_delivery',
                'courier_name' => ($parcel?->rider?->user?->name) ?: ($shipment?->parcels?->first()?->rider?->user?->name),
                'courier_vehicle' => ($parcel?->rider?->vehicle_type) ?: ($shipment?->parcels?->first()?->rider?->vehicle_type ?? 'Motorcycle Fleet'),
                'destination' => $parcel ? ($parcel->city . ', ' . $parcel->province) : ($shipment ? ($shipment->destination_city . ', ' . $shipment->destination_state) : ''),
                'events' => ($shipment?->trackingEvents ?? collect())->map(fn ($e) => [
                    'label' => $e->label,
                    'description' => $e->description,
                    'location' => $e->location,
                    'time' => $e->occurred_at?->format('M j, Y g:i A'),
                    'diff' => $e->occurred_at?->diffForHumans(),
                ]),
                'scans' => ($parcel?->scans ?? collect())->map(fn ($s) => [
                    'scan_type' => ucwords(str_replace('_', ' ', $s->scan_type)),
                    'note' => $s->note,
                    'time' => $s->scanned_at?->format('M j, Y g:i A'),
                    'diff' => $s->scanned_at?->diffForHumans(),
                ]),
            ]);
        }

        return view('tracking.index', compact('shipment', 'parcel', 'trackingNumber', 'notFound', 'currentStage', 'stages'));
    }

    private function stageFor(?string $status): int
    {
        return match ($status) {
            'delivered' => 4,
            'out_for_delivery' => 3,
            'in_transit', 'rider_received' => 2,
            'packed', 'sorted', 'assigned', 'scanned' => 1,
            'pending', 'received' => 0,
            default => 0,
        };
    }
}
