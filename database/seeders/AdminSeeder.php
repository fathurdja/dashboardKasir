<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        $salesUsers = [
            ['name' => 'Sales 1', 'username' => 'sales1', 'email' => 'sales1@gmail.com'],
            ['name' => 'Sales 2', 'username' => 'sales2', 'email' => 'sales2@gmail.com'],
            ['name' => 'Sales 3', 'username' => 'sales3', 'email' => 'sales3@gmail.com'],
        ];

        foreach ($salesUsers as $s) {
            User::firstOrCreate(
                ['username' => $s['username']],
                [
                    'name' => $s['name'],
                    'username' => $s['username'],
                    'email' => $s['email'],
                    'role' => 'user',
                    'password' => Hash::make('sales123'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
