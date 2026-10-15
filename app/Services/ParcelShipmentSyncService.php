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
            $shipment = null;
            if ($parcel->shipment_id) {
                $shipment = Shipment::whereKey($parcel->shipment_id)->lockForUpdate()->first();
            }
            if (! $shipment && $parcel->parcel_number) {
                $shipment = Shipment::where('tracking_number', $parcel->parcel_number)->lockForUpdate()->first();
                if ($shipment) {
                    $parcel->updateQuietly(['shipment_id' => $shipment->id]);
                }
            }
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
                return;
            }

            $riderName = $parcel->rider?->user?->name;
            $location = implode(', ', array_filter([$parcel->city, $parcel->province]));
            $description = match ($status) {
                'out_for_delivery' => $riderName ? "Parcel is out for delivery with courier {$riderName}." : 'Parcel is out for final delivery.',
                'delivered' => $riderName ? "Parcel was successfully delivered by courier {$riderName}." : 'Parcel has been delivered.',
                'in_transit' => $riderName ? "Package picked up by courier {$riderName} and is in transit." : 'Parcel is in transit.',
                default => ShipmentStatusService::description($status),
            };

            if ($shipment->status !== $status) {
                app(ShipmentStatusService::class)->transition($shipment, [
                    'status' => $status,
                    'label' => ShipmentStatusService::label($status),
                    'description' => $description,
                    'location' => $location,
                ], 'rider_app');

                if ($status === 'delivered' && ! $shipment->delivered_at) {
                    $shipment->updateQuietly(['delivered_at' => now()]);
                }
                return;
            }

            // If already at status, record event if not logged yet
            $hasRecentEvent = $shipment->trackingEvents()
                ->where('status', $status)
                ->where('occurred_at', '>=', now()->subMinutes(5))
                ->exists();

            if (! $hasRecentEvent) {
                $shipment->trackingEvents()->create([
                    'status' => $status,
                    'label' => ShipmentStatusService::label($status),
                    'description' => $description,
                    'location' => $location,
                    'occurred_at' => now(),
                    'source' => 'rider_app',
                ]);
            }
        });
    }
}