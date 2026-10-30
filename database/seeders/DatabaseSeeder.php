<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'amelia@lumora.example'],
            ['name' => 'Amelia Morgan', 'password' => bcrypt('password'), 'role' => 'admin'],
        );

        $this->call(DemoDataSeeder::class);
    }
}
