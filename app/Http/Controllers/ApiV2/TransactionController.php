<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * GET /api-v2/transaksi
     * Admin: view all transactions (optional filter ?user_id=X or ?search=X)
     * Regular User: view ONLY their own transactions
     */
    public function index(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);

        $query = Transaction::with(['items', 'user:id,name,username,email,role'])
            ->orderBy('tanggal', 'desc');

        // Search by ID, Customer Name, or Alamat
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('id_transaksi', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
            });
        }

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
     * GET /api-v2/transaksi/{id}
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
     * POST /api-v2/transaksi
     * Creates transaction, auto-registers new customers to master data, and updates stock pool
     */
    public function store(Request $request)
    {
        try {
            // 1. Flexible Parameter Aliasing for Frontend Compatibility
            // A. Customer Name
            if (!$request->filled('customerName')) {
                $cName = $request->input('customer_name')
                      ?? $request->input('name')
                      ?? $request->input('customer')
                      ?? $request->input('pelanggan');
                if ($cName) {
                    $request->merge(['customerName' => trim($cName)]);
                }
            }

            // B. Alamat (fallback to Gorontalo if empty)
            if (!$request->filled('alamat')) {
                $addr = $request->input('address') ?? 'Gorontalo';
                $request->merge(['alamat' => $addr ?: 'Gorontalo']);
            }

            // C. Date / Tanggal
            if (!$request->filled('date') && $request->filled('tanggal')) {
                $request->merge(['date' => $request->input('tanggal')]);
            }
            if (!$request->filled('date')) {
                $request->merge(['date' => now()->format('Y-m-d H:i:s')]);
            }

            // D. Status
            if (!$request->filled('status')) {
                $request->merge(['status' => 'LUNAS']);
            }

            // E. Auto-generate or deduplicate id_transaksi
            $inputTrxId = $request->input('id_transaksi');
            if (empty($inputTrxId) || Transaction::where('id_transaksi', $inputTrxId)->exists()) {
                $today = date('Ymd');
                $lastTrx = Transaction::where('id_transaksi', 'LIKE', "Trx-{$today}-%")
                    ->orderBy('id_transaksi', 'desc')
                    ->first();
                $nextNum = 1;
                if ($lastTrx && preg_match('/Trx-\d+-(\d+)/', $lastTrx->id_transaksi, $m)) {
                    $nextNum = ((int)$m[1]) + 1;
                }
                $request->merge(['id_transaksi' => sprintf("Trx-%s-%04d", $today, $nextNum)]);
            }

            // 2. Validation
            $validated = $request->validate([
                'id_transaksi'     => 'required|string',
                'customerName'     => 'required|string',
                'alamat'           => 'nullable|string',
                'date'             => 'required',
                'total'            => 'required|numeric',
                'status'           => 'required|string',
                'user_id'          => 'sometimes|nullable|exists:users,id',
                'user_name'        => 'sometimes|nullable|string',

                'items'            => 'required|array|min:1',
                'items.*.tyunit'   => 'required',
                'items.*.price'    => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.bonus'    => 'nullable|integer|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
            ]);

            // Ensure alamat is string
            $validated['alamat'] = !empty($validated['alamat']) ? trim($validated['alamat']) : 'Gorontalo';

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
                $admin = User::where('role', 'admin')->first();
                $userId = $admin ? $admin->id : 1;
                $userName = $admin ? $admin->name : 'Admin';
            }

            $trx = null;

            DB::transaction(function () use ($validated, $userId, $userName, $request, &$trx) {

                // 3. Group required quantities and resolve items
                $groupSalesNeeded = [];
                $resolvedItems = [];

                foreach ($validated['items'] as $item) {
                    $tyunit = (string)$item['tyunit'];

                    // Resolve MasterStock by TYUNIT, or ID, or Product Name
                    $barang = MasterStock::where('TYUNIT', $tyunit)->lockForUpdate()->first();
                    if (!$barang && is_numeric($tyunit)) {
                        $formattedTyunit = sprintf("BV%02d", (int)$tyunit);
                        $barang = MasterStock::where('TYUNIT', $formattedTyunit)->lockForUpdate()->first();
                        if ($barang) {
                            $item['tyunit'] = $formattedTyunit;
                        }
                    }
                    if (!$barang && !empty($item['nama_barang'])) {
                        $barang = MasterStock::where('NTYUNIT', $item['nama_barang'])->lockForUpdate()->first();
                        if ($barang) {
                            $item['tyunit'] = $barang->TYUNIT;
                        }
                    }

                    if (!$barang) {
                        throw new \Exception("Barang {$tyunit} tidak ditemukan");
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

                // 4. Validate Quota against Centralized Stock Pool
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

                // 5. Update Global Stock and UserStock
                foreach ($resolvedItems as $r) {
                    $item = $r['item'];
                    $barang = $r['barang'];
                    $totalKeluar = $r['totalKeluar'];

                    // A. Update Global MasterStock & Product
                    MasterStock::where('TYUNIT', $item['tyunit'])->increment('stock', $totalKeluar);

                    $prodId = str_starts_with($item['tyunit'], 'BV') ? (int) substr($item['tyunit'], 2) : null;
                    $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $barang->NTYUNIT);
                    $prodQuery->increment('stock', $totalKeluar);

                    // B. Sync to UserStock records
                    UserStock::where('tyunit', $item['tyunit'])->increment('stock', $totalKeluar);
                }

                // 6. Create Transaction record
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

                // 7. Create TransactionItem records
                foreach ($resolvedItems as $r) {
                    $item = $r['item'];
                    $barang = $r['barang'];

                    TransactionItem::create([
                        'id_transaksi' => $trx->id_transaksi,
                        'tyunit'       => $item['tyunit'],
                        'nama_barang'  => $barang->NTYUNIT,
                        'harga'        => $item['price'],
                        'quantity'     => $item['quantity'],
                        'bonus'        => $item['bonus'] ?? 0,
                        'subtotal'     => $item['subtotal'],
                    ]);
                }

                // 8. AUTO-REGISTER / SYNC CUSTOMER TO MASTER DATA
                $custName = trim($validated['customerName']);
                if (!empty($custName)) {
                    Customer::firstOrCreate(
                        ['name' => $custName],
                        [
                            'alamat'     => $validated['alamat'],
                            'telepon'    => $request->input('telepon') ?? $request->input('phone') ?? null,
                            'keterangan' => 'Terdaftar otomatis dari transaksi ' . $trx->id_transaksi,
                        ]
                    );
                }
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Transaksi berhasil disimpan',
                'id_transaksi' => $trx ? $trx->id_transaksi : $validated['id_transaksi'],
                'user'         => [
                    'id'   => $userId,
                    'name' => $userName,
                ]
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Transaction store error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api-v2/transaksi/{id}
     * Supports updating customer, alamat, status, and item master variants (e.g. changing from Rp 13.000 to Rp 0)
     */
    public function update(Request $request, $id)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        $trx = Transaction::with('items')->where('id_transaksi', $id)->firstOrFail();

        if ($currentUser && !$currentUser->isAdmin()) {
            if ($trx->user_id && $trx->user_id != $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak memiliki akses untuk mengubah transaksi ini'
                ], 403);
            }
        }

        try {
            DB::transaction(function () use ($request, $trx) {
                $updateData = [];

                $custName = $request->input('customer_name')
                         ?? $request->input('customerName')
                         ?? $request->input('name')
                         ?? $request->input('customer');
                if (!empty($custName)) {
                    $updateData['customer_name'] = trim($custName);
                }

                if ($request->has('alamat')) {
                    $updateData['alamat'] = $request->input('alamat') ?: 'Gorontalo';
                }

                if ($request->has('status')) {
                    $updateData['status'] = $request->input('status');
                }

                if ($request->has('date') || $request->has('tanggal')) {
                    $updateData['tanggal'] = $request->input('date') ?? $request->input('tanggal');
                }

                // Handle item modifications (e.g. changing variant or price to Rp 0)
                if ($request->has('items') && is_array($request->input('items'))) {
                    $rawItems = $request->input('items');

                    // 1. Revert previous stock for existing items
                    foreach ($trx->items as $oldItem) {
                        $totalReturn = (int) $oldItem->quantity + (int) ($oldItem->bonus ?? 0);
                        if ($totalReturn > 0) {
                            MasterStock::where('TYUNIT', $oldItem->tyunit)->decrement('stock', $totalReturn);

                            $prodId = str_starts_with($oldItem->tyunit, 'BV') ? (int) substr($oldItem->tyunit, 2) : null;
                            $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $oldItem->nama_barang);
                            $prodQuery->decrement('stock', $totalReturn);

                            UserStock::where('tyunit', $oldItem->tyunit)->decrement('stock', $totalReturn);
                        }
                    }

                    // 2. Delete existing items
                    TransactionItem::where('id_transaksi', $trx->id_transaksi)->delete();

                    // 3. Insert new items and mutate stock
                    $calculatedTotal = 0;
                    foreach ($rawItems as $rawItem) {
                        $tyunit = (string) ($rawItem['tyunit'] ?? $rawItem['TYUNIT'] ?? '');
                        $barang = MasterStock::where('TYUNIT', $tyunit)->lockForUpdate()->first();

                        if (!$barang && is_numeric($tyunit)) {
                            $formatted = sprintf("BV%02d", (int)$tyunit);
                            $barang = MasterStock::where('TYUNIT', $formatted)->lockForUpdate()->first();
                            if ($barang) {
                                $tyunit = $formatted;
                            }
                        }

                        if (!$barang && !empty($rawItem['nama_barang'])) {
                            $barang = MasterStock::where('NTYUNIT', $rawItem['nama_barang'])->lockForUpdate()->first();
                            if ($barang) {
                                $tyunit = $barang->TYUNIT;
                            }
                        }

                        if (!$barang) {
                            throw new \Exception("Barang {$tyunit} tidak ditemukan");
                        }

                        $qty = (int) ($rawItem['quantity'] ?? $rawItem['qty'] ?? 1);
                        $bonus = (int) ($rawItem['bonus'] ?? 0);
                        $harga = isset($rawItem['price']) ? (float)$rawItem['price'] : (isset($rawItem['harga']) ? (float)$rawItem['harga'] : (float)$barang->hjual);
                        $subtotal = isset($rawItem['subtotal']) ? (float)$rawItem['subtotal'] : ($qty * $harga);

                        $totalKeluar = $qty + $bonus;

                        // Increment new item stock
                        MasterStock::where('TYUNIT', $tyunit)->increment('stock', $totalKeluar);

                        $prodId = str_starts_with($tyunit, 'BV') ? (int) substr($tyunit, 2) : null;
                        $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $barang->NTYUNIT);
                        $prodQuery->increment('stock', $totalKeluar);

                        UserStock::where('tyunit', $tyunit)->increment('stock', $totalKeluar);

                        TransactionItem::create([
                            'id_transaksi' => $trx->id_transaksi,
                            'tyunit'       => $tyunit,
                            'nama_barang'  => $barang->NTYUNIT,
                            'harga'        => $harga,
                            'quantity'     => $qty,
                            'bonus'        => $bonus,
                            'subtotal'     => $subtotal,
                        ]);

                        $calculatedTotal += $subtotal;
                    }

                    $updateData['total'] = $request->has('total') ? (float)$request->input('total') : $calculatedTotal;
                } elseif ($request->has('total')) {
                    $updateData['total'] = (float)$request->input('total');
                }

                if (!empty($updateData)) {
                    $trx->update($updateData);
                }

                if (!empty($updateData['customer_name'])) {
                    Customer::firstOrCreate(
                        ['name' => $updateData['customer_name']],
                        ['alamat' => $updateData['alamat'] ?? $trx->alamat ?? 'Gorontalo']
                    );
                }
            });

            $freshTrx = Transaction::with(['items', 'user:id,name,username,email,role'])
                ->where('id_transaksi', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diupdate',
                'data'    => $freshTrx,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transaction update error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api-v2/transaksi/{id}
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
