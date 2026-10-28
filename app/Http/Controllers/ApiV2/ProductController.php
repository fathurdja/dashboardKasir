<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\UserStock;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Returns unified global products with quota, sold, and remaining stock
     */
    public function index(Request $request)
    {
        $products = Product::ordered()->get()->map(function ($p) {
            $kuota = (int) ($p->kuota ?? 0);
            $totalTerjualGroup = !empty($p->kdklp)
                ? (int) Product::where('kdklp', $p->kdklp)->sum('stock')
                : (int) $p->stock;
            $sisaKuota = $kuota > 0 ? max(0, $kuota - $totalTerjualGroup) : 0;

            $arr = $p->toArray();
            $arr['terjual'] = (int) $p->stock;
            $arr['kuota'] = $kuota;
            $arr['sisa_kuota'] = $sisaKuota;
            return $arr;
        });

        return response()->json($products);
    }

    /**
     * POST /api/products (Admin only)
     */
    public function store(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang diizinkan menambah produk baru'
            ], 403);
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'kdklp' => 'nullable|string|max:50',
            'price' => 'required|numeric',
            'stock' => 'nullable|numeric',
            'kuota' => 'nullable|numeric',
        ]);

        if (!isset($validated['stock'])) {
            $validated['stock'] = 0;
        }

        if (!isset($validated['kuota']) && !empty($validated['kdklp'])) {
            $existingKuota = Product::where('kdklp', $validated['kdklp'])->value('kuota')
                ?? MasterStock::where('kdklp', $validated['kdklp'])->value('kuota')
                ?? 0;
            $validated['kuota'] = (int) $existingKuota;
        }

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
                'kuota'   => (int) ($product->kuota ?? 0),
                'tglup'   => now(),
            ]
        );

        // Keep UserStock equalized for all users
        UserStock::syncAllFromMasterStock();

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * PUT/PATCH /api/products/{id} (Admin only)
     */
    public function update(Request $request, Product $product)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang diizinkan mengubah produk atau stok'
            ], 403);
        }

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'kdklp' => 'nullable|string|max:50',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|numeric',
            'kuota' => 'sometimes|numeric',
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
            if (isset($validated['kuota'])) $masterUpdates['kuota'] = (int) $validated['kuota'];
            if (!empty($masterUpdates)) {
                $master->update($masterUpdates);
            }
        }

        // If kuota was provided and product has kdklp, sync kuota across the group
        if (isset($validated['kuota']) && !empty($product->kdklp)) {
            $newKuota = (int) $validated['kuota'];
            Product::where('kdklp', $product->kdklp)->update(['kuota' => $newKuota]);
            MasterStock::where('kdklp', $product->kdklp)->update(['kuota' => $newKuota]);
        }

        // Keep UserStock equalized for all users
        UserStock::syncAllFromMasterStock();

        return response()->json($product);
    }

    /**
     * DELETE /api/products/{id} (Admin only)
     */
    public function destroy(Request $request, Product $product)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang diizinkan menghapus produk'
            ], 403);
        }

        $code = sprintf('BV%02d', $product->id);
        MasterStock::where('TYUNIT', $code)->orWhere('NTYUNIT', $product->name)->delete();
        UserStock::where('tyunit', $code)->delete();
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
