<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Parcel;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class OrderSyncService
{
    public function upsertFromWebhook(array $payload): ?Order
    {
        $order = data_get($payload, 'data.order', data_get($payload, 'order', $payload));
        $store = Store::where('is_active', true)->first();
        if (! $store || ! data_get($order, 'id')) {
            return null;
        }
        return DB::transaction(function () use ($order, $store) {
            $record = Order::updateOrCreate(['store_id' => $store->id, 'external_id' => (string) data_get($order, 'id')], [
                'order_number' => data_get($order, 'number'), 'customer_name' => data_get($order, 'customer.name', 'Store customer'),
                'customer_email' => data_get($order, 'customer.email'), 'shipping_address' => data_get($order, 'shipping_address'),
                'status' => data_get($order, 'status', 'paid'), 'total' => data_get($order, 'total', 0),
                'ordered_at' => data_get($order, 'created_at'), 'metadata' => $order,
            ]);
            if (is_array(data_get($order, 'items'))) {
                $record->items()->delete();
                $record->items()->createMany(collect(data_get($order, 'items'))->map(fn ($item) => [
                    'external_id' => data_get($item, 'id'), 'sku' => data_get($item, 'sku'),
                    'name' => data_get($item, 'name', 'Store item'), 'quantity' => data_get($item, 'quantity', 1),
                    'unit_price' => data_get($item, 'unit_price', 0), 'metadata' => $item,
                ])->all());
            }
            $address = data_get($order, 'shipping_address', []);
            $shipment = $record->shipments()->updateOrCreate(
                ['external_id' => (string) data_get($order, 'shipment.id', 'SHIP-'.data_get($order, 'id'))],
                [
                    'tracking_number' => data_get($order, 'shipment.tracking_number'),
                    'destination_city' => data_get($address, 'city'),
                    'destination_state' => data_get($address, 'state', data_get($address, 'province')),
                    'destination_country' => data_get($address, 'country', 'PH'),
                    'metadata' => data_get($order, 'shipment', []),
                ],
            );
            // Existing records retain their lifecycle status when storefront data is refreshed.
            Parcel::updateOrCreate(
                ['parcel_number' => (string) data_get($order, 'number', '#'.data_get($order, 'id'))],
                [
                    'order_id' => $record->id, 'shipment_id' => $shipment->id, 'store_id' => $store->id,
                    'barcode' => data_get($order, 'shipment.tracking_number'),
                    'recipient_name' => data_get($order, 'customer.name', 'Store customer'),
                    'recipient_phone' => data_get($address, 'phone'),
                    'address_line' => data_get($address, 'line1', data_get($address, 'address1')),
                    'city' => data_get($address, 'city'), 'province' => data_get($address, 'province', data_get($address, 'state')),
                    'postal_code' => data_get($address, 'postal_code', data_get($address, 'zip')),
                    'received_at' => now(), 'metadata' => ['source' => 'ecommerce_sync'],
                ],
            );
            return $record->load(['items', 'shipments']);
        });
    }
}