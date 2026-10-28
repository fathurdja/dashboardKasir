<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterStockResource;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\User;
use App\Models\UserStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);

        if ($currentUser && !$currentUser->isAdmin()) {
            UserStock::initializeForUser($currentUser->id);
            $userStocks = UserStock::where('user_id', $currentUser->id)->get()->keyBy('tyunit');

            $stocks = MasterStock::with('items')->ordered()->get()->map(function ($m) use ($userStocks, $currentUser) {
                $uStock = $userStocks->get($m->TYUNIT);
                $kuota = $uStock ? (int) $uStock->kuota : 0;
                $soldGroup = !empty($m->kdklp)
                    ? (int) UserStock::where('user_id', $currentUser->id)->where('kdklp', $m->kdklp)->sum('stock')
                    : ($uStock ? (int) $uStock->stock : 0);
                $sisaKuota = $kuota > 0 ? max(0, $kuota - $soldGroup) : 0;

                return [
                    'TYUNIT'     => $m->TYUNIT,
                    'NTYUNIT'    => $m->NTYUNIT,
                    'kdklp'      => $m->kdklp,
                    'hjual'      => (int) $m->hjual,
                    'stock'      => $uStock ? (int) $uStock->stock : 0, // Terjual user ini
                    'terjual'    => $uStock ? (int) $uStock->stock : 0,
                    'kuota'      => $kuota,
                    'sisa_kuota' => $sisaKuota,
                    'items'      => $m->items,
                ];
            });

            return response()->json(['data' => $stocks]);
        }

        $stocks = MasterStock::with('items')->ordered()->get();
        return MasterStockResource::collection($stocks);
    }

    /**
     * Store a newly created resource in storage (Admin only)
     */
    public function store(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa hanya memiliki hak akses melihat stok (read-only).'
            ], 403);
        }

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

        if (empty($stock->kuota) && !empty($stock->kdklp)) {
            $existingKuota = MasterStock::where('kdklp', $stock->kdklp)->where('TYUNIT', '!=', $stock->TYUNIT)->value('kuota') ?? 0;
            if ($existingKuota > 0) {
                $stock->kuota = $existingKuota;
                $stock->save();
            }
        }

        $prodId = str_starts_with($stock->TYUNIT, 'BV') ? (int) substr($stock->TYUNIT, 2) : null;
        if ($prodId) {
            Product::updateOrCreate(
                ['id' => $prodId],
                [
                    'name'  => $stock->NTYUNIT,
                    'price' => $stock->hjual,
                    'stock' => $stock->stock ?? 0,
                    'kuota' => $stock->kuota ?? 0,
                    'kdklp' => $stock->kdklp,
                ]
            );
        } else {
            Product::create([
                'name'  => $stock->NTYUNIT,
                'price' => $stock->hjual,
                'stock' => $stock->stock ?? 0,
                'kuota' => $stock->kuota ?? 0,
                'kdklp' => $stock->kdklp,
            ]);
        }

        // Ensure all users have this stock initialized
        foreach (User::all() as $u) {
            $userGroupKuota = UserStock::where('user_id', $u->id)
                ->where('kdklp', $stock->kdklp)
                ->value('kuota') ?? ($u->isAdmin() ? 0 : (int) ($stock->kuota ?? 0));

            UserStock::firstOrCreate(
                ['user_id' => $u->id, 'tyunit' => $stock->TYUNIT],
                ['kdklp' => $stock->kdklp, 'kuota' => $userGroupKuota, 'stock' => 0]
            );
        }

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
     * Update the specified resource in storage (Admin only)
     */
    public function update(Request $request, string $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa hanya memiliki hak akses melihat stok (read-only).'
            ], 403);
        }

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
        $prodId = str_starts_with($stock->TYUNIT, 'BV') ? (int) substr($stock->TYUNIT, 2) : null;
        $product = $prodId ? Product::find($prodId) : Product::where('name', $stock->NTYUNIT)->first();
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

        if (isset($validated['kuota']) && !empty($stock->kdklp)) {
            $newKuota = (int) $validated['kuota'];
            MasterStock::where('kdklp', $stock->kdklp)->update(['kuota' => $newKuota]);
            Product::where('kdklp', $stock->kdklp)->update(['kuota' => $newKuota]);
        }

        return new MasterStockResource($stock);
    }

    /**
     * Flexible Stock Update Endpoint (Admin only)
     */
    public function updateStock(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa hanya memiliki hak akses melihat stok (read-only).'
            ], 403);
        }

        if ($request->has('kdklp') || $request->has('category') || $request->has('kelompok')) {
            return $this->setStockAwal($request);
        }

        $validated = $request->validate([
            'id'       => 'sometimes|numeric',
            'tyunit'   => 'sometimes|string',
            'stock'    => 'sometimes|numeric',
            'kuota'    => 'sometimes|numeric',
            'price'    => 'sometimes|numeric',
            'name'     => 'sometimes|string',
        ]);

        $m = null;
        if (!empty($validated['tyunit'])) {
            $m = MasterStock::find($validated['tyunit']);
        } elseif (!empty($validated['id'])) {
            $code = sprintf('BV%02d', $validated['id']);
            $m = MasterStock::find($code);
        } elseif (!empty($validated['name'])) {
            $m = MasterStock::where('NTYUNIT', $validated['name'])->first();
        }

        if (!$m) {
            return response()->json([
                'success' => false,
                'message' => 'Product atau MasterStock tidak ditemukan'
            ], 404);
        }

        if (isset($validated['kuota'])) {
            $kuotaVal = (int) $validated['kuota'];
            if (!empty($m->kdklp)) {
                MasterStock::where('kdklp', $m->kdklp)->update(['kuota' => $kuotaVal]);
                Product::where('kdklp', $m->kdklp)->update(['kuota' => $kuotaVal]);
            } else {
                $m->kuota = $kuotaVal;
                $m->save();
            }
        }

        $prodId = str_starts_with($m->TYUNIT, 'BV') ? (int) substr($m->TYUNIT, 2) : null;
        $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $m->NTYUNIT);

        if (isset($validated['stock'])) {
            $m->stock = (int) $validated['stock'];
            $m->save();
            $prodQuery->update(['stock' => $m->stock]);
        }

        if (isset($validated['price'])) {
            $m->hjual = $validated['price'];
            $m->save();
            $prodQuery->update(['price' => $m->hjual]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock berhasil diperbarui',
            'data'    => new MasterStockResource($m)
        ]);
    }

    /**
     * Set Stok Awal / Kuota Komoditas (Admin only)
     * POST /api/stock-awal
     */
    public function setStockAwal(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa hanya memiliki hak akses melihat stok (read-only). Pengelolaan stok hanya dapat dilakukan oleh Admin.'
            ], 403);
        }

        $validated = $request->validate([
            'kelompok'      => 'sometimes|string',
            'kdklp'         => 'sometimes|string',
            'category'      => 'sometimes|string',
            'kuota'         => 'sometimes|numeric|min:0',
            'stock_awal'    => 'sometimes|numeric|min:0',
            'user_id'       => 'sometimes|nullable|exists:users,id',
            'reset_terjual' => 'sometimes|boolean',
        ]);

        $group = strtoupper(trim($validated['kelompok'] ?? $validated['kdklp'] ?? $validated['category'] ?? 'GALON'));
        $kuotaVal = (int) ($validated['stock_awal'] ?? $validated['kuota'] ?? 0);
        $resetTerjual = $request->boolean('reset_terjual', false);
        $targetUserId = $validated['user_id'] ?? null;

        // Fuzzy match nama kelompok
        $match = MasterStock::where('kdklp', $group)->first();
        if (!$match) {
            $match = MasterStock::where('kdklp', 'LIKE', "%{$group}%")->first();
            if ($match) {
                $group = $match->kdklp;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Kelompok {$group} tidak ditemukan. Pilihan kelompok: GALON, BOTOL_600, BOTOL_1500, GELAS."
                ], 404);
            }
        }

        if ($targetUserId) {
            // Set stock awal for a specific sales user
            UserStock::initializeForUser($targetUserId);
            UserStock::where('user_id', $targetUserId)
                ->where('kdklp', $group)
                ->update(['kuota' => $kuotaVal]);

            if ($resetTerjual) {
                UserStock::where('user_id', $targetUserId)
                    ->where('kdklp', $group)
                    ->update(['stock' => 0]);
            }

            $user = User::find($targetUserId);
            $totalTerjual = (int) UserStock::where('user_id', $targetUserId)->where('kdklp', $group)->sum('stock');
            $sisaStock = max(0, $kuotaVal - $totalTerjual);

            return response()->json([
                'success' => true,
                'message' => "Stok awal {$group} untuk user {$user->name} ({$user->username}) berhasil diset ke {$kuotaVal} unit",
                'data' => [
                    'user_id'       => $user->id,
                    'user_name'     => $user->name,
                    'kelompok'      => $group,
                    'stock_awal'    => $kuotaVal,
                    'total_terjual' => $totalTerjual,
                    'sisa_stock'    => $sisaStock,
                    'reset_terjual' => $resetTerjual,
                ]
            ]);
        }

        // Set global stock awal (updates master and sets default quota for all sales users)
        MasterStock::where('kdklp', $group)->update(['kuota' => $kuotaVal, 'tglup' => now()]);
        Product::where('kdklp', $group)->update(['kuota' => $kuotaVal]);

        foreach (User::where('role', 'user')->get() as $salesUser) {
            UserStock::initializeForUser($salesUser->id);
            UserStock::where('user_id', $salesUser->id)
                ->where('kdklp', $group)
                ->update(['kuota' => $kuotaVal]);
            if ($resetTerjual) {
                UserStock::where('user_id', $salesUser->id)
                    ->where('kdklp', $group)
                    ->update(['stock' => 0]);
            }
        }

        if ($resetTerjual) {
            MasterStock::where('kdklp', $group)->update(['stock' => 0]);
            Product::where('kdklp', $group)->update(['stock' => 0]);
        }

        $totalTerjual = (int) MasterStock::where('kdklp', $group)->sum('stock');
        $sisaStock = max(0, $kuotaVal - $totalTerjual);

        return response()->json([
            'success' => true,
            'message' => "Stok awal kelompok {$group} berhasil diset ke {$kuotaVal} unit untuk semua sales",
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
     * GET /api/stock-awal
     */
    public function getStockAwal(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $targetUserId = ($currentUser && !$currentUser->isAdmin())
            ? $currentUser->id
            : ($request->input('user_id') ?? null);

        if ($targetUserId) {
            UserStock::initializeForUser($targetUserId);
            $groups = UserStock::where('user_id', $targetUserId)
                ->select('kdklp')
                ->distinct()
                ->whereNotNull('kdklp')
                ->get()
                ->map(function ($row) use ($targetUserId) {
                    $items = UserStock::where('user_id', $targetUserId)->where('kdklp', $row->kdklp)->get();
                    $first = $items->first();
                    $stockAwal = $first ? (int) $first->kuota : 0;
                    $totalTerjual = (int) $items->sum('stock');
                    $sisaStock = $stockAwal > 0 ? max(0, $stockAwal - $totalTerjual) : 0;

                    return [
                        'user_id'       => $targetUserId,
                        'kelompok'      => $row->kdklp,
                        'stock_awal'    => $stockAwal,
                        'total_terjual' => $totalTerjual,
                        'sisa_stock'    => $sisaStock,
                        'jumlah_produk' => $items->count(),
                    ];
                });

            return response()->json($groups);
        }

        // Global / Admin view
        $groups = MasterStock::select('kdklp')
            ->distinct()
            ->whereNotNull('kdklp')
            ->orderByRaw("FIELD(kdklp, 'GALON', 'BOTOL_600', 'BOTOL_1500', 'GELAS')")
            ->get()
            ->map(function ($row) {
                $items = MasterStock::where('kdklp', $row->kdklp)->ordered()->get();
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
     * GET /api/stock-terjual
     */
    public function getStockTerjual(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $targetUserId = ($currentUser && !$currentUser->isAdmin())
            ? $currentUser->id
            : ($request->input('user_id') ?? null);

        if ($targetUserId) {
            UserStock::initializeForUser($targetUserId);
            $query = UserStock::with('masterStock')->where('user_id', $targetUserId);
            if ($request->has('kelompok')) {
                $query->where('kdklp', strtoupper($request->kelompok));
            }

            $items = $query->get()->map(function ($us) use ($targetUserId) {
                return [
                    'user_id'     => $targetUserId,
                    'tyunit'      => $us->tyunit,
                    'nama_produk' => $us->masterStock ? $us->masterStock->NTYUNIT : $us->tyunit,
                    'kelompok'    => $us->kdklp,
                    'harga'       => $us->masterStock ? (int) $us->masterStock->hjual : 0,
                    'terjual'     => (int) $us->stock,
                    'tglup'       => $us->updated_at,
                ];
            });

            return response()->json([
                'user_id'                   => $targetUserId,
                'total_terjual_keseluruhan' => $items->sum('terjual'),
                'items'                     => $items,
            ]);
        }

        // Global / Admin view
        $query = MasterStock::ordered();
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

        return response()->json([
            'total_terjual_keseluruhan' => $items->sum('terjual'),
            'items'                     => $items,
        ]);
    }

    /**
     * Reset Counter Stok Terjual (Admin only)
     * POST /api/stock-terjual/reset
     */
    public function resetStockTerjual(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa tidak memiliki izin mereset counter stok terjual. Hanya Admin yang diizinkan.'
            ], 403);
        }

        $group = $request->input('kelompok', $request->input('kdklp'));
        $targetUserId = $request->input('user_id');

        if ($targetUserId) {
            $q = UserStock::where('user_id', $targetUserId);
            if ($group) $q->where('kdklp', strtoupper(trim($group)));
            $q->update(['stock' => 0]);

            return response()->json([
                'success' => true,
                'message' => "Counter stok terjual untuk user ID {$targetUserId} berhasil direset ke 0"
            ]);
        }

        if ($group) {
            $group = strtoupper(trim($group));
            MasterStock::where('kdklp', $group)->update(['stock' => 0]);
            Product::where('kdklp', $group)->update(['stock' => 0]);
            UserStock::where('kdklp', $group)->update(['stock' => 0]);
            $msg = "Counter stok terjual untuk kelompok {$group} berhasil direset ke 0 untuk semua user";
        } else {
            MasterStock::query()->update(['stock' => 0]);
            Product::query()->update(['stock' => 0]);
            UserStock::query()->update(['stock' => 0]);
            $msg = "Seluruh counter stok terjual semua produk berhasil direset ke 0 untuk semua user";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    /**
     * Ringkasan Komprehensif (Stok Awal, Terjual, Sisa)
     * GET /api/stock-summary
     */
    public function stockSummary(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $targetUserId = ($currentUser && !$currentUser->isAdmin())
            ? $currentUser->id
            : ($request->input('user_id') ?? null);

        if ($targetUserId) {
            UserStock::initializeForUser($targetUserId);
            $user = User::find($targetUserId);

            $groups = UserStock::where('user_id', $targetUserId)
                ->select('kdklp')
                ->distinct()
                ->whereNotNull('kdklp')
                ->get()
                ->map(function ($row) use ($targetUserId) {
                    $items = UserStock::with('masterStock')
                        ->where('user_id', $targetUserId)
                        ->where('kdklp', $row->kdklp)
                        ->get();

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
                        'rincian_produk' => $items->map(function ($us) {
                            return [
                                'tyunit'      => $us->tyunit,
                                'nama_produk' => $us->masterStock ? $us->masterStock->NTYUNIT : $us->tyunit,
                                'harga'       => $us->masterStock ? (int) $us->masterStock->hjual : 0,
                                'terjual'     => (int) $us->stock,
                            ];
                        })
                    ];
                });

            return response()->json([
                'user'   => [
                    'id'       => $user ? $user->id : $targetUserId,
                    'name'     => $user ? $user->name : 'Sales',
                    'username' => $user ? $user->username : '',
                ],
                'groups' => $groups,
            ]);
        }

        // Global view
        $groups = MasterStock::select('kdklp')
            ->distinct()
            ->whereNotNull('kdklp')
            ->orderByRaw("FIELD(kdklp, 'GALON', 'BOTOL_600', 'BOTOL_1500', 'GELAS')")
            ->get()
            ->map(function ($row) {
                $items = MasterStock::where('kdklp', $row->kdklp)->ordered()->get();
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

    public function stockGroups(Request $request)
    {
        return $this->stockSummary($request);
    }

    /**
     * Batch sync endpoint (Admin only)
     */
    public function sync(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: User biasa hanya memiliki hak akses melihat stok (read-only).'
            ], 403);
        }

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
                    $m->stock = $stockVal;
                }
                if ($price !== null) $m->hjual = $price;
                if ($name !== null) $m->NTYUNIT = $name;
                $m->save();

                $prodId = str_starts_with($m->TYUNIT, 'BV') ? (int) substr($m->TYUNIT, 2) : null;
                $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $m->NTYUNIT);
                $prodQuery->update([
                    'stock' => $m->stock,
                    'price' => $m->hjual,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stock berhasil disinkronkan']);
    }

    /**
     * Remove the specified resource from storage (Admin only)
     */
    public function destroy(Request $request, string $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang dapat menghapus produk.'
            ], 403);
        }

        $stock = MasterStock::findOrFail($id);
        UserStock::where('tyunit', $stock->TYUNIT)->delete();
        $stock->delete();

        return response()->json([
            'message' => 'Master stock berhasil dihapus.'
        ]);
    }
}
