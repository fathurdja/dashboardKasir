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
        $categories = [
            'Makanan' => ['Nasi Goreng Special', 'Mie Goreng Seafood', 'Ayam Bakar Madu', 'Sate Ayam Madura'],
            'Minuman' => ['Es Teh Manis', 'Es Jeruk Peras', 'Kopi Susu Gula Aren', 'Thai Tea Bottle'],
            'Snack' => ['Kentang Goreng', 'Cireng Rujak', 'Pisang Goreng Keju'],
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
