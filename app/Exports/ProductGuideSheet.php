<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductGuideSheet implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    public function title(): string
    {
        return 'Panduan';
    }

    public function array(): array
    {
        return [
            ['PANDUAN PENGISIAN TEMPLATE IMPORT PRODUK', '', ''],
            ['', '', ''],
            ['Kolom', 'Wajib?', 'Keterangan'],
            ['nama_produk', 'YA ✅', 'Nama produk. Wajib diisi di setiap baris.'],
            ['kategori', 'Tidak', 'Nama kategori. Jika belum ada di database, akan dibuat otomatis.'],
            ['barcode', 'Tidak', 'Kode barcode/SKU unik. Jika sudah ada di database, baris akan di-skip.'],
            ['deskripsi', 'Tidak', 'Deskripsi singkat produk.'],
            ['harga_beli', 'Tidak', 'Harga beli / HPP. Angka tanpa titik ribuan. Default: 0.'],
            ['harga_jual', 'Tidak', 'Harga jual ke pelanggan. Angka tanpa titik ribuan. Default: 0.'],
            ['stok_awal', 'Tidak', 'Jumlah stok awal saat import. Angka bulat. Default: 0.'],
            ['status_aktif', 'Tidak', 'Isi "Ya" atau "Tidak". Default: Ya.'],
            ['', '', ''],
            ['KOLOM VARIANT (Opsional)', '', ''],
            ['variant_nama', 'Tidak', 'Nama variant (contoh: Regular, Jumbo, Ice, Hot).'],
            ['variant_sku', 'Tidak', 'SKU unik untuk variant. Jika sudah ada di database, variant di-skip.'],
            ['variant_harga_beli', 'Tidak', 'Harga beli khusus variant. Default: 0.'],
            ['variant_tambahan_harga', 'Tidak', 'Tambahan harga jual untuk variant ini. Default: 0.'],
            ['', '', ''],
            ['ATURAN PENTING', '', ''],
            ['1.', '', 'Baris pertama di sheet "Template" adalah HEADER. Jangan diubah atau dihapus.'],
            ['2.', '', 'Hapus semua baris contoh sebelum mengisi data Anda.'],
            ['3.', '', 'Jika produk memiliki beberapa variant, buat beberapa baris dengan nama_produk dan barcode yang SAMA.'],
            ['4.', '', 'Hanya baris pertama dari grup produk yang sama yang perlu mengisi kolom produk (nama, harga, stok, dsb).'],
            ['5.', '', 'Baris ke-2 dan seterusnya dari grup yang sama cukup isi kolom variant saja.'],
            ['6.', '', 'Format angka: gunakan angka biasa (contoh: 25000), JANGAN pakai titik ribuan (25.000).'],
            ['7.', '', 'File yang didukung: .xlsx, .xls, .csv'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 12,
            'C' => 80,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge title row
        $sheet->mergeCells('A1:C1');

        $lastRow = count($this->array());

        return [
            // Title
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['argb' => 'FF1E3A5F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Table header (row 3)
            3 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A5F'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF4A6FA5'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Variant section header (row 13)
            13 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FF1E3A5F'],
                ],
            ],
            // Rules section header (row 19)
            19 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FF1E3A5F'],
                ],
            ],
            // Data rows border
            "A4:C11" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD0D5DD'],
                    ],
                ],
            ],
            "A14:C17" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD0D5DD'],
                    ],
                ],
            ],
        ];
    }
}
