<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Parcel;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParcelIntakeService
{
    public function receive(array $data, int $userId): Parcel
    {
        return DB::transaction(function () use ($data, $userId) {
            $store = Store::where('is_active', true)->first();
            if (! $store) {
                throw ValidationException::withMessages(['recipient_name' => 'An active workspace is required before receiving parcels.']);
            }
            $address = [
                'line1' => $data['address_line'], 'city' => $data['city'],
                'state' => $data['province'], 'postal_code' => $data['postal_code'] ?? null,
                'country' => 'PH', 'phone' => $data['recipient_phone'],
            ];
            // An internal order reference, never used as the visible Track ID.
            $order = Order::create([
                'store_id' => $store->id, 'external_id' => 'intake-'.Str::uuid(),
                'customer_name' => $data['recipient_name'], 'shipping_address' => $address,
                'status' => 'pending', 'total' => 0, 'ordered_at' => now(),
                'metadata' => ['source' => 'parcel_intake'],
            ]);
            $shipment = $order->shipments()->create([
                'carrier' => 'Lumora Express', 'status' => 'pending',
                'destination_city' => $data['city'], 'destination_state' => $data['province'],
                'destination_country' => 'PH', 'metadata' => ['source' => 'parcel_intake'],
            ]);
            // The database primary key allocates the sequence safely across concurrent admins.
            $shipment->update(['tracking_number' => $shipment->lumora_tracking_id]);
            $parcel = Parcel::create(array_merge($data, [
                'store_id' => $store->id, 'order_id' => $order->id, 'shipment_id' => $shipment->id,
                'parcel_number' => $shipment->lumora_tracking_id,
                'status' => 'received', 'received_at' => now(),
                'metadata' => ['source' => 'parcel_intake'],
            ]));
            $parcel->scans()->create([
                'user_id' => $userId, 'scan_type' => 'receive',
                'barcode' => $parcel->barcode, 'scanned_at' => now(),
            ]);
            return $parcel;
        }, 3);
    }
}