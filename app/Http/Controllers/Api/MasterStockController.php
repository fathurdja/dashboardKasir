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
            'stock'   => 'sometimes|numeric',
            'kuota'   => 'sometimes|numeric',
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
            if (isset($validated['kuota'])) $productUpdates['kuota'] = (int) $validated['kuota'];
            if (!empty($productUpdates)) {
                $product->update($productUpdates);
            }
        }

        // If setting kuota, sync to all items in group
        if (isset($validated['kuota']) && !empty($stock->kdklp)) {
            $newKuota = (int) $validated['kuota'];
            MasterStock::where('kdklp', $stock->kdklp)->update(['kuota' => $newKuota]);
            Product::where('kdklp', $stock->kdklp)->update(['kuota' => $newKuota]);
        }

        $stock->refresh();
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
        $kuota = $request->input('kuota', $request->input('quota'));
        $mode = $request->input('mode', 'set'); // 'set' or 'add'
        $resetSales = $request->boolean('reset_sales', false);

        // 1. Setting Group Quota (e.g. GALON = 100)
        if (!empty($kdklp) || $kuota !== null) {
            $targetGroup = strtoupper(trim($kdklp ?: 'GALON'));
            $groupQuery = MasterStock::where('kdklp', $targetGroup);
            if ($groupQuery->count() === 0) {
                $found = MasterStock::where('kdklp', 'LIKE', "%{$targetGroup}%")->first();
                if ($found) {
                    $targetGroup = $found->kdklp;
                }
            }

            $kuotaVal = (int) ($kuota ?? $stock ?? 0);
            MasterStock::where('kdklp', $targetGroup)->update(['kuota' => $kuotaVal]);
            Product::where('kdklp', $targetGroup)->update(['kuota' => $kuotaVal]);

            if ($resetSales || $request->has('reset')) {
                MasterStock::where('kdklp', $targetGroup)->update(['stock' => 0]);
                Product::where('kdklp', $targetGroup)->update(['stock' => 0]);
            }

            $totalTerjual = (int) MasterStock::where('kdklp', $targetGroup)->sum('stock');
            return response()->json([
                'success' => true,
                'message' => "Kuota untuk kelompok {$targetGroup} berhasil diset ke {$kuotaVal} unit",
                'group' => $targetGroup,
                'kuota' => $kuotaVal,
                'total_terjual' => $totalTerjual,
                'sisa_kuota' => max(0, $kuotaVal - $totalTerjual),
                'items_count' => MasterStock::where('kdklp', $targetGroup)->count(),
            ]);
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

        // 2. Update individual product stock / sales count
        if ($stock !== null) {
            $stockValue = (int) $stock;
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
        return $this->stockSummary();
    }

    /**
     * Set Stok Awal / Kuota Komoditas
     * Endpoint: POST /api/stock-awal
     */
    public function setStockAwal(Request $request)
    {
        $validated = $request->validate([
            'kelompok'      => 'sometimes|string',
            'kdklp'         => 'sometimes|string',
            'category'      => 'sometimes|string',
            'kuota'         => 'sometimes|numeric|min:0',
            'stock_awal'    => 'sometimes|numeric|min:0',
            'reset_terjual' => 'sometimes|boolean',
        ]);

        $group = strtoupper(trim($validated['kelompok'] ?? $validated['kdklp'] ?? $validated['category'] ?? 'GALON'));
        $kuotaVal = (int) ($validated['stock_awal'] ?? $validated['kuota'] ?? 0);
        $resetTerjual = $request->boolean('reset_terjual', false);

        // Fuzzy match nama kelompok
        $match = MasterStock::where('kdklp', $group)->first();
        if (!$match) {
            $match = MasterStock::where('kdklp', 'LIKE', "%{$group}%")->first();
            if ($match) {
                $group = $match->kdklp;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Kelompok {$group} tidak ditemukan. Pilihan kelompok yang tersedia: GALON, BOTOL_600, BOTOL_1500, GELAS."
                ], 404);
            }
        }

        // Set kuota awal
        MasterStock::where('kdklp', $group)->update(['kuota' => $kuotaVal, 'tglup' => now()]);
        Product::where('kdklp', $group)->update(['kuota' => $kuotaVal]);

        // Opsi reset counter terjual ke 0 (biasanya untuk awal hari baru)
        if ($resetTerjual) {
            MasterStock::where('kdklp', $group)->update(['stock' => 0]);
            Product::where('kdklp', $group)->update(['stock' => 0]);
        }

        $totalTerjual = (int) MasterStock::where('kdklp', $group)->sum('stock');
        $sisaStock = max(0, $kuotaVal - $totalTerjual);

        return response()->json([
            'success' => true,
            'message' => "Stok awal untuk kelompok {$group} berhasil diset ke {$kuotaVal} unit",
            'data' => [
                'kelompok'      => $group,
                'stock_awal'    => $kuotaVal,
                'total_terjual' => $totalTerjual,
                'sisa_stock'    => $sisaStock,
                'reset_terjual' => $resetTerjual,
                'jumlah_produk' => MasterStock::where('kdklp', $group)->count(),
            ]
        ], 200);
    }

    /**
     * Get Daftar Stok Awal per Kelompok
     * Endpoint: GET /api/stock-awal
     */
    public function getStockAwal()
    {
        $groups = MasterStock::select('kdklp')
            ->distinct()
            ->whereNotNull('kdklp')
            ->get()
            ->map(function ($row) {
                $items = MasterStock::where('kdklp', $row->kdklp)->get();
                $first = $items->first();
                $stockAwal = $first ? (int) $first->kuota : 0;
                $totalTerjual = (int) $items->sum('stock');
                $sisaStock = $stockAwal > 0 ? max(0, $stockAwal - $totalTerjual) : 0;

                return [
                    'kelompok'      => $row->kdklp,
                    'stock_awal'    => $stockAwal,
                    'total_terjual' => $totalTerjual,
                    'sisa_stock'    => $sisaStock,
                    'jumlah_produk' => $items->count(),
                ];
            });

        return response()->json($groups);
    }

    /**
     * Get Rincian Stok Terjual per Produk
     * Endpoint: GET /api/stock-terjual
     */
    public function getStockTerjual(Request $request)
    {
        $query = MasterStock::query();
        if ($request->has('kelompok')) {
            $query->where('kdklp', strtoupper($request->kelompok));
        }

        $items = $query->get()->map(function ($it) {
            return [
                'tyunit'      => $it->TYUNIT,
                'nama_produk' => $it->NTYUNIT,
                'kelompok'    => $it->kdklp,
                'harga'       => (int) $it->hjual,
                'terjual'     => (int) $it->stock,
                'tglup'       => $it->tglup,
            ];
        });

        $totalTerjualSemua = $items->sum('terjual');

        return response()->json([
            'total_terjual_keseluruhan' => $totalTerjualSemua,
            'items'                     => $items,
        ]);
    }

    /**
     * Reset Counter Stok Terjual (misal Tutup Buku / Awal Hari)
     * Endpoint: POST /api/stock-terjual/reset
     */
    public function resetStockTerjual(Request $request)
    {
        $group = $request->input('kelompok', $request->input('kdklp'));
        if ($group) {
            $group = strtoupper(trim($group));
            MasterStock::where('kdklp', $group)->update(['stock' => 0]);
            Product::where('kdklp', $group)->update(['stock' => 0]);
            $msg = "Counter stok terjual untuk kelompok {$group} berhasil direset ke 0";
        } else {
            MasterStock::query()->update(['stock' => 0]);
            Product::query()->update(['stock' => 0]);
            $msg = "Seluruh counter stok terjual semua produk berhasil direset ke 0";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    /**
     * Ringkasan Komprehensif (Stok Awal, Terjual, Sisa)
     * Endpoint: GET /api/stock-summary
     */
    public function stockSummary()
    {
        $groups = MasterStock::select('kdklp')
            ->distinct()
            ->whereNotNull('kdklp')
            ->get()
            ->map(function ($row) {
                $items = MasterStock::where('kdklp', $row->kdklp)->get();
                $first = $items->first();
                $stockAwal = $first ? (int) $first->kuota : 0;
                $totalTerjual = (int) $items->sum('stock');
                $sisaStock = $stockAwal > 0 ? max(0, $stockAwal - $totalTerjual) : 0;

                return [
                    'kelompok'       => $row->kdklp,
                    'stock_awal'     => $stockAwal,
                    'total_terjual'  => $totalTerjual,
                    'sisa_stock'     => $sisaStock,
                    'jumlah_produk'  => $items->count(),
                    'rincian_produk' => $items->map(function ($it) {
                        return [
                            'tyunit'      => $it->TYUNIT,
                            'nama_produk' => $it->NTYUNIT,
                            'harga'       => (int) $it->hjual,
                            'terjual'     => (int) $it->stock,
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
