<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create Default Store Settings
        \App\Models\StoreSettings::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Toko Kasir AI',
                'address' => 'Jl. Contoh Alamat No 123',
                'phone' => '081234567890',
                'tax_rate' => 11.00,
                'store_code' => 'TKA-001',
            ]
        );

        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
