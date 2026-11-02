<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // 1 - 7 : Kelompok GALON
            ['id' => 1,  'name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 0,     'stock' => 0],
            ['id' => 2,  'name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 13000, 'stock' => 0],
            ['id' => 3,  'name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 16000, 'stock' => 0],
            ['id' => 4,  'name' => 'Boneva Galon isi saja', 'kdklp' => 'GALON', 'price' => 17000, 'stock' => 0],
            ['id' => 5,  'name' => 'Boneva Galon + isi',    'kdklp' => 'GALON', 'price' => 0,     'stock' => 0],
            ['id' => 6,  'name' => 'Boneva Galon + isi',    'kdklp' => 'GALON', 'price' => 75000, 'stock' => 0],
            ['id' => 7,  'name' => 'Boneva Galon + isi',    'kdklp' => 'GALON', 'price' => 85000, 'stock' => 0],

            // 8 - 13 : Kelompok BOTOL_600
            ['id' => 8,  'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 0,     'stock' => 0],
            ['id' => 9,  'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 37000, 'stock' => 0],
            ['id' => 10, 'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 38000, 'stock' => 0],
            ['id' => 11, 'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 38500, 'stock' => 0],
            ['id' => 12, 'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 39000, 'stock' => 0],
            ['id' => 13, 'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 40000, 'stock' => 0],

            // 14 - 17 : Kelompok BOTOL_1500
            ['id' => 14, 'name' => 'Boneva Botol 1,5 L',    'kdklp' => 'BOTOL_1500', 'price' => 0,     'stock' => 0],
            ['id' => 15, 'name' => 'Boneva Botol 1,5 L',    'kdklp' => 'BOTOL_1500', 'price' => 41000, 'stock' => 0],
            ['id' => 16, 'name' => 'Boneva Botol 1,5 L',    'kdklp' => 'BOTOL_1500', 'price' => 42000, 'stock' => 0],
            ['id' => 17, 'name' => 'Boneva Botol 1,5 L',    'kdklp' => 'BOTOL_1500', 'price' => 43000, 'stock' => 0],

            // 18 - 24 : Kelompok GELAS
            ['id' => 18, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 0,     'stock' => 0],
            ['id' => 19, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 21000, 'stock' => 0],
            ['id' => 20, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 22000, 'stock' => 0],
            ['id' => 21, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 23000, 'stock' => 0],
            ['id' => 22, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 21500, 'stock' => 0],
            ['id' => 23, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 22500, 'stock' => 0],
            ['id' => 24, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 24000, 'stock' => 0],

            // 25 : Kelompok BOTOL_600 (Tambahan varian)
            ['id' => 25, 'name' => 'Boneva Botol 600 Ml',   'kdklp' => 'BOTOL_600', 'price' => 41000, 'stock' => 0],
        ];

        foreach ($products as $item) {
            Product::create($item);
        }
    }
}
