<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'kdklp' => 'nullable|string|max:50',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
        ]);

        $product = Product::create($validated);

        // Sync with MasterStock
        $code = sprintf('BV%02d', $product->id);
        MasterStock::updateOrCreate(
            ['TYUNIT' => $code],
            [
                'NTYUNIT' => $product->name,
                'kdklp'   => $product->kdklp,
                'hjual'   => $product->price,
                'stock'   => (int) $product->stock,
                'tglup'   => now(),
            ]
        );

        if (!empty($product->kdklp)) {
            $newStock = (int) $product->stock;
            Product::where('kdklp', $product->kdklp)->update(['stock' => $newStock]);
            MasterStock::where('kdklp', $product->kdklp)->update(['stock' => $newStock, 'tglup' => now()]);
        }

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'kdklp' => 'nullable|string|max:50',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|numeric',
        ]);

        $product->update($validated);

        // Sync with MasterStock
        $code = sprintf('BV%02d', $product->id);
        $master = MasterStock::find($code) ?? MasterStock::where('NTYUNIT', $product->name)->first();
        if ($master) {
            $masterUpdates = [];
            if (isset($validated['stock'])) $masterUpdates['stock'] = (int) $product->stock;
            if (isset($validated['price'])) $masterUpdates['hjual'] = $product->price;
            if (isset($validated['name'])) $masterUpdates['NTYUNIT'] = $product->name;
            if (isset($validated['kdklp'])) $masterUpdates['kdklp'] = $validated['kdklp'];
            if (!empty($masterUpdates)) {
                $master->update($masterUpdates);
            }
        }

        // Shared stock sync for groups (e.g. GALON)
        if (isset($validated['stock']) && !empty($product->kdklp)) {
            $newStock = (int) $validated['stock'];
            Product::where('kdklp', $product->kdklp)->update(['stock' => $newStock]);
            MasterStock::where('kdklp', $product->kdklp)->update(['stock' => $newStock, 'tglup' => now()]);
        }

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
