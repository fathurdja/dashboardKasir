<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        DB::transaction(function () use ($request) {

            $trx = Transaction::create([
                'id_transaksi' => $request->id_transaksi,
                'customer_name' => $request->customerName,
                'alamat' => $request->alamat,
                'tanggal' => $request->date,
                'total' => $request->total,
                'status' => $request->status,
            ]);

            foreach ($request->items as $item) {
                TransactionItem::create([
                    'id_transaksi' => $trx->id_transaksi,
                    'tyunit'       => $item['tyunit'],
                    'nama_barang' => $item['name'],
                    'harga' => $item['price'],
                    'quantity' => $item['quantity'],
                    'bonus' => $item['bonus'],
                    'subtotal' => $item['subtotal'],
                ]);
            }
        });

        return response()->json(['message' => 'Transaksi berhasil disimpan'], 201);
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
