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
            ['email' => 'apple@gmail.com'],
            [
                'name' => 'Apple Store',
                'first_name' => 'Apple',
                'last_name' => 'Store',
                'password' => Hash::make('123123123'),
                'role' => 'seller',
                'status' => 'active',
                'email_verified' => 1,
                'email_verified_at' => now(),
                'shop_name' => 'Apple Store',
                'shop_description' => 'Official Apple products and accessories.',
            ]
        );

        SellerApplication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Apple Store',
                'category' => 'Electronics',
                'status' => 'approved',
                'reason' => 'Official Apple seller account.',
            ]
        );
    }

    public function down(): void
    {
        // Keep seller account intact on rollback.
    }
};
