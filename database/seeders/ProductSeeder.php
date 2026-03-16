<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
            $category = Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
                'is_active' => true,
            ]);

            foreach ($products as $prodName) {
                $purchasePrice = rand(5000, 20000);
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $prodName,
                    'description' => 'Menu ' . $prodName . ' paling enak di kota ini.',
                    'purchase_price' => $purchasePrice,
                    'price' => $purchasePrice + rand(5000, 15000),
                    'is_active' => true,
                    'barcode' => rand(100000000, 999999999),
                ]);

                // Add variants for some products
                if (Str::contains($prodName, 'Es') || Str::contains($prodName, 'Kopi')) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => 'Ukuran Besar',
                        'purchase_price' => 2000,
                        'additional_price' => 5000,
                        'sku' => 'VAR-' . rand(1000, 9999),
                    ]);
                    
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => 'Ukuran Sedang',
                        'purchase_price' => 0,
                        'additional_price' => 0,
                        'sku' => 'VAR-' . rand(1000, 9999),
                    ]);
                }
            }
        }
    }
}
