<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Gather all unique customers from existing transaksi
        $rows = DB::table('transaksi')
            ->select('customer_name', 'alamat')
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->orderBy('tanggal', 'asc')
            ->get();

        $customerMap = [];
        foreach ($rows as $r) {
            $name = trim($r->customer_name);
            if ($name === '') continue;
            $key = strtolower($name);

            $rawAddr = trim((string)($r->alamat ?? ''));
            $cleanAddr = 'Gorontalo';
            if ($rawAddr !== '' && !in_array(strtolower($rawAddr), ['gtlo', 'gto', 'gtko', 'gorontalo'])) {
                $cleanAddr = $rawAddr;
            }

            if (!isset($customerMap[$key])) {
                $customerMap[$key] = [
                    'name' => $name,
                    'alamat' => $cleanAddr,
                ];
            } else {
                if ($cleanAddr !== 'Gorontalo' && $customerMap[$key]['alamat'] === 'Gorontalo') {
                    $customerMap[$key]['alamat'] = $cleanAddr;
                }
            }
        }

        // 2. Default fallback if transaksi is empty
        if (empty($customerMap)) {
            $defaults = [
                "230 Cafe", "Adira", "Ajama Kopitiam", "Als Cafe", "Anis Naki", "Apotik Hanifa",
                "Arsya Permai", "B-One Mart", "Bima", "Biro Pengadaan Prop Gtlo", "Bisnis Center UBM",
                "Bulan Cake", "Ci Ana", "Combos A Yani", "Combos Limboto", "CombosKasuari",
                "Damhil", "Danny", "DD Mart", "Depan bpjs", "Depot Kai", "Dewi BSM",
                "Dikes hasni", "Dikes Mei", "Dikes Yankes", "El-Madinah", "Galon Pecah",
                "Garuda I donesia", "Gembira Tamalate", "Hasjrat Limboto", "Hasrat Abadi",
                "HSB", "HSB ( Rully)", "Humind", "Hyundai", "Ibu Dian", "Ibu Ella",
                "Ibu Hetty", "Ibu Vita", "K3 Futsal", "Kantin Al-Hijrah", "Kantin SMA 1",
                "Kingdom Gym", "ko heng", "konsumen", "Koperasi Unisan", "Lapak Jaman",
                "Lapak Syifa Limboto", "Mama Cece", "Masuk Rumah", "Mulia Mart",
                "Nasdem Gorontalo", "Pak Iwan", "Pak Kus", "Pengadilan Agama", "PLN",
                "Ronny Mandiri", "rosi BPS", "rumah", "Sampoerna", "San San", "San-San",
                "Sinar Krida", "SKY", "SMP 1", "SMP 2", "SMP 6", "Sofyan Tambipi",
                "Synsana", "TC Motor", "TDT Kota", "Titi", "Tk Plastik Mikel",
                "Toko Karawo Limboto", "Toko Mandiri", "Toko Nues", "Yan Melati",
                "Yudha", "Yudi Telaga", "Yusrin Telaga"
            ];
            foreach ($defaults as $defName) {
                $customerMap[strtolower($defName)] = [
                    'name' => $defName,
                    'alamat' => 'Gorontalo',
                ];
            }
        }

        ksort($customerMap);

        // 3. Upsert into customers table
        foreach ($customerMap as $item) {
            Customer::firstOrCreate(
                ['name' => $item['name']],
                [
                    'alamat' => $item['alamat'],
                    'telepon' => null,
                    'keterangan' => null,
                ]
            );
        }
    }
}
