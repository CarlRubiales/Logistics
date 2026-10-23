<?php

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'rolex@gmail.com'],
            [
                'name' => 'Rolex Store',
                'first_name' => 'Rolex',
                'last_name' => 'Store',
                'password' => Hash::make('123123123'),
                'role' => 'seller',
                'status' => 'active',
                'email_verified' => 1,
                'email_verified_at' => now(),
                'shop_name' => 'Rolex Store',
                'shop_description' => 'Official Rolex luxury watches.',
            ]
        );

        SellerApplication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Rolex Store',
                'category' => 'Fashion & Accessories',
                'status' => 'approved',
                'reason' => 'Official Rolex seller account.',
            ]
        );
    }

    public function down(): void
    {
        // Keep seller account intact on rollback.
    }
};
