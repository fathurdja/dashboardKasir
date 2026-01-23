<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Kopi Hitam', 'price' => 15000, 'stock' => 20],
            ['name' => 'Teh Manis', 'price' => 10000, 'stock' => 30],
            ['name' => 'Air Mineral', 'price' => 5000, 'stock' => 50],
            ['name' => 'Nasi Goreng', 'price' => 25000, 'stock' => 15],
            ['name' => 'Mie Goreng', 'price' => 20000, 'stock' => 18],
            ['name' => 'Ayam Geprek', 'price' => 22000, 'stock' => 25],
            ['name' => 'Burger Mini', 'price' => 18000, 'stock' => 12],
            ['name' => 'Kentang Goreng', 'price' => 15000, 'stock' => 20],
            ['name' => 'Es Jeruk', 'price' => 12000, 'stock' => 30],
            ['name' => 'Es Teh', 'price' => 8000, 'stock' => 40],
            ['name' => 'Soda Gembira', 'price' => 17000, 'stock' => 10],
            ['name' => 'Roti Bakar', 'price' => 16000, 'stock' => 14],
            ['name' => 'Pisang Goreng', 'price' => 10000, 'stock' => 22],
            ['name' => 'Sate Ayam', 'price' => 30000, 'stock' => 10],
            ['name' => 'Soto Ayam', 'price' => 22000, 'stock' => 8],
            ['name' => 'Bakso', 'price' => 20000, 'stock' => 15],
            ['name' => 'Nugget', 'price' => 12000, 'stock' => 25],
            ['name' => 'Susu Coklat', 'price' => 14000, 'stock' => 18],
            ['name' => 'Lemon Tea', 'price' => 12000, 'stock' => 20],
            ['name' => 'Teh Botol', 'price' => 6000, 'stock' => 50],
        ];

        foreach ($products as $item) {
            Product::create($item);
        }
    }
}
