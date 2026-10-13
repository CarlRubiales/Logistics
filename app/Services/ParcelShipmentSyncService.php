<?php

namespace App\Services;

use App\Models\Parcel;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParcelShipmentSyncService
{
    public function sync(Parcel $parcel): void
    {
        DB::transaction(function () use ($parcel) {
            $shipment = Shipment::whereKey($parcel->shipment_id)->lockForUpdate()->first();
            if (! $shipment) {
                return;
            }
            $status = match ($parcel->status) {
                'received', 'scanned' => 'pending',
                'sorted', 'assigned' => 'packed',
                'rider_received', 'in_transit' => 'in_transit',
                'out_for_delivery' => 'out_for_delivery',
                'delivered' => 'delivered',
                'exception', 'failed' => 'exception',
                'cancelled' => 'cancelled',
                default => null,
            };
            if (! $status) {
                return;
            }
            if (! ShipmentStatusService::allowed($shipment, $shipment->status, $status)) {
                throw ValidationException::withMessages(['status' => 'The shipment has already progressed beyond this sorting step.']);
            }
            if ($shipment->status !== $status) {
                app(ShipmentStatusService::class)->transition($shipment, ['status' => $status], 'sorting_center');
                return;
            }
            // A real scan/assignment can occur without changing the shipment stage.
            $shipment->trackingEvents()->create([
                'status' => $status,
                'label' => match ($parcel->status) {
                    'received' => 'Parcel received',
                    'scanned' => 'Scanned at sorting center',
                    'sorted' => 'Sorted for delivery',
                    'assigned' => 'Assigned to rider',
                    'rider_received', 'in_transit' => 'In transit',
                    default => ShipmentStatusService::label($status),
                },
                'description' => ShipmentStatusService::description($status),
                'location' => implode(', ', array_filter([$parcel->city, $parcel->province])),
                'occurred_at' => now(), 'source' => 'sorting_center',
            ]);
        });
    }
}