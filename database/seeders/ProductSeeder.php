<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockTransaction;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
<<<<<<< HEAD
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

            // 26 : Kelompok GELAS (Tambahan varian Rp 20.000)
            ['id' => 26, 'name' => 'Boneva Gelas',          'kdklp' => 'GELAS', 'price' => 20000, 'stock' => 0],
=======
        $categories = [
            'Makanan' => ['Nasi Goreng Special', 'Mie Goreng Seafood', 'Ayam Bakar Madu', 'Sate Ayam Madura'],
            'Minuman' => ['Es Teh Manis', 'Es Jeruk Peras', 'Kopi Susu Gula Aren', 'Thai Tea Bottle'],
            'Snack' => ['Kentang Goreng', 'Cireng Rujak', 'Pisang Goreng Keju'],
>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e
        ];

        foreach ($categories as $catName => $products) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName, 'is_active' => true]
            );

            foreach ($products as $prodName) {
                $purchasePrice = rand(5000, 20000);
                $product = Product::firstOrCreate(
                    ['name' => $prodName, 'category_id' => $category->id],
                    [
                        'description' => 'Menu ' . $prodName . ' paling enak di kota ini.',
                        'purchase_price' => $purchasePrice,
                        'price' => $purchasePrice + rand(5000, 15000),
                        'is_active' => true,
                        'barcode' => rand(100000000, 999999999),
                    ]
                );

                StockTransaction::create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'type' => 'in',
                    'quantity' => rand(50, 200),
                    'notes' => 'Initial Stock',
                ]);

                // Add variants for some products
                if (Str::contains($prodName, 'Es') || Str::contains($prodName, 'Kopi')) {
                    $v1 = ProductVariant::firstOrCreate(
                        ['product_id' => $product->id, 'name' => 'Ukuran Besar'],
                        [
                            'purchase_price' => 2000,
                            'additional_price' => 5000,
                            'sku' => 'VAR-' . rand(1000, 9999),
                        ]
                    );
                    StockTransaction::create([
                        'product_id' => $product->id,
                        'variant_id' => $v1->id,
                        'type' => 'in',
                        'quantity' => rand(30, 100),
                        'notes' => 'Initial Stock',
                    ]);
                    
                    $v2 = ProductVariant::firstOrCreate(
                        ['product_id' => $product->id, 'name' => 'Ukuran Sedang'],
                        [
                            'purchase_price' => 0,
                            'additional_price' => 0,
                            'sku' => 'VAR-' . rand(1000, 9999),
                        ]
                    );
                    StockTransaction::create([
                        'product_id' => $product->id,
                        'variant_id' => $v2->id,
                        'type' => 'in',
                        'quantity' => rand(30, 100),
                        'notes' => 'Initial Stock',
                    ]);
                }
            }
        }
    }
}
