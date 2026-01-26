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
        $data = $request->all();

        // Jika hanya 1 transaksi, bungkus jadi array
        if (isset($data['id_transaksi'])) {
            $data = [$data];
        }

        $request->validate([
            '*.id_transaksi' => 'required|string|distinct',
            '*.customerName' => 'required|string',
            '*.alamat' => 'required|string',
            '*.date' => 'required|date',
            '*.total' => 'required|numeric',
            '*.status' => 'required|string',
            '*.items' => 'required|array|min:1',
            '*.items.*.tyunit' => 'required|string|exists:munit,TYUNIT',
            '*.items.*.price' => 'required|numeric',
            '*.items.*.quantity' => 'required|integer|min:1',
            '*.items.*.bonus' => 'required|integer|min:0',
            '*.items.*.subtotal' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {

            foreach ($data as $trxData) {

                $trx = Transaction::create([
                    'id_transaksi'  => $trxData['id_transaksi'],
                    'customer_name' => $trxData['customerName'],
                    'alamat'        => $trxData['alamat'],
                    'tanggal'       => $trxData['date'],
                    'total'         => $trxData['total'],
                    'status'        => $trxData['status'],
                ]);

                foreach ($trxData['items'] as $item) {

                    // Ambil data barang dari master stock
                    $barang = MasterStock::where('TYUNIT', $item['tyunit'])->firstOrFail();

                    TransactionItem::create([
                        'id_transaksi' => $trx->id_transaksi,
                        'tyunit'       => $item['tyunit'],
                        'nama_barang'  => $barang->NTYUNIT,   // ❗ tidak null
                        'harga'        => $item['price'],
                        'quantity'     => $item['quantity'],
                        'bonus'        => $item['bonus'],
                        'subtotal'     => $item['subtotal'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Semua transaksi berhasil disimpan'], 201);
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
