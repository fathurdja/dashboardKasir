<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
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

                $trx = Transaction::create([
                    'id_transaksi'  => $validated['id_transaksi'],
                    'customer_name' => $validated['customerName'],
                    'alamat'        => $validated['alamat'],
                    'tanggal'       => $validated['date'],
                    'total'         => $validated['total'],
                    'status'        => $validated['status'],
                ]);

                foreach ($validated['items'] as $item) {

                    $barang = MasterStock::where('TYUNIT', $item['tyunit'])
                        ->lockForUpdate()
                        ->first();

                    if (!$barang) {
                        throw new \Exception("Barang {$item['tyunit']} tidak ditemukan");
                    }

                    $totalKeluar = $item['quantity'] + $item['bonus'];

                    if ($barang->stock < $totalKeluar) {
                        throw new \Exception("Stock {$item['tyunit']} tidak mencukupi");
                    }

                    $barang->decrement('stock', $totalKeluar);

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
            TransactionItem::where('id_transaksi', $id)->delete();
            Transaction::where('id_transaksi', $id)->delete();
        });

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
