<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Boneva Galon isi partai', 'price' => 13000, 'stock' => 0],
            ['name' => 'Boneva Galon isi saja', 'price' => 16000, 'stock' => 0],
            ['name' => 'Boneva Galon isi saja', 'price' => 17000, 'stock' => 0],
            ['name' => 'Boneva Galon + isi Partai', 'price' => 75000, 'stock' => 0],
            ['name' => 'Boneva Galon + isi', 'price' => 85000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'price' => 37000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'price' => 38000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'price' => 38500, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'price' => 39000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'price' => 40000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'price' => 41000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'price' => 42000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'price' => 43000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'price' => 23000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'price' => 22000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'price' => 21000, 'stock' => 0],
        ];

        foreach ($products as $item) {
            Product::create($item);
        }
    }
}
