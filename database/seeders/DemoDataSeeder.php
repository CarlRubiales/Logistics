<?php

namespace Database\Seeders;

use App\Models\DestinationArea;
use App\Models\FulfillmentLocation;
use App\Models\Order;
use App\Models\Parcel;
use App\Models\RiderAreaAssignment;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::updateOrCreate(
            ['external_store_id' => 'lumora-demo'],
            [
                'name' => 'Lumora Logistics',
                'platform' => 'custom',
                'api_base_url' => 'https://store.example.test/api',
                'is_active' => true,
            ],
        );

        $location = FulfillmentLocation::updateOrCreate(
            ['store_id' => $store->id, 'code' => 'NYC-01'],
            [
                'name' => 'Makati Fulfillment Studio',
                'city' => 'Makati City',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'country' => 'PH',
                'is_active' => true,
            ],
        );

        $customers = [
            ['Sophia Chen', 'Makati City', 'Metro Manila'],
            ['James Robinson', 'Taguig City', 'Metro Manila'],
            ['Olivia Valdez', 'Pasig City', 'Metro Manila'],
            ['Noah Kim', 'Quezon City', 'Metro Manila'],
            ['Maya Patel', 'Manila', 'Metro Manila'],
            ['Evelyn Hart', 'Mandaluyong City', 'Metro Manila'],
            ['Liam Brooks', 'Calamba City', 'Laguna'],
            ['Amara Williams', 'Antipolo City', 'Rizal'],
            ['Elena Rossi', 'Batangas City', 'Batangas'],
            ['Aiden Smith', 'Santa Rosa City', 'Laguna'],
            ['Nora Anderson', 'Imus City', 'Cavite'],
            ['Theo Martin', 'Bacoor City', 'Cavite'],
        ];
        $statuses = ['in_transit', 'packed', 'in_transit', 'exception', 'delivered', 'in_transit', 'pending', 'in_transit', 'delivered', 'in_transit', 'packed', 'in_transit'];
        $carriers = ['Lumora Express', 'J&T Express', 'LBC Express'];

        $areas = collect([
            ['name' => 'Area A — Santa Cruz', 'code' => 'LAG-A', 'cities' => ['Santa Cruz']],
            ['name' => 'Area B — Pagsanjan', 'code' => 'LAG-B', 'cities' => ['Pagsanjan']],
            ['name' => 'Area C — Los Baños', 'code' => 'LAG-C', 'cities' => ['Los Baños']],
        ])->mapWithKeys(fn ($area) => [$area['code'] => DestinationArea::updateOrCreate(['store_id' => $store->id, 'code' => $area['code']], $area + ['is_active' => true])]);

        foreach ([['Rider 01', 'rider01@example.com', 'LAG-A'], ['Rider 02', 'rider02@example.com', 'LAG-B'], ['Rider 03', 'rider03@example.com', 'LAG-C']] as [$name, $email, $areaCode]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => 'rider12345', 'role' => 'rider']);
            $rider = RiderProfile::updateOrCreate(['user_id' => $user->id], ['phone' => '09'.random_int(100000000, 999999999), 'vehicle_type' => 'Motorcycle', 'registration_status' => 'approved', 'availability' => 'available', 'approved_at' => now()]);
            RiderAreaAssignment::updateOrCreate(['rider_profile_id' => $rider->id, 'destination_area_id' => $areas[$areaCode]->id], ['is_primary' => true, 'is_active' => true]);
        }

        foreach ($customers as $index => [$name, $city, $state]) {
            $number = 2187 - $index;
            $order = Order::updateOrCreate(
                ['store_id' => $store->id, 'external_id' => "LM-{$number}"],
                [
                    'order_number' => "LM-{$number}",
                    'customer_name' => $name,
                    'customer_email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                    'shipping_address' => ['line1' => 'Sample delivery address', 'city' => $city, 'state' => $state, 'postal_code' => '1200', 'country' => 'PH'],
                    'status' => 'paid',
                    'total' => 48.00 + ($index * 7.5),
                    'ordered_at' => now()->subMinutes($index * 23),
                    'metadata' => ['source' => 'lumora-demo-seeder'],
                ],
            );

            $order->items()->updateOrCreate(
                ['sku' => 'LUM-SERUM-01'],
                ['name' => 'Velvet Glow Serum', 'quantity' => 1 + ($index % 2), 'quantity_fulfilled' => 0, 'unit_price' => 48.00],
            );

            $status = $statuses[$index];
            $shipment = $order->shipments()->updateOrCreate(
                ['external_id' => "SH-240912-0" . (81 - $index)],
                [
                    'fulfillment_location_id' => $location->id,
                    'tracking_number' => $status === 'pending' ? null : "TRK{$number}LUMORA",
                    'carrier' => $carriers[$index % count($carriers)],
                    'service_level' => $index % 2 === 0 ? 'Express' : 'Standard',
                    'status' => $status,
                    'destination_city' => $city,
                    'destination_state' => $state,
                    'destination_country' => 'PH',
                    'weight' => 0.35 + (($index % 4) * 0.1),
                    'exception_type' => $status === 'exception' ? 'Address review' : null,
                    'exception_note' => $status === 'exception' ? 'Recipient address needs confirmation.' : null,
                    'shipped_at' => in_array($status, ['in_transit', 'delivered'], true) ? now()->subHours($index + 1) : null,
                    'delivered_at' => $status === 'delivered' ? now()->subHours($index) : null,
                ],
            );

            if ($shipment->trackingEvents()->count() === 0) {
                $shipment->trackingEvents()->create([
                    'status' => $status,
                    'label' => match ($status) {
                        'pending' => 'Order received',
                        'packed' => 'Ready to ship',
                        'in_transit' => 'In transit',
                        'delivered' => 'Delivered',
                        'exception' => 'Address needs review',
                        default => ucfirst($status),
                    },
                    'description' => 'Demo tracking event for the Lumora operations console.',
                    'location' => "{$city}, {$state}",
                    'occurred_at' => now()->subMinutes($index * 8),
                    'source' => 'demo',
                ]);
            }
        }

        $this->call(LocalizeLumoraDemoSeeder::class);

        $sampleParcels = [
            ['parcel_number' => '#1001', 'barcode' => 'LUM-1001', 'recipient_name' => 'Maria Santos', 'address_line' => 'Purok 2, Santa Cruz', 'city' => 'Santa Cruz', 'province' => 'Laguna', 'postal_code' => '4009', 'area' => 'LAG-A', 'rider' => 'rider01@example.com'],
            ['parcel_number' => '#1002', 'barcode' => 'LUM-1002', 'recipient_name' => 'Juan Dela Cruz', 'address_line' => 'National Highway, Pagsanjan', 'city' => 'Pagsanjan', 'province' => 'Laguna', 'postal_code' => '4016', 'area' => 'LAG-B', 'rider' => 'rider02@example.com'],
            ['parcel_number' => '#1003', 'barcode' => 'LUM-1003', 'recipient_name' => 'Ana Reyes', 'address_line' => 'Barangay Batong Malake, Los Baños', 'city' => 'Los Baños', 'province' => 'Laguna', 'postal_code' => '4030', 'area' => 'LAG-C', 'rider' => 'rider03@example.com'],
        ];
        foreach ($sampleParcels as $sample) {
            $rider = RiderProfile::whereHas('user', fn ($q) => $q->where('email', $sample['rider']))->first();
            Parcel::updateOrCreate(['parcel_number' => $sample['parcel_number']], ['store_id' => $store->id, 'destination_area_id' => $areas[$sample['area']]->id, 'assigned_rider_id' => $rider?->id, 'barcode' => $sample['barcode'], 'recipient_name' => $sample['recipient_name'], 'address_line' => $sample['address_line'], 'city' => $sample['city'], 'province' => $sample['province'], 'postal_code' => $sample['postal_code'], 'status' => 'sorted', 'received_at' => now()->subHours(2), 'scanned_at' => now()->subHours(1), 'sorted_at' => now()->subMinutes(30)]);
        }
    }
}
