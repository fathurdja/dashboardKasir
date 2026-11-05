<?php

namespace App\Models;

use Carbon\Carbon;
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
        'stok_awal',
        'stok_tambahan',
        'stock_masuk',
        'stock_terjual',
        'sisa_stock',
        'total_transaksi',
        'total_nominal',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'         => 'date:Y-m-d',
        'stok_awal'       => 'integer',
        'stok_tambahan'   => 'integer',
        'stock_masuk'     => 'integer',
        'stock_terjual'   => 'integer',
        'sisa_stock'      => 'integer',
        'total_transaksi' => 'integer',
        'total_nominal'   => 'float',
    ];

    public static $groups = ['GALON', 'BOTOL_600', 'BOTOL_1500', 'GELAS'];
    public static $startDate = '2026-11-05';

    /**
     * Get baseline physical remaining stock from MasterStock (munit)
     */
    public static function getPhysicalRemainingStock(string $kdklp): int
    {
        $group = strtoupper(trim($kdklp));
        $kuota = (int) MasterStock::where('kdklp', $group)->value('kuota');
        $terjual = (int) MasterStock::where('kdklp', $group)->sum('stock');
        return max(0, $kuota - $terjual);
    }

    /**
     * Ensure monitoring records exist for a given date.
     * If records do not exist, automatically carry over yesterday's sisa_stock as today's stok_awal!
     */
    public static function ensureDateInitialized(string $date)
    {
        $groups = static::$groups;

        // If before start date, keep zeroed
        if ($date < static::$startDate) {
            foreach ($groups as $grp) {
                static::updateOrCreate(
                    ['tanggal' => $date, 'kdklp' => $grp],
                    [
                        'stok_awal'       => 0,
                        'stok_tambahan'   => 0,
                        'stock_masuk'     => 0,
                        'stock_terjual'   => 0,
                        'sisa_stock'      => 0,
                        'total_transaksi' => 0,
                        'total_nominal'   => 0.00,
                        'keterangan'      => 'Reset (mulai ' . static::$startDate . ')',
                    ]
                );
            }
            return;
        }

        $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        foreach ($groups as $grp) {
            $record = static::where('tanggal', $date)->where('kdklp', $grp)->first();

            if (!$record) {
                // Determine opening carryover stock
                $carryover = 0;
                if ($prevDate >= static::$startDate) {
                    $prevRecord = static::where('tanggal', $prevDate)->where('kdklp', $grp)->first();
                    if ($prevRecord) {
                        $carryover = (int) $prevRecord->sisa_stock;
                    }
                }

                // If no valid previous record (or starting baseline on start date), use current physical stock
                if ($carryover === 0 && $date === static::$startDate) {
                    $carryover = static::getPhysicalRemainingStock($grp);
                }

                // Any incoming entries already recorded for this date?
                $tambahan = (int) StockEntry::where('tanggal', $date)
                    ->where('kdklp', $grp)
                    ->whereIn('tipe', ['MASUK', 'SET_AWAL'])
                    ->sum('jumlah');

                $totalMasuk = $carryover + $tambahan;

                $record = static::create([
                    'tanggal'         => $date,
                    'kdklp'           => $grp,
                    'stok_awal'       => $carryover,
                    'stok_tambahan'   => $tambahan,
                    'stock_masuk'     => $totalMasuk,
                    'stock_terjual'   => 0,
                    'sisa_stock'      => $totalMasuk,
                    'total_transaksi' => 0,
                    'total_nominal'   => 0.00,
                    'keterangan'      => $tambahan > 0 
                        ? "Bawaan sisa ({$carryover}) + tambahan masuk ({$tambahan})"
                        : "Bawaan sisa kemarin ({$carryover})",
                ]);
            }
        }
    }

    /**
     * Rollover stock at midnight:
     * Yesterday's sisa_stock becomes today's stok_awal!
     */
    public static function rollover(?string $targetDate = null)
    {
        $today = $targetDate ?: date('Y-m-d');
        $yesterday = Carbon::parse($today)->subDay()->format('Y-m-d');

        // Sync yesterday's transactions first to make sure ending stock is accurate
        if ($yesterday >= static::$startDate) {
            static::syncDate($yesterday);
        }

        $groups = static::$groups;
        foreach ($groups as $grp) {
            $carryover = 0;
            if ($yesterday >= static::$startDate) {
                $prevRecord = static::where('tanggal', $yesterday)->where('kdklp', $grp)->first();
                if ($prevRecord) {
                    $carryover = (int) $prevRecord->sisa_stock;
                }
            } else {
                $carryover = static::getPhysicalRemainingStock($grp);
            }

            $record = static::firstOrNew([
                'tanggal' => $today,
                'kdklp'   => $grp,
            ]);

            $record->stok_awal = $carryover;
            $tambahan = (int) ($record->stok_tambahan ?? 0);
            $record->stock_masuk = $carryover + $tambahan;
            $record->keterangan = $tambahan > 0
                ? "Bawaan sisa ({$carryover}) + tambahan masuk ({$tambahan})"
                : "Bawaan sisa kemarin ({$carryover})";
            $record->save();
        }

        // Re-sync today's transactions
        static::syncDate($today);
    }

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

        // Ensure date records initialized
        static::ensureDateInitialized($date);

        $record = static::firstOrNew([
            'tanggal' => $date,
            'kdklp'   => $group,
        ]);

        $record->stok_tambahan = ((int) $record->stok_tambahan) + $jumlah;
        $record->stock_masuk = ((int) $record->stok_awal) + $record->stok_tambahan;
        $record->sisa_stock = max(0, $record->stock_masuk - ((int) $record->stock_terjual));
        if ($keterangan) {
            $record->keterangan = $keterangan;
        }
        $record->save();

        return $record;
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
                static::updateOrCreate(
                    ['tanggal' => $date, 'kdklp' => $grp],
                    [
                        'stok_awal'       => 0,
                        'stok_tambahan'   => 0,
                        'stock_masuk'     => 0,
                        'stock_terjual'   => 0,
                        'sisa_stock'      => 0,
                        'total_transaksi' => 0,
                        'total_nominal'   => 0.00,
                        'keterangan'      => 'Reset (mulai ' . static::$startDate . ')',
                    ]
                );
            }
            return;
        }

        // Ensure record is initialized first so carryover is set
        static::ensureDateInitialized($date);

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

            // Recompute total stock_masuk from stok_awal + stok_tambahan
            $stokAwal = (int) ($record->stok_awal ?? 0);
            $stokTambahan = (int) ($record->stok_tambahan ?? 0);
            $totalMasuk = $stokAwal + $stokTambahan;

            $record->stock_masuk = $totalMasuk;
            $record->stock_terjual = $sold;
            $record->total_transaksi = $trxCount;
            $record->total_nominal = $nominal;
            $record->sisa_stock = max(0, $totalMasuk - $sold);
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
