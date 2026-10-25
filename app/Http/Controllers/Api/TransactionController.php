<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // GET
    public function index()
    {
        return Transaction::with('items')
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    // GET by ID
    public function show($id)
    {
        return Transaction::with('items')
            ->where('id_transaksi', $id)
            ->firstOrFail();
    }

    // POST
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_transaksi' => 'required|string',
                'customerName' => 'required|string',
                'alamat' => 'required|string',
                'date' => 'required|date',
                'total' => 'required|numeric',
                'status' => 'required|string',

                'items' => 'required|array|min:1',
                'items.*.tyunit' => 'required|string|exists:munit,TYUNIT',
                'items.*.price' => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.bonus' => 'required|integer|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
            ]);

            DB::transaction(function () use ($validated) {

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
                        'item'   => $item,
                        'barang' => $barang,
                        'totalKeluar' => $totalKeluar,
                    ];
                }

                // 2. Validate Quota (if kuota is configured > 0)
                foreach ($groupSalesNeeded as $groupKey => $info) {
                    if (!empty($info['kdklp'])) {
                        $kuota = (int) MasterStock::where('kdklp', $info['kdklp'])->value('kuota');
                        if ($kuota > 0) {
                            $currentSold = (int) MasterStock::where('kdklp', $info['kdklp'])->sum('stock');
                            $newTotal = $currentSold + $info['needed'];
                            if ($newTotal > $kuota) {
                                $sisaKuota = max(0, $kuota - $currentSold);
                                throw new \Exception("Total penjualan {$info['name']} ({$newTotal}) melebihi kuota {$kuota}. Sisa kuota tersedia: {$sisaKuota} unit");
                            }
                        }
                    }
                }

                // 3. Update individual product sales count (stock)
                foreach ($resolvedItems as $r) {
                    $item = $r['item'];
                    $barang = $r['barang'];
                    $totalKeluar = $r['totalKeluar'];

                    MasterStock::where('TYUNIT', $item['tyunit'])->increment('stock', $totalKeluar);

                    $prodId = str_starts_with($item['tyunit'], 'BV') ? (int) substr($item['tyunit'], 2) : null;
                    $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $barang->NTYUNIT);
                    $prodQuery->increment('stock', $totalKeluar);
                }

                // 4. Create Transaction record
                $trx = Transaction::create([
                    'id_transaksi'  => $validated['id_transaksi'],
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
                'message' => 'Transaksi berhasil disimpan'
            ], 201);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }





    // UPDATE
    public function update(Request $request, $id)
    {
        $trx = Transaction::where('id_transaksi', $id)->firstOrFail();

        $trx->update([
            'customer_name' => $request->customer_name,
            'alamat' => $request->alamat,
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'Transaksi berhasil diupdate']);
    }

    // DELETE
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $trx = Transaction::with('items')->where('id_transaksi', $id)->firstOrFail();

            foreach ($trx->items as $item) {
                $totalReturn = (int) $item->quantity + (int) ($item->bonus ?? 0);
                $barang = MasterStock::where('TYUNIT', $item->tyunit)->first();
                if ($barang) {
                    MasterStock::where('TYUNIT', $item->tyunit)->decrement('stock', $totalReturn);

                    $prodId = str_starts_with($item->tyunit, 'BV') ? (int) substr($item->tyunit, 2) : null;
                    $prodQuery = $prodId ? Product::where('id', $prodId) : Product::where('name', $item->nama_barang);
                    $prodQuery->decrement('stock', $totalReturn);
                }
            }

            TransactionItem::where('id_transaksi', $id)->delete();
            $trx->delete();
        });

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
