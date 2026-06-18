<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductTemplateSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function title(): string
    {
        return 'Template';
    }

    public function headings(): array
    {
        return [
            'nama_produk',
            'kategori',
            'barcode',
            'deskripsi',
            'harga_beli',
            'harga_jual',
            'stok_awal',
            'status_aktif',
            'variant_nama',
            'variant_sku',
            'variant_harga_beli',
            'variant_tambahan_harga',
        ];
    }

    public function array(): array
    {
        return [
            // Contoh 1: Produk tanpa variant
            [
                'Nasi Goreng Special',
                'Makanan',
                'NG001',
                'Nasi goreng dengan telur dan ayam',
                12000,
                25000,
                50,
                'Ya',
                '',
                '',
                '',
                '',
            ],
            // Contoh 2: Produk dengan variant - baris pertama
            [
                'Es Teh',
                'Minuman',
                'ET001',
                'Teh manis dingin',
                3000,
                8000,
                100,
                'Ya',
                'Regular',
                'ET001-R',
                3000,
                0,
            ],
            // Contoh 3: Variant kedua dari produk yang sama (barcode sama)
            [
                'Es Teh',
                'Minuman',
                'ET001',
                '',
                '',
                '',
                '',
                '',
                'Jumbo',
                'ET001-J',
                3000,
                3000,
            ],
            // Contoh 4: Produk tanpa barcode, tanpa variant
            [
                'Kerupuk',
                'Snack',
                '',
                'Kerupuk udang',
                2000,
                5000,
                200,
                'Ya',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // nama_produk
            'B' => 15,  // kategori
            'C' => 15,  // barcode
            'D' => 35,  // deskripsi
            'E' => 15,  // harga_beli
            'F' => 15,  // harga_jual
            'G' => 12,  // stok_awal
            'H' => 14,  // status_aktif
            'I' => 18,  // variant_nama
            'J' => 15,  // variant_sku
            'K' => 18,  // variant_harga_beli
            'L' => 22,  // variant_tambahan_harga
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array()) + 1; // +1 for header

        // ── Header row styling ──
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'], // Dark navy blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF4A6FA5'],
                ],
            ],
        ];

        // ── Required columns (A=nama_produk) get red accent on header ──
        $sheet->getStyle('A1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFC62828'], // Red for required
            ],
        ]);

        // ── Data rows styling ──
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD0D5DD'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        // ── Alternate row colors ──
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF7F8FA'],
                    ],
                ]);
            }
        }

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [
            1 => $headerStyle,
            "A2:L{$lastRow}" => $dataStyle,
        ];
    }
}
