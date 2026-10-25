<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\UserStock;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $targetUserId = ($currentUser && !$currentUser->isAdmin())
            ? $currentUser->id
            : ($request->input('user_id') ?? null);

        if ($targetUserId) {
            UserStock::initializeForUser($targetUserId);
            $userStocks = UserStock::where('user_id', $targetUserId)->get()->keyBy('tyunit');

            $products = Product::all()->map(function ($p) use ($userStocks, $targetUserId) {
                $code = sprintf('BV%02d', $p->id);
                $uStock = $userStocks->get($code);
                $kuota = $uStock ? (int) $uStock->kuota : 0;
                $soldGroup = !empty($p->kdklp)
                    ? (int) UserStock::where('user_id', $targetUserId)->where('kdklp', $p->kdklp)->sum('stock')
                    : ($uStock ? (int) $uStock->stock : 0);
                $sisaKuota = $kuota > 0 ? max(0, $kuota - $soldGroup) : 0;

                $arr = $p->toArray();
                $arr['stock'] = $uStock ? (int) $uStock->stock : 0;
                $arr['terjual'] = $uStock ? (int) $uStock->stock : 0;
                $arr['kuota'] = $kuota;
                $arr['sisa_kuota'] = $sisaKuota;
                return $arr;
            });

            return response()->json($products);
        }

        $products = Product::all()->map(function ($p) {
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

    public function store(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa tidak diizinkan menambah produk baru'
            ], 403);
        }

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

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa tidak diizinkan mengubah produk'
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

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa tidak diizinkan menghapus produk'
            ], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
