<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockMonitoring extends Model
{
    use HasFactory;

    protected $table = 'stock_monitorings';

    protected $fillable = [
        'tanggal',
        'kdklp',
        'stock_masuk',
        'stock_terjual',
        'sisa_stock',
        'total_transaksi',
        'total_nominal',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'         => 'date:Y-m-d',
        'stock_masuk'     => 'integer',
        'stock_terjual'   => 'integer',
        'sisa_stock'      => 'integer',
        'total_transaksi' => 'integer',
        'total_nominal'   => 'float',
    ];

    public static $groups = ['GALON', 'BOTOL_600', 'BOTOL_1500', 'GELAS'];
    public static $startDate = '2026-11-04';

    /**
     * Record incoming stock (penambahan stok / stok masuk)
     */
    public static function recordStockMasuk(string $kdklp, int $jumlah, ?string $date = null, string $tipe = 'MASUK', ?string $keterangan = null, ?int $userId = null)
    {
        $group = strtoupper(trim($kdklp));
        $date = $date ?: date('Y-m-d');

        // Current kuota before update
        $currentKuota = (int) MasterStock::where('kdklp', $group)->value('kuota');
        $newKuota = $currentKuota + $jumlah;

        // Log to stock_entries
        StockEntry::create([
            'tanggal'      => $date,
            'kdklp'        => $group,
            'tipe'         => $tipe,
            'jumlah'       => $jumlah,
            'stok_sebelum' => $currentKuota,
            'stok_sesudah' => $newKuota,
            'user_id'      => $userId,
            'keterangan'   => $keterangan,
        ]);

        // Find or create daily monitoring row
        $monitoring = static::firstOrCreate(
            ['tanggal' => $date, 'kdklp' => $group],
            [
                'stock_masuk'     => 0,
                'stock_terjual'   => 0,
                'sisa_stock'      => 0,
                'total_transaksi' => 0,
                'total_nominal'   => 0.00,
                'keterangan'      => $keterangan,
            ]
        );

        $monitoring->stock_masuk += $jumlah;
        $monitoring->sisa_stock = max(0, $monitoring->stock_masuk - $monitoring->stock_terjual);
        if ($keterangan && empty($monitoring->keterangan)) {
            $monitoring->keterangan = $keterangan;
        }
        $monitoring->save();

        return $monitoring;
    }

    /**
     * Synchronize stock terjual from transactions for a given date
     */
    public static function syncDate(string $date)
    {
        $groups = static::$groups;

        // If date is before monitoring start date, zero out everything
        if ($date < static::$startDate) {
            foreach ($groups as $grp) {
                $record = static::firstOrNew([
                    'tanggal' => $date,
                    'kdklp'   => $grp,
                ]);
                $record->stock_masuk = 0;
                $record->stock_terjual = 0;
                $record->sisa_stock = 0;
                $record->total_transaksi = 0;
                $record->total_nominal = 0;
                $record->keterangan = 'Reset (mulai ' . static::$startDate . ')';
                $record->save();
            }
            return;
        }

        // Get sales from transactions for this date per kelompok
        $salesData = DB::table('transaksi_items')
            ->join('transaksi', 'transaksi_items.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('munit', 'transaksi_items.tyunit', '=', 'munit.TYUNIT')
            ->whereDate('transaksi.tanggal', $date)
            ->selectRaw("
                munit.kdklp,
                SUM(transaksi_items.quantity + transaksi_items.bonus) as total_terjual,
                COUNT(DISTINCT transaksi.id_transaksi) as total_transaksi,
                SUM(transaksi_items.subtotal) as total_nominal
            ")
            ->groupBy('munit.kdklp')
            ->get()
            ->keyBy('kdklp');

        foreach ($groups as $grp) {
            $sales = $salesData->get($grp);
            $sold = $sales ? (int) $sales->total_terjual : 0;
            $trxCount = $sales ? (int) $sales->total_transaksi : 0;
            $nominal = $sales ? (float) $sales->total_nominal : 0.0;

            $record = static::firstOrNew([
                'tanggal' => $date,
                'kdklp'   => $grp,
            ]);

            $record->stock_terjual = $sold;
            $record->total_transaksi = $trxCount;
            $record->total_nominal = $nominal;
            $record->sisa_stock = max(0, ((int)$record->stock_masuk) - $sold);
            $record->save();
        }
    }

    /**
     * Resync all dates from transactions
     */
    public static function syncAllDates()
    {
        $dates = DB::table('transaksi')
            ->selectRaw('DISTINCT DATE(tanggal) as tgl')
            ->whereDate('tanggal', '>=', static::$startDate)
            ->orderBy('tgl')
            ->pluck('tgl');

        foreach ($dates as $d) {
            static::syncDate($d);
        }
    }
}
