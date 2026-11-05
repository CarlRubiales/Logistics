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

            $totalAmount = (float) ($data['total'] ?? $data['cod_amount'] ?? 0);
            $orderMetadata = array_merge(['source' => 'parcel_intake'], $data['metadata'] ?? []);

            // An internal order reference, never used as the visible Track ID.
            $order = Order::create([
                'store_id' => $store->id,
                'external_id' => $data['external_id'] ?? ('intake-'.Str::uuid()),
                'order_number' => $data['order_number'] ?? null,
                'customer_name' => $data['recipient_name'],
                'shipping_address' => $address,
                'status' => 'pending',
                'total' => $totalAmount,
                'ordered_at' => now(),
                'metadata' => $orderMetadata,
            ]);

            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $idx => $item) {
                    $order->items()->create([
                        'name' => $item['name'] ?? $item['product_name'] ?? 'Order Item',
                        'sku' => $item['sku'] ?? ('LM-SKU-' . ($item['product_id'] ?? ($idx + 1))),
                        'quantity' => (int) ($item['qty'] ?? $item['quantity'] ?? 1),
                        'unit_price' => (float) ($item['unit_price'] ?? 0),
                    ]);
                }
            }

            $shipment = $order->shipments()->create([
                'carrier' => 'Lumora Express', 'status' => 'pending',
                'destination_city' => $data['city'], 'destination_state' => $data['province'],
                'destination_country' => 'PH', 'metadata' => $orderMetadata,
            ]);

            // The database primary key allocates the sequence safely across concurrent admins.
            $shipment->update(['tracking_number' => $shipment->lumora_tracking_id]);

            $parcelAttributes = collect($data)->only([
                'destination_area_id', 'branch', 'assigned_rider_id', 'barcode',
                'recipient_name', 'recipient_phone', 'address_line', 'city',
                'province', 'postal_code', 'sort_bin',
            ])->all();

            $parcel = Parcel::create(array_merge($parcelAttributes, [
                'store_id' => $store->id, 'order_id' => $order->id, 'shipment_id' => $shipment->id,
                'parcel_number' => $shipment->lumora_tracking_id,
                'status' => 'received', 'received_at' => now(),
                'metadata' => $orderMetadata,
            ]));

            $parcel->scans()->create([
                'user_id' => $userId, 'scan_type' => 'receive',
                'barcode' => $parcel->barcode, 'scanned_at' => now(),
            ]);

            return $parcel;
        }, 3);
    }
}
