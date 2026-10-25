<?php

use App\Models\Product;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure Earrings seller user exists
        $seller = User::where('email', 'earrings@gmail.com')->first();

        if (! $seller) {
            $seller = User::create([
                'name' => 'Earrings Store',
                'first_name' => 'Earrings',
                'last_name' => 'Store',
                'email' => 'earrings@gmail.com',
                'password' => Hash::make('123123123'),
                'role' => 'seller',
                'status' => 'active',
                'email_verified' => 1,
                'email_verified_at' => now(),
                'shop_name' => 'Earrings Store',
                'shop_description' => 'Earrings and jewelry accessories.',
            ]);

            $sellerAppData = [
                'business_name' => 'Earrings Store',
                'status' => 'approved',
                'reason' => 'Official Earrings seller account.',
            ];
            if (Schema::hasColumn('seller_applications', 'category')) {
                $sellerAppData['category'] = 'Fashion & Accessories';
            }

            SellerApplication::updateOrCreate(
                ['user_id' => $seller->id],
                $sellerAppData
            );
        }

        // 2. Persist image binaries into uploaded_files table for deployment durability
        $imageFiles = [
            'products/stud-earring-1.jpg',
            'products/stud-earring-2.jpg',
            'products/stud-earring-3.jpg',
            'products/stud-earring-4.jpg',
            'products/cross-earring-1.jpg',
            'products/cross-earring-2.jpg',
            'products/cross-earring-3.jpg',
            'products/cross-earring-4.jpg',
        ];

        if (Schema::hasTable('uploaded_files')) {
            foreach ($imageFiles as $relPath) {
                $fullPath = public_path($relPath);
                if (file_exists($fullPath)) {
                    $raw = @file_get_contents($fullPath);
                    if ($raw !== false && $raw !== '') {
                        DB::table('uploaded_files')->updateOrInsert(
                            ['path' => $relPath],
                            [
                                'mime_type' => 'image/jpeg',
                                'contents' => base64_encode($raw),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }

        // 3. Seed "Diamond Solitaire Stud Earrings" with 4 photo slots
        $studEarring = Product::updateOrCreate(
            [
                'seller_id' => $seller->id,
                'name' => 'Diamond Solitaire Stud Earrings',
            ],
            [
                'category' => 'womens-accessories',
                'description' => "Timeless and radiant, these classic solitaire stud earrings feature brilliant round-cut lab diamonds set in polished four-prong solid sterling silver and platinum basket settings. Designed for everyday luxury and effortless brilliance, complete with secure butterfly push backs.",
                'price' => 1299.00,
                'discount_percent' => 15.00,
                'stock' => 50,
                'sales_count' => 84,
                'rating' => 4.90,
                'variant_type' => 'Size',
                'image' => 'products/stud-earring-1.jpg',
                'images' => [
                    'products/stud-earring-1.jpg',
                    'products/stud-earring-2.jpg',
                    'products/stud-earring-3.jpg',
                    'products/stud-earring-4.jpg',
                ],
                'status' => 'active',
            ]
        );

        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->where('product_id', $studEarring->id)->delete();
            DB::table('product_variants')->insert([
                [
                    'product_id' => $studEarring->id,
                    'name' => '4mm (0.50 ctw)',
                    'stock' => 25,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'product_id' => $studEarring->id,
                    'name' => '6mm (1.00 ctw)',
                    'stock' => 25,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 4. Seed "Gothic Silver Cross Drop Earrings" with 4 photo slots
        $crossEarring = Product::updateOrCreate(
            [
                'seller_id' => $seller->id,
                'name' => 'Gothic Silver Cross Drop Earrings',
            ],
            [
                'category' => 'womens-accessories',
                'description' => "Make an iconic statement with these gothic cross drop earrings. Handcrafted from premium 925 sterling silver with vintage filigree engraving and subtle dark oxidized patina, suspended from a comfortable snap-closure huggie hoop for seamless day-to-night styling.",
                'price' => 899.00,
                'discount_percent' => 10.00,
                'stock' => 65,
                'sales_count' => 62,
                'rating' => 4.85,
                'variant_type' => 'Style',
                'image' => 'products/cross-earring-1.jpg',
                'images' => [
                    'products/cross-earring-1.jpg',
                    'products/cross-earring-2.jpg',
                    'products/cross-earring-3.jpg',
                    'products/cross-earring-4.jpg',
                ],
                'status' => 'active',
            ]
        );

        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->where('product_id', $crossEarring->id)->delete();
            DB::table('product_variants')->insert([
                [
                    'product_id' => $crossEarring->id,
                    'name' => 'Single Earring',
                    'stock' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'product_id' => $crossEarring->id,
                    'name' => 'Pair (2 Earrings)',
                    'stock' => 35,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Keep seeded products intact on rollback
    }
};
