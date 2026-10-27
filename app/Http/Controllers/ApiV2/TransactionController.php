<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * GET /api/transaksi
     * Admin: view all transactions (optional filter ?user_id=X)
     * Regular User: view ONLY their own transactions
     */
    public function index(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);

        $query = Transaction::with(['items', 'user:id,name,username,email,role'])
            ->orderBy('tanggal', 'desc');

        if ($currentUser) {
            if (!$currentUser->isAdmin()) {
                // Regular user can only view their own transactions
                $query->where('user_id', $currentUser->id);
            } elseif ($request->filled('user_id')) {
                // Admin filtering by user_id
                $query->where('user_id', $request->input('user_id'));
            }
        } elseif ($request->filled('user_id')) {
            // Unauthenticated with query param
            $query->where('user_id', $request->input('user_id'));
        }

        return response()->json($query->get());
    }

    /**
     * GET /api/transaksi/{id}
     */
    public function show(Request $request, $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);

        $trx = Transaction::with(['items', 'user:id,name,username,email,role'])
            ->where('id_transaksi', $id)
            ->firstOrFail();

        if ($currentUser && !$currentUser->isAdmin()) {
            if ($trx->user_id && $trx->user_id != $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak memiliki akses ke nota transaksi ini'
                ], 403);
            }
        }

        return response()->json($trx);
    }

    /**
     * POST /api/transaksi
     * Creates transaction and updates centralized stock pool
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_transaksi'     => 'required|string',
                'customerName'     => 'required|string',
                'alamat'           => 'required|string',
                'date'             => 'required|date',
                'total'            => 'required|numeric',
                'status'           => 'required|string',
                'user_id'          => 'sometimes|nullable|exists:users,id',
                'user_name'        => 'sometimes|nullable|string',

                'items'            => 'required|array|min:1',
                'items.*.tyunit'   => 'required|string|exists:munit,TYUNIT',
                'items.*.price'    => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.bonus'    => 'required|integer|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
            ]);

            // Resolve acting user
            $currentUser = AuthController::resolveCurrentUser($request);
            $userId = null;
            $userName = null;

            if ($currentUser) {
                $userId = $currentUser->id;
                $userName = $currentUser->name;
            } elseif (!empty($validated['user_id'])) {
                $targetUser = User::find($validated['user_id']);
                if ($targetUser) {
                    $userId = $targetUser->id;
                    $userName = $targetUser->name;
                }
            } else {
                // Default to admin/system if completely unauthenticated
                $admin = User::where('role', 'admin')->first();
                $userId = $admin ? $admin->id : 1;
                $userName = $admin ? $admin->name : 'Admin';
            }

            DB::transaction(function () use ($validated, $userId, $userName) {

                // 1. Group required quantities and resolve items
                $groupSalesNeeded = [];
                $resolvedItems = [];

                foreach ($validated['items'] as $item) {
                    $barang = MasterStock::where('TYUNIT', $item['tyunit'])
                        ->lockForUpdate()
                        ->first();

                    if (!$barang) {
                        throw new \Exception("Barang {$item['tyunit']} tidak ditemukan");
                    }

                    $totalKeluar = (int) $item['quantity'] + (int) ($item['bonus'] ?? 0);
                    $groupKey = !empty($barang->kdklp) ? $barang->kdklp : $barang->TYUNIT;

                    if (!isset($groupSalesNeeded[$groupKey])) {
                        $groupSalesNeeded[$groupKey] = [
                            'needed' => 0,
                            'kdklp'  => $barang->kdklp,
                            'name'   => !empty($barang->kdklp) ? $barang->kdklp : $barang->NTYUNIT,
                        ];
                    }

                    $groupSalesNeeded[$groupKey]['needed'] += $totalKeluar;

                    $resolvedItems[] = [
                        'item'        => $item,
                        'barang'      => $barang,
                        'totalKeluar' => $totalKeluar,
                    ];
                }

                // 2. Validate Quota against Centralized / Global Stock Pool
                foreach ($groupSalesNeeded as $groupKey => $info) {
                    if (!empty($info['kdklp'])) {
                        $kuota = (int) MasterStock::where('kdklp', $info['kdklp'])->value('kuota');
                        if ($kuota > 0) {
                            $currentSold = (int) MasterStock::where('kdklp', $info['kdklp'])->sum('stock');
                            $newTotal = $currentSold + $info['needed'];
                            if ($newTotal > $kuota) {
                                $sisaKuota = max(0, $kuota - $currentSold);
                                throw new \Exception("Stok {$info['name']} tidak mencukupi. Sisa kuota tersedia: {$sisaKuota} unit (dibutuhkan: {$info['needed']} unit)");
                            }
                        }
                    }
                }

                // 3. Update Global Stock and UserStock
                foreach ($resolvedItems as $r) {
                    $item = $r['item'];
                    $barang = $r['barang'];
                    $totalKeluar = $r['totalKeluar'];

                    // A. Update Global MasterStock & Product
                    MasterStock::where('TYUNIT', $item['tyunit'])->increment('stock', $totalKeluar);

                    $prodId = str_starts_with($item['tyunit'], 'BV') ? (int) substr($item['tyunit'], 2) : null;
                    $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $barang->NTYUNIT);
                    $prodQuery->increment('stock', $totalKeluar);

                    // B. Sync to all UserStock records so all users stay identical
                    UserStock::where('tyunit', $item['tyunit'])->increment('stock', $totalKeluar);
                }

                // 4. Create Transaction record with user association
                $trx = Transaction::create([
                    'id_transaksi'  => $validated['id_transaksi'],
                    'user_id'       => $userId,
                    'user_name'     => $userName,
                    'customer_name' => $validated['customerName'],
                    'alamat'        => $validated['alamat'],
                    'tanggal'       => $validated['date'],
                    'total'         => $validated['total'],
                    'status'        => $validated['status'],
                ]);

                // 5. Create TransactionItem records
                foreach ($resolvedItems as $r) {
                    $item = $r['item'];
                    $barang = $r['barang'];

                    TransactionItem::create([
                        'id_transaksi' => $trx->id_transaksi,
                        'tyunit'       => $item['tyunit'],
                        'nama_barang'  => $barang->NTYUNIT,
                        'harga'        => $item['price'],
                        'quantity'     => $item['quantity'],
                        'bonus'        => $item['bonus'],
                        'subtotal'     => $item['subtotal'],
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'user'    => [
                    'id'   => $userId,
                    'name' => $userName,
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/transaksi/{id}
     */
    public function update(Request $request, $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $trx = Transaction::where('id_transaksi', $id)->firstOrFail();

        if ($currentUser && !$currentUser->isAdmin()) {
            if ($trx->user_id && $trx->user_id != $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak memiliki akses untuk mengubah transaksi ini'
                ], 403);
            }
        }

        $trx->update([
            'customer_name' => $request->customer_name ?? $trx->customer_name,
            'alamat'        => $request->alamat ?? $trx->alamat,
            'status'        => $request->status ?? $trx->status,
        ]);

        return response()->json(['message' => 'Transaksi berhasil diupdate']);
    }

    /**
     * DELETE /api/transaksi/{id}
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $trx = Transaction::with('items')->where('id_transaksi', $id)->firstOrFail();

        if ($currentUser && !$currentUser->isAdmin()) {
            if ($trx->user_id && $trx->user_id != $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak dapat menghapus transaksi user lain'
                ], 403);
            }
        }

        DB::transaction(function () use ($trx) {
            foreach ($trx->items as $item) {
                $totalReturn = (int) $item->quantity + (int) ($item->bonus ?? 0);
                $barang = MasterStock::where('TYUNIT', $item->tyunit)->first();
                if ($barang) {
                    // Return global stock
                    MasterStock::where('TYUNIT', $item->tyunit)->decrement('stock', $totalReturn);

                    $prodId = str_starts_with($item->tyunit, 'BV') ? (int) substr($item->tyunit, 2) : null;
                    $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $item->nama_barang);
                    $prodQuery->decrement('stock', $totalReturn);

                    // Return user stock
                    UserStock::where('tyunit', $item->tyunit)->decrement('stock', $totalReturn);
                }
            }

            TransactionItem::where('id_transaksi', $trx->id_transaksi)->delete();
            $trx->delete();
        });

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
