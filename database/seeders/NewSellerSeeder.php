<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NewSellerSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = [
            [
                'name'        => 'New Balance Store',
                'email'       => 'newbalance@gmail.com',
                'password'    => Hash::make('123123123'),
                'role'        => 'seller',
                'status'      => 'active',
                'shop_name'   => 'New Balance Store',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'New Balance Store',
                'email'       => 'newbalance1@gmail.com',
                'password'    => Hash::make('123123123'),
                'role'        => 'seller',
                'status'      => 'active',
                'shop_name'   => 'New Balance Store',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Xiaomi Store',
                'email'       => 'xiaomi@gmail.com',
                'password'    => Hash::make('123123123'),
                'role'        => 'seller',
                'status'      => 'active',
                'shop_name'   => 'Xiaomi Store',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($sellers as $sellerData) {
            // updateOrCreate ensures password is always set correctly even if user exists
            $user = User::updateOrCreate(
                ['email' => $sellerData['email']],
                $sellerData
            );
            $this->command->info(($user->wasRecentlyCreated ? 'Created' : 'Updated') . " seller: {$sellerData['email']}");
        }
    }
}
