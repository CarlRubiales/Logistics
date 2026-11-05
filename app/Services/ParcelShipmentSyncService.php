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
                if (! $shipment && preg_match('/LM-TRK-0*(\d+)/i', $parcel->parcel_number, $matches)) {
                    $shipment = Shipment::whereKey((int) $matches[1])->lockForUpdate()->first();
                }
            }
            if (! $shipment && $parcel->barcode) {
                $shipment = Shipment::where('tracking_number', $parcel->barcode)->lockForUpdate()->first();
                if (! $shipment && preg_match('/LM-TRK-0*(\d+)/i', $parcel->barcode, $matches)) {
                    $shipment = Shipment::whereKey((int) $matches[1])->lockForUpdate()->first();
                }
            }
            if ($shipment) {
                if ($parcel->shipment_id !== $shipment->id) {
                    $parcel->updateQuietly(['shipment_id' => $shipment->id]);
                }
                if ($shipment->order_id && ! $parcel->order_id) {
                    $parcel->updateQuietly(['order_id' => $shipment->order_id]);
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

            // Sync parent order status
            $order = $shipment->order ?: $parcel->order;
            if ($order) {
                $orderStatus = match ($status) {
                    'delivered' => 'delivered',
                    'in_transit', 'out_for_delivery', 'packed' => 'processing',
                    'cancelled' => 'cancelled',
                    default => null,
                };
                if ($orderStatus && $order->status !== $orderStatus) {
                    $order->updateQuietly(['status' => $orderStatus]);
                }
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

        // Push status update back to live Lumora E-Commerce website if linked to a waybill
        $waybillCode = $parcel->metadata['waybill_order_id'] ?? $parcel->barcode;
        $parcelStatus = $parcel->status;
        $branchName = $parcel->branch ?: 'Main';
        $riderName = $parcel->rider?->user?->name;

        if ($waybillCode && in_array($parcelStatus, ['in_transit', 'out_for_delivery', 'delivered', 'cancelled'], true)) {
            app()->terminating(function () use ($waybillCode, $parcelStatus, $branchName, $riderName) {
                $ecommerceBaseUrl = rtrim((string) env('LUMORA_ECOMMERCE_URL', 'https://lumora.party'), '/');
                try {
                    \Illuminate\Support\Facades\Http::timeout(3)
                        ->acceptJson()
                        ->post("{$ecommerceBaseUrl}/api/logistics/waybill/" . urlencode((string) $waybillCode) . '/status', [
                            'status' => $parcelStatus,
                            'hub' => $branchName . ' Hub',
                            'rider_name' => $riderName,
                            'note' => "Parcel status updated to {$parcelStatus} via Lumora Logistics",
                        ]);
                } catch (\Throwable $e) {
                    // Non-blocking
                }
            });
        }
    }
}