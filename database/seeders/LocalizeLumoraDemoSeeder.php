<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizeLumoraDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Match both the demo store and explicit seed provenance; never reseed accounts or statuses.
        $destinations = [
            ['Makati City', 'Metro Manila', '1200'],
            ['Taguig City', 'Metro Manila', '1630'],
            ['Pasig City', 'Metro Manila', '1600'],
            ['Quezon City', 'Metro Manila', '1100'],
            ['Manila', 'Metro Manila', '1000'],
            ['Mandaluyong City', 'Metro Manila', '1550'],
            ['Calamba City', 'Laguna', '4027'],
            ['Antipolo City', 'Rizal', '1870'],
            ['Batangas City', 'Batangas', '4200'],
            ['Santa Rosa City', 'Laguna', '4026'],
            ['Imus City', 'Cavite', '4103'],
            ['Bacoor City', 'Cavite', '4102'],
        ];
        $carriers = ['Lumora Express', 'J&T Express', 'LBC Express'];
        $updated = 0;

        DB::transaction(function () use ($destinations, $carriers, &$updated) {
            foreach ($destinations as $index => [$city, $province, $postalCode]) {
                $order = Order::where('external_id', 'LM-'.(2187 - $index))
                    ->whereHas('store', fn ($query) => $query->where('external_store_id', 'lumora-demo'))
                    ->first();
                if (! $order || ($order->metadata['source'] ?? null) !== 'lumora-demo-seeder') {
                    continue;
                }
                $order->timestamps = false;
                $order->update(['shipping_address' => array_merge($order->shipping_address ?? [], [
                    'city' => $city, 'state' => $province, 'postal_code' => $postalCode, 'country' => 'PH',
                ])]);
                $shipment = $order->shipments()->where('external_id', 'SH-240912-0'.(81 - $index))->first();
                if (! $shipment) {
                    continue;
                }
                $shipment->timestamps = false;
                $shipment->update([
                    'destination_city' => $city, 'destination_state' => $province,
                    'destination_country' => 'PH',
                    'carrier' => in_array($shipment->carrier, ['DHL Express', 'UPS Ground', 'FedEx Home'], true)
                        ? $carriers[$index % count($carriers)] : $shipment->carrier,
                ]);
                foreach ($shipment->trackingEvents()->where('source', 'demo')->get() as $event) {
                    $event->timestamps = false;
                    $event->update(['location' => "{$city}, {$province}"]);
                }
                $updated++;
            }
        });
        $this->command?->info("Localized {$updated} demo shipments; real shipments and tracking numbers were preserved.");
    }
}