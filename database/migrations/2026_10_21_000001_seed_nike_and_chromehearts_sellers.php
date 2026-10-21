<?php

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $sellers = [
            [
                'email' => 'nike@gmail.com',
                'name' => 'Nike Store',
                'first_name' => 'Nike',
                'last_name' => 'Store',
                'shop_name' => 'Nike Store',
                'shop_description' => 'Official Nike footwear, apparel, and sportswear.',
                'category' => 'Sports & Outdoors',
                'reason' => 'Official Nike seller account.',
            ],
            [
                'email' => 'chromehearts@gmail.com',
                'name' => 'Chrome Hearts',
                'first_name' => 'Chrome',
                'last_name' => 'Hearts',
                'shop_name' => 'Chrome Hearts',
                'shop_description' => 'Official Chrome Hearts luxury apparel, silver jewelry, and accessories.',
                'category' => 'Fashion & Accessories',
                'reason' => 'Official Chrome Hearts seller account.',
            ],
        ];

        foreach ($sellers as $seller) {
            $user = User::updateOrCreate(
                ['email' => $seller['email']],
                [
                    'name' => $seller['name'],
                    'first_name' => $seller['first_name'],
                    'last_name' => $seller['last_name'],
                    'password' => Hash::make('123123123'),
                    'role' => 'seller',
                    'status' => 'active',
                    'email_verified' => 1,
                    'email_verified_at' => now(),
                    'shop_name' => $seller['shop_name'],
                    'shop_description' => $seller['shop_description'],
                ]
            );

            SellerApplication::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $seller['shop_name'],
                    'category' => $seller['category'],
                    'status' => 'approved',
                    'reason' => $seller['reason'],
                ]
            );
        }
    }

    public function down(): void
    {
        // Keep seller accounts intact on rollback.
    }
};
