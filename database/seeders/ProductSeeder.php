<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Boneva Galon isi partai', 'kdklp' => 'GALON', 'price' => 13000, 'stock' => 0],
            ['name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 16000, 'stock' => 0],
            ['name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 17000, 'stock' => 0],
            ['name' => 'Boneva Galon + isi Partai', 'kdklp' => 'GALON', 'price' => 75000, 'stock' => 0],
            ['name' => 'Boneva Galon + isi', 'kdklp' => 'GALON', 'price' => 85000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 37000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 38000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 40000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'kdklp' => 'BOTOL_1500', 'price' => 41000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'kdklp' => 'BOTOL_1500', 'price' => 43000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'kdklp' => 'GELAS', 'price' => 23000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'kdklp' => 'GELAS', 'price' => 22000, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'kdklp' => 'GELAS', 'price' => 21000, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 38500, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 39000, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'kdklp' => 'BOTOL_1500', 'price' => 42000, 'stock' => 0],
            ['name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 0, 'stock' => 0],
            ['name' => 'Boneva Galon + isi', 'kdklp' => 'GALON', 'price' => 0, 'stock' => 0],
            ['name' => 'Boneva Botol 600 Ml', 'kdklp' => 'BOTOL_600', 'price' => 0, 'stock' => 0],
            ['name' => 'Boneva Botol 1,5 L', 'kdklp' => 'BOTOL_1500', 'price' => 0, 'stock' => 0],
            ['name' => 'Boneva Gelas', 'kdklp' => 'GELAS', 'price' => 0, 'stock' => 0],
        ];

        foreach ($products as $item) {
            Product::create($item);
        }
    }
}
