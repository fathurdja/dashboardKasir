<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterStockResource;
use App\Models\MasterStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterStockController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stocks = MasterStock::with('items')->get();
        return MasterStockResource::collection($stocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('price') && !$request->has('hjual')) {
            $request->merge(['hjual' => $request->input('price')]);
        }
        if ($request->has('name') && !$request->has('NTYUNIT')) {
            $request->merge(['NTYUNIT' => $request->input('name')]);
        }

        $validated = $request->validate([
            'TYUNIT'  => 'required|string|unique:munit,TYUNIT',
            'NTYUNIT' => 'required|string',
            'kdklp'   => 'nullable|string',
            'hjual'   => 'required|numeric',
            'stock'   => 'nullable|numeric|min:0',
            'cmodule' => 'nullable|string',
            'userup'  => 'nullable|string',
            'tglup'   => 'nullable|date',
        ]);

        $stock = MasterStock::create($validated);

        Product::firstOrCreate(
            ['name' => $stock->NTYUNIT],
            [
                'price' => $stock->hjual,
                'stock' => $stock->stock ?? 0,
            ]
        );

        return new MasterStockResource($stock);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stock = MasterStock::with('items')->findOrFail($id);
        return new MasterStockResource($stock);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $stock = MasterStock::findOrFail($id);

        if ($request->has('price') && !$request->has('hjual')) {
            $request->merge(['hjual' => $request->input('price')]);
        }
        if ($request->has('name') && !$request->has('NTYUNIT')) {
            $request->merge(['NTYUNIT' => $request->input('name')]);
        }

        $validated = $request->validate([
            'TYUNIT'  => [
                'sometimes',
                'string',
                Rule::unique('munit', 'TYUNIT')->ignore($stock->TYUNIT, 'TYUNIT'),
            ],
            'NTYUNIT' => 'sometimes|string',
            'kdklp'   => 'nullable|string',
            'hjual'   => 'sometimes|numeric',
            'stock'   => 'sometimes|numeric|min:0',
            'cmodule' => 'nullable|string',
            'userup'  => 'nullable|string',
            'tglup'   => 'nullable|date',
        ]);

        $stock->update($validated);

        // Keep Product table synchronized
        $product = Product::where('name', $stock->NTYUNIT)->first();
        if ($product) {
            $productUpdates = [];
            if (isset($validated['stock'])) $productUpdates['stock'] = (int) $stock->stock;
            if (isset($validated['hjual'])) $productUpdates['price'] = $stock->hjual;
            if (isset($validated['NTYUNIT'])) $productUpdates['name'] = $stock->NTYUNIT;
            if (isset($validated['kdklp'])) $productUpdates['kdklp'] = $validated['kdklp'];
            if (!empty($productUpdates)) {
                $product->update($productUpdates);
            }
        }

        // Shared stock sync for groups (e.g. GALON)
        if (isset($validated['stock']) && !empty($stock->kdklp)) {
            $newStock = (int) $validated['stock'];
            MasterStock::where('kdklp', $stock->kdklp)->update(['stock' => $newStock, 'tglup' => now()]);
            Product::where('kdklp', $stock->kdklp)->update(['stock' => $newStock]);
            $stock->refresh();
        }

        return new MasterStockResource($stock);
    }

    /**
     * Dedicated stock update endpoint
     */
    public function updateStock(Request $request)
    {
        $kdklp = $request->input('kdklp', $request->input('category', $request->input('group')));
        $tyunit = $request->input('tyunit', $request->input('TYUNIT', $request->input('id')));
        $stock = $request->input('stock', $request->input('quantity', $request->input('qty')));
        $mode = $request->input('mode', 'set'); // 'set' or 'add'

        if ($stock === null) {
            return response()->json(['success' => false, 'message' => 'Nilai stock wajib diisi'], 422);
        }

        $stockValue = (int) $stock;

        // If target is directly a group / category (e.g. "GALON")
        if (!empty($kdklp)) {
            $targetGroup = strtoupper(trim($kdklp));
            $groupQuery = MasterStock::where('kdklp', $targetGroup);
            if ($groupQuery->count() === 0) {
                // Try partial search e.g. "galon" in group
                $found = MasterStock::where('kdklp', 'LIKE', "%{$targetGroup}%")->first();
                if ($found) {
                    $targetGroup = $found->kdklp;
                    $groupQuery = MasterStock::where('kdklp', $targetGroup);
                }
            }

            if ($groupQuery->count() > 0) {
                if ($mode === 'add') {
                    MasterStock::where('kdklp', $targetGroup)->increment('stock', $stockValue);
                    Product::where('kdklp', $targetGroup)->increment('stock', $stockValue);
                } else {
                    MasterStock::where('kdklp', $targetGroup)->update(['stock' => $stockValue, 'tglup' => now()]);
                    Product::where('kdklp', $targetGroup)->update(['stock' => $stockValue]);
                }

                $sample = MasterStock::where('kdklp', $targetGroup)->first();
                return response()->json([
                    'success' => true,
                    'message' => "Stock untuk kelompok {$targetGroup} berhasil diperbarui",
                    'group' => $targetGroup,
                    'stock' => (int) $sample->stock,
                    'items_count' => MasterStock::where('kdklp', $targetGroup)->count(),
                ]);
            }
        }

        if (is_numeric($tyunit)) {
            $tyunit = sprintf('BV%02d', $tyunit);
        }

        $m = null;
        if ($tyunit) {
            $m = MasterStock::find($tyunit);
        }
        if (!$m && $request->has('name')) {
            $name = $request->input('name');
            $m = MasterStock::where('NTYUNIT', $name)->first();
            if (!$m) {
                $m = MasterStock::where('NTYUNIT', 'LIKE', "%{$name}%")->first();
            }
        }

        if (!$m) {
            return response()->json(['success' => false, 'message' => "Item {$tyunit} tidak ditemukan"], 404);
        }

        // Shared stock sync for groups (e.g. all GALON items)
        if (!empty($m->kdklp)) {
            if ($mode === 'add') {
                MasterStock::where('kdklp', $m->kdklp)->increment('stock', $stockValue);
                Product::where('kdklp', $m->kdklp)->increment('stock', $stockValue);
            } else {
                MasterStock::where('kdklp', $m->kdklp)->update(['stock' => $stockValue, 'tglup' => now()]);
                Product::where('kdklp', $m->kdklp)->update(['stock' => $stockValue]);
            }
            $m->refresh();
        } else {
            if ($mode === 'add') {
                $m->stock += $stockValue;
            } else {
                $m->stock = $stockValue;
            }
            $m->tglup = now();
            $m->save();
            Product::where('name', $m->NTYUNIT)->update(['stock' => $m->stock]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock berhasil diperbarui',
            'data' => new MasterStockResource($m)
        ]);
    }

    /**
     * Get grouped stock summary
     */
    public function stockGroups()
    {
        $groups = MasterStock::select('kdklp')
            ->distinct()
            ->whereNotNull('kdklp')
            ->get()
            ->map(function ($row) {
                $items = MasterStock::where('kdklp', $row->kdklp)->get();
                $first = $items->first();
                return [
                    'group' => $row->kdklp,
                    'stock' => $first ? (int) $first->stock : 0,
                    'items_count' => $items->count(),
                    'items' => $items->map(function ($it) {
                        return [
                            'tyunit' => $it->TYUNIT,
                            'name'   => $it->NTYUNIT,
                            'price'  => (int) $it->hjual,
                            'stock'  => (int) $it->stock,
                        ];
                    })
                ];
            });

        return response()->json($groups);
    }

    /**
     * Batch sync endpoint
     */
    public function sync(Request $request)
    {
        $items = $request->all();
        if (isset($items['items'])) {
            $items = $items['items'];
        } elseif (isset($items['data'])) {
            $items = $items['data'];
        }

        if (!is_array($items)) {
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $tyunit = $item['tyunit'] ?? $item['TYUNIT'] ?? null;
            $id = $item['id'] ?? null;
            $stock = $item['stock'] ?? null;
            $price = $item['price'] ?? $item['hjual'] ?? null;
            $name = $item['name'] ?? $item['NTYUNIT'] ?? null;

            $m = null;
            if ($tyunit) {
                $m = MasterStock::find($tyunit);
            } elseif ($id) {
                $code = sprintf('BV%02d', $id);
                $m = MasterStock::find($code);
            } elseif ($name) {
                $m = MasterStock::where('NTYUNIT', $name)->first();
            }

            if ($m) {
                if ($stock !== null) {
                    $stockVal = (int) $stock;
                    if (!empty($m->kdklp)) {
                        MasterStock::where('kdklp', $m->kdklp)->update(['stock' => $stockVal, 'tglup' => now()]);
                        Product::where('kdklp', $m->kdklp)->update(['stock' => $stockVal]);
                    } else {
                        $m->stock = $stockVal;
                    }
                }
                if ($price !== null) $m->hjual = $price;
                if ($name !== null) $m->NTYUNIT = $name;
                $m->save();

                Product::where('name', $m->NTYUNIT)->update([
                    'stock' => $m->stock,
                    'price' => $m->hjual,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stock berhasil disinkronkan']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stock = MasterStock::findOrFail($id);
        $stock->delete();

        return response()->json([
            'message' => 'Master stock berhasil dihapus.'
        ]);
    }
}
