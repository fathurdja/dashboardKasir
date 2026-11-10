<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\MasterStock;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockMonitoring;
use App\Models\Transaction;
use App\Models\UserStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMonitoringController extends Controller
{
    protected static $dayNames = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];

    protected static $monthNames = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    /**
     * GET /api-v2/stock-monitoring
     * Main Monitoring Stock API (supports summary, per_kelompok, daily, and monthly views)
     */
    public function index(Request $request)
    {
        $period = $request->query('period'); // 'daily', 'monthly', or null (all)
        $month = $request->query('month');   // e.g. '2026-11'
        $date = $request->query('date');     // e.g. '2026-11-04'
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $kdklp = $request->query('kdklp') ?? $request->query('kelompok');

        // Always sync today's transaction data to ensure realtime freshness
        $todayStr = date('Y-m-d');
        StockMonitoring::syncDate($todayStr);
        if ($date && $date !== $todayStr) {
            StockMonitoring::syncDate($date);
        }

        // Base query
        $query = StockMonitoring::query();

        if (!empty($kdklp)) {
            $query->where('kdklp', strtoupper(trim($kdklp)));
        }

        if (!empty($date)) {
            $query->whereDate('tanggal', $date);
        } elseif (!empty($month)) {
            $query->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$month])
                  ->whereDate('tanggal', '>=', StockMonitoring::$startDate);
        } else {
            $query->whereDate('tanggal', '>=', !empty($startDate) ? $startDate : StockMonitoring::$startDate);
            if (!empty($endDate)) {
                $query->whereDate('tanggal', '<=', $endDate);
            }
        }

        $allRows = $query->orderBy('tanggal', 'desc')->get();

        // Distinct transactions count queries (prevents double-counting multi-commodity receipts)
        $trxCountsByDate = [];
        $trxCountsByMonth = [];
        $totalTransaksi = 0;

        if (empty($kdklp)) {
            $baseTrx = DB::table('transaksi');
            if (!empty($date)) {
                if ($date === StockMonitoring::$startDate) {
                    $totalTransaksi = 0;
                    $trxCountsByDate = [];
                    $trxCountsByMonth = [];
                } else {
                    $baseTrx->whereDate('tanggal', $date);
                    $totalTransaksi = (int) (clone $baseTrx)->count();
                    $trxCountsByDate = (clone $baseTrx)
                        ->selectRaw("DATE(tanggal) as tgl, COUNT(DISTINCT id_transaksi) as total_trx")
                        ->groupBy('tgl')
                        ->pluck('total_trx', 'tgl')
                        ->toArray();
                }
            } else {
                $baseTrx->whereDate('tanggal', '>', StockMonitoring::$startDate);
                if (!empty($month)) {
                    $baseTrx->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$month]);
                }
                if (!empty($startDate)) {
                    $baseTrx->whereDate('tanggal', '>=', $startDate);
                }
                if (!empty($endDate)) {
                    $baseTrx->whereDate('tanggal', '<=', $endDate);
                }
                $totalTransaksi = (int) (clone $baseTrx)->count();

                $trxCountsByDate = (clone $baseTrx)
                    ->selectRaw("DATE(tanggal) as tgl, COUNT(DISTINCT id_transaksi) as total_trx")
                    ->groupBy('tgl')
                    ->pluck('total_trx', 'tgl')
                    ->toArray();

                $trxCountsByMonth = (clone $baseTrx)
                    ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bln, COUNT(DISTINCT id_transaksi) as total_trx")
                    ->groupBy('bln')
                    ->pluck('total_trx', 'bln')
                    ->toArray();
            }
        } else {
            $totalTransaksi = (int) $allRows->sum('total_transaksi');
        }

        // 2. Summary per Kelompok & Overall Totals
        $perKelompok = [];
        $groups = StockMonitoring::$groups;
        foreach ($groups as $grp) {
            $grpRows = $allRows->where('kdklp', $grp);
            if ($grpRows->isEmpty()) {
                continue;
            }

            if (!empty($date)) {
                // Single day view
                $row = $grpRows->first();
                $gAwal = (int) ($row->stok_awal ?? 0);
                $gTambahan = (int) ($row->stok_tambahan ?? 0);
                $gMasuk = (int) ($row->stock_masuk ?? 0);
                $gTerjual = (int) ($row->stock_terjual ?? 0);
                $gSisa = (int) ($row->sisa_stock ?? 0);
            } else {
                // Multi-day / Range view: opening stock is from earliest day, incoming additions are summed
                $earliest = $grpRows->sortBy('tanggal')->first();
                $latest = $grpRows->sortByDesc('tanggal')->first();
                $gAwal = $earliest ? (int) $earliest->stok_awal : 0;
                $gTambahan = (int) $grpRows->sum('stok_tambahan');
                $gMasuk = $gAwal + $gTambahan;
                $gTerjual = (int) $grpRows->sum('stock_terjual');
                $gSisa = $latest ? (int) $latest->sisa_stock : max(0, $gMasuk - $gTerjual);
            }

            $perKelompok[] = [
                'kdklp'           => $grp,
                'stok_awal'       => $gAwal,
                'stok_tambahan'   => $gTambahan,
                'stock_masuk'     => $gMasuk,
                'stock_terjual'   => $gTerjual,
                'sisa_stock'      => $gSisa,
                'total_transaksi' => (int) $grpRows->sum('total_transaksi'),
                'total_nominal'   => (float) $grpRows->sum('total_nominal'),
            ];
        }

        // 1. Overall Summary (aggregated from groups to avoid double counting multi-day carryovers)
        $totalMasuk = (int) collect($perKelompok)->sum('stock_masuk');
        $totalTerjual = (int) collect($perKelompok)->sum('stock_terjual');
        $sisaStock = (int) collect($perKelompok)->sum('sisa_stock');
        $totalNominal = (float) $allRows->sum('total_nominal');

        // 3. Daily Breakdown
        $daily = [];
        $groupedByDate = $allRows->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m-d');
        });

        foreach ($groupedByDate as $tgl => $rows) {
            $cDate = Carbon::parse($tgl);
            $dayEn = $cDate->format('l');
            $dayId = self::$dayNames[$dayEn] ?? $dayEn;

            $dAwal = (int) $rows->sum('stok_awal');
            $dTambahan = (int) $rows->sum('stok_tambahan');
            $dMasuk = (int) $rows->sum('stock_masuk');
            $dTerjual = (int) $rows->sum('stock_terjual');
            $dTrx = empty($kdklp) 
                ? (int) ($trxCountsByDate[$tgl] ?? $rows->sum('total_transaksi'))
                : (int) $rows->sum('total_transaksi');
            $dNominal = (float) $rows->sum('total_nominal');

            $details = [];
            foreach ($groups as $grp) {
                $item = $rows->firstWhere('kdklp', $grp);
                $details[] = [
                    'kdklp'           => $grp,
                    'stok_awal'       => $item ? (int)$item->stok_awal : 0,
                    'stok_tambahan'   => $item ? (int)$item->stok_tambahan : 0,
                    'stock_masuk'     => $item ? (int)$item->stock_masuk : 0,
                    'stock_terjual'   => $item ? (int)$item->stock_terjual : 0,
                    'sisa_stock'      => $item ? (int)$item->sisa_stock : 0,
                    'total_transaksi' => $item ? (int)$item->total_transaksi : 0,
                    'total_nominal'   => $item ? (float)$item->total_nominal : 0.0,
                    'keterangan'      => $item ? $item->keterangan : null,
                ];
            }

            $daily[] = [
                'tanggal'         => $tgl,
                'hari'            => $dayId,
                'formatted'       => $cDate->format('d/m/Y'),
                'stok_awal'       => $dAwal,
                'stok_tambahan'   => $dTambahan,
                'total_masuk'     => $dMasuk,
                'total_terjual'   => $dTerjual,
                'sisa'            => max(0, $dMasuk - $dTerjual),
                'total_transaksi' => $dTrx,
                'total_nominal'   => $dNominal,
                'rincian'         => $details,
            ];
        }

        // 4. Monthly Breakdown
        $monthly = [];
        $groupedByMonth = $allRows->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        });

        foreach ($groupedByMonth as $bln => $mRows) {
            list($year, $monthNum) = explode('-', $bln);
            $monthName = self::$monthNames[$monthNum] ?? $monthNum;
            $mDetails = [];
            foreach ($groups as $grp) {
                $subRows = $mRows->where('kdklp', $grp);
                $earliest = $subRows->sortBy('tanggal')->first();
                $latest = $subRows->sortByDesc('tanggal')->first();
                $smAwal = $earliest ? (int) $earliest->stok_awal : 0;
                $smTambahan = (int) $subRows->sum('stok_tambahan');
                $smMasuk = $smAwal + $smTambahan;
                $smTerjual = (int) $subRows->sum('stock_terjual');
                $smSisa = $latest ? (int) $latest->sisa_stock : max(0, $smMasuk - $smTerjual);
                $mDetails[] = [
                    'kdklp'           => $grp,
                    'stok_awal'       => $smAwal,
                    'stok_tambahan'   => $smTambahan,
                    'stock_masuk'     => $smMasuk,
                    'stock_terjual'   => $smTerjual,
                    'sisa_stock'      => $smSisa,
                    'total_transaksi' => (int) $subRows->sum('total_transaksi'),
                    'total_nominal'   => (float) $subRows->sum('total_nominal'),
                ];
            }

            $mAwal = (int) collect($mDetails)->sum('stok_awal');
            $mTambahan = (int) collect($mDetails)->sum('stok_tambahan');
            $mMasuk = $mAwal + $mTambahan;
            $mTerjual = (int) collect($mDetails)->sum('stock_terjual');
            $mSisa = (int) collect($mDetails)->sum('sisa_stock');
            $mTrx = empty($kdklp)
                ? (int) ($trxCountsByMonth[$bln] ?? $mRows->sum('total_transaksi'))
                : (int) $mRows->sum('total_transaksi');
            $mNominal = (float) $mRows->sum('total_nominal');

            $monthly[] = [
                'bulan'           => $bln,
                'nama_bulan'      => "{$monthName} {$year}",
                'stok_awal'       => $mAwal,
                'stok_tambahan'   => $mTambahan,
                'total_masuk'     => $mMasuk,
                'total_terjual'   => $mTerjual,
                'sisa'            => $mSisa,
                'total_transaksi' => $mTrx,
                'total_nominal'   => $mNominal,
                'rincian'         => $mDetails,
            ];
        }

        $response = [
            'success' => true,
            'message' => 'Data monitoring stok berhasil diambil',
            'summary' => [
                'total_stock_masuk'   => $totalMasuk,
                'total_stock_terjual' => $totalTerjual,
                'sisa_stock'          => $sisaStock,
                'total_transaksi'     => $totalTransaksi,
                'total_nominal'       => $totalNominal,
            ],
            'per_kelompok' => $perKelompok,
        ];

        if ($period === 'daily') {
            $response['data'] = $daily;
        } elseif ($period === 'monthly') {
            $response['data'] = $monthly;
        } else {
            $response['daily'] = $daily;
            $response['monthly'] = $monthly;
        }

        return response()->json($response);
    }

    /**
     * GET /api-v2/stock-monitoring/daily
     */
    public function daily(Request $request)
    {
        $request->merge(['period' => 'daily']);
        return $this->index($request);
    }

    /**
     * GET /api-v2/stock-monitoring/monthly
     */
    public function monthly(Request $request)
    {
        $request->merge(['period' => 'monthly']);
        return $this->index($request);
    }

    /**
     * POST /api-v2/stock-monitoring/masuk or /api-v2/stock-masuk
     * Admin input incoming stock (stok masuk)
     */
    public function stockMasuk(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang dapat menginput stok masuk.'
            ], 403);
        }

        $validated = $request->validate([
            'kdklp'      => 'sometimes|string',
            'kelompok'   => 'sometimes|string',
            'category'   => 'sometimes|string',
            'jumlah'     => 'required|integer|min:1',
            'tanggal'    => 'sometimes|date',
            'keterangan' => 'sometimes|nullable|string',
        ]);

        $group = strtoupper(trim($validated['kdklp'] ?? $validated['kelompok'] ?? $validated['category'] ?? 'GALON'));
        $match = MasterStock::where('kdklp', $group)->first();
        if (!$match) {
            $match = MasterStock::where('kdklp', 'LIKE', "%{$group}%")->first();
            if ($match) {
                $group = $match->kdklp;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Kelompok {$group} tidak ditemukan. Pilihan: GALON, BOTOL_600, BOTOL_1500, GELAS."
                ], 404);
            }
        }

        $jumlah = (int) $validated['jumlah'];
        $date = !empty($validated['tanggal']) ? Carbon::parse($validated['tanggal'])->format('Y-m-d') : date('Y-m-d');
        $ket = $validated['keterangan'] ?? "Stok masuk {$group} (+{$jumlah} unit)";
        $userId = $currentUser ? $currentUser->id : 1;

        // 1. Record stock masuk to StockMonitoring & StockEntry
        $monitoring = StockMonitoring::recordStockMasuk($group, $jumlah, $date, 'MASUK', $ket, $userId);

        // 2. Increment kuota in MasterStock, Product, UserStock
        MasterStock::where('kdklp', $group)->increment('kuota', $jumlah);
        Product::where('kdklp', $group)->increment('kuota', $jumlah);
        UserStock::where('kdklp', $group)->increment('kuota', $jumlah);

        // Re-sync date for accuracy
        StockMonitoring::syncDate($date);

        $currentKuota = (int) MasterStock::where('kdklp', $group)->value('kuota');
        $totalTerjual = (int) MasterStock::where('kdklp', $group)->sum('stock');
        $sisaKuota = max(0, $currentKuota - $totalTerjual);

        return response()->json([
            'success' => true,
            'message' => "Stok masuk sebanyak {$jumlah} unit untuk {$group} berhasil dicatat pada tanggal {$date}",
            'data' => [
                'tanggal'       => $date,
                'kelompok'      => $group,
                'jumlah_masuk'  => $jumlah,
                'total_masuk'   => (int) $monitoring->fresh()->stock_masuk,
                'kuota_global'  => $currentKuota,
                'total_terjual' => $totalTerjual,
                'sisa_kuota'    => $sisaKuota,
                'keterangan'    => $ket,
            ]
        ], 201);
    }

    /**
     * POST /api-v2/stock-monitoring/sync
     * Admin recalculates and resyncs monitoring table from all transactions
     */
    public function sync(Request $request)
    {
        $currentUser = AuthController::resolveCurrentUser($request);
        if ($currentUser && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya Admin yang dapat melakukan sinkronisasi data.'
            ], 403);
        }

        StockMonitoring::syncAllDates();

        return response()->json([
            'success' => true,
            'message' => 'Seluruh data monitoring stok berhasil disinkronisasikan dari transaksi.',
            'total_dates' => StockMonitoring::distinct('tanggal')->count('tanggal'),
            'total_rows'  => StockMonitoring::count(),
        ]);
    }
}
