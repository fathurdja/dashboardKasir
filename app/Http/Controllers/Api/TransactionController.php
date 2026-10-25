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

                // 1. Group requirements by kdklp (or individual item)
                $groupRequirements = [];
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
                    $groupName = !empty($barang->kdklp) ? $barang->kdklp : $barang->NTYUNIT;

                    if (!isset($groupRequirements[$groupKey])) {
                        $groupRequirements[$groupKey] = [
                            'stock' => (int) $barang->stock,
                            'needed' => 0,
                            'name' => $groupName,
                            'kdklp' => $barang->kdklp,
                            'tyunit' => $barang->TYUNIT,
                            'barang_name' => $barang->NTYUNIT,
                        ];
                    }

                    $groupRequirements[$groupKey]['needed'] += $totalKeluar;

                    $resolvedItems[] = [
                        'item' => $item,
                        'barang' => $barang,
                    ];
                }

                // 2. Validate stock sufficiency for all groups
                foreach ($groupRequirements as $key => $req) {
                    if ($req['stock'] < $req['needed']) {
                        throw new \Exception("Stock {$req['name']} tidak mencukupi (Tersedia: {$req['stock']}, Dibutuhkan: {$req['needed']})");
                    }
                }

                // 3. Deduct stock per group across munit and products
                foreach ($groupRequirements as $key => $req) {
                    $deduct = $req['needed'];
                    if (!empty($req['kdklp'])) {
                        MasterStock::where('kdklp', $req['kdklp'])->decrement('stock', $deduct);
                        Product::where('kdklp', $req['kdklp'])->decrement('stock', $deduct);
                    } else {
                        MasterStock::where('TYUNIT', $req['tyunit'])->decrement('stock', $deduct);
                        Product::where('name', $req['barang_name'])->decrement('stock', $deduct);
                    }
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
                    if (!empty($barang->kdklp)) {
                        MasterStock::where('kdklp', $barang->kdklp)->increment('stock', $totalReturn);
                        Product::where('kdklp', $barang->kdklp)->increment('stock', $totalReturn);
                    } else {
                        MasterStock::where('TYUNIT', $barang->TYUNIT)->increment('stock', $totalReturn);
                        Product::where('name', $barang->NTYUNIT)->increment('stock', $totalReturn);
                    }
                }
            }

            TransactionItem::where('id_transaksi', $id)->delete();
            $trx->delete();
        });

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
