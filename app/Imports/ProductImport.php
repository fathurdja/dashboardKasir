<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToCollection, WithHeadingRow
{
    protected int $successCount = 0;
    protected int $skipCount = 0;
    protected array $errors = [];

    /**
     * Category cache: lowercased name => Category model
     */
    protected array $categoryCache = [];

    public function collection(Collection $rows): void
    {
        // Pre-load all categories into cache
        Category::all()->each(function (Category $cat) {
            $this->categoryCache[Str::lower(trim($cat->name))] = $cat;
        });

        // Group rows by product identity (barcode if present, else nama_produk)
        // This allows multiple rows for the same product to add variants
        $grouped = $this->groupRows($rows);

        foreach ($grouped as $groupKey => $groupedRows) {
            $this->processProductGroup($groupKey, $groupedRows);
        }
    }

    /**
     * Group rows by product identity.
     * If a row has a barcode, group by barcode.
     * Otherwise, group by nama_produk.
     */
    protected function groupRows(Collection $rows): array
    {
        $groups = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because index is 0-based and row 1 is header

            $name = $this->cleanString($row['nama_produk'] ?? null);

            if (empty($name)) {
                $this->errors[] = "Baris {$rowNum}: Kolom 'nama_produk' wajib diisi.";
                $this->skipCount++;
                continue;
            }

            // Determine group key
            $barcode = $this->cleanString($row['barcode'] ?? null);
            $groupKey = !empty($barcode) ? "barcode:{$barcode}" : "name:{$name}";

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = [
                'row_number' => $rowNum,
                'data' => $row,
            ];
        }

        return $groups;
    }

    /**
     * Process a group of rows that belong to the same product.
     */
    protected function processProductGroup(string $groupKey, array $rows): void
    {
        $firstRow = $rows[0]['data'];
        $firstRowNum = $rows[0]['row_number'];

        $name = $this->cleanString($firstRow['nama_produk'] ?? null);
        $barcode = $this->cleanString($firstRow['barcode'] ?? null);

        // ── Validate barcode uniqueness in database ──
        if (!empty($barcode)) {
            $existing = Product::withTrashed()->where('barcode', $barcode)->first();
            if ($existing) {
                $rowNums = implode(', ', array_column($rows, 'row_number'));
                $this->errors[] = "Baris {$rowNums}: Barcode '{$barcode}' sudah ada di database (produk: {$existing->name}). Baris ini di-skip.";
                $this->skipCount += count($rows);
                return;
            }
        }

        // ── Validate prices ──
        $purchasePrice = $this->parseNumber($firstRow['harga_beli'] ?? 0);
        $sellingPrice = $this->parseNumber($firstRow['harga_jual'] ?? 0);

        if ($purchasePrice === false) {
            $this->errors[] = "Baris {$firstRowNum}: 'harga_beli' harus berupa angka.";
            $this->skipCount += count($rows);
            return;
        }

        if ($sellingPrice === false) {
            $this->errors[] = "Baris {$firstRowNum}: 'harga_jual' harus berupa angka.";
            $this->skipCount += count($rows);
            return;
        }

        // ── Resolve category ──
        $categoryId = null;
        $categoryName = $this->cleanString($firstRow['kategori'] ?? null);
        if (!empty($categoryName)) {
            $categoryId = $this->resolveCategory($categoryName);
        }

        // ── Resolve is_active ──
        $isActive = $this->parseBoolean($firstRow['status_aktif'] ?? 'Ya');

        // ── Create product ──
        try {
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $this->cleanString($firstRow['deskripsi'] ?? null),
                'barcode' => $barcode ?: null,
                'purchase_price' => $purchasePrice,
                'price' => $sellingPrice,
                'is_active' => $isActive,
            ]);
        } catch (\Exception $e) {
            $this->errors[] = "Baris {$firstRowNum}: Gagal membuat produk '{$name}'. Error: {$e->getMessage()}";
            $this->skipCount += count($rows);
            return;
        }

        // ── Create initial stock ──
        $initialStock = (int) ($this->parseNumber($firstRow['stok_awal'] ?? 0) ?: 0);
        if ($initialStock > 0) {
            StockTransaction::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $initialStock,
                'notes' => 'Stok awal (import Excel)',
            ]);
        }

        // ── Create variants from all rows in this group ──
        foreach ($rows as $rowEntry) {
            $row = $rowEntry['data'];
            $rowNum = $rowEntry['row_number'];

            $variantName = $this->cleanString($row['variant_nama'] ?? null);
            if (empty($variantName)) {
                continue; // No variant in this row
            }

            $variantSku = $this->cleanString($row['variant_sku'] ?? null);

            // Check SKU uniqueness
            if (!empty($variantSku)) {
                $existingSku = ProductVariant::withTrashed()->where('sku', $variantSku)->first();
                if ($existingSku) {
                    $this->errors[] = "Baris {$rowNum}: Variant SKU '{$variantSku}' sudah ada di database. Variant ini di-skip.";
                    continue;
                }
            }

            $variantPurchasePrice = $this->parseNumber($row['variant_harga_beli'] ?? 0);
            $variantAdditionalPrice = $this->parseNumber($row['variant_tambahan_harga'] ?? 0);

            if ($variantPurchasePrice === false) {
                $this->errors[] = "Baris {$rowNum}: 'variant_harga_beli' harus berupa angka.";
                continue;
            }
            if ($variantAdditionalPrice === false) {
                $this->errors[] = "Baris {$rowNum}: 'variant_tambahan_harga' harus berupa angka.";
                continue;
            }

            try {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variantName,
                    'sku' => $variantSku ?: null,
                    'purchase_price' => $variantPurchasePrice ?: 0,
                    'additional_price' => $variantAdditionalPrice ?: 0,
                ]);
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNum}: Gagal membuat variant '{$variantName}'. Error: {$e->getMessage()}";
            }
        }

        $this->successCount++;
    }

    /**
     * Resolve category by name, creating it if it doesn't exist.
     */
    protected function resolveCategory(string $name): ?string
    {
        $key = Str::lower(trim($name));

        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key]->id;
        }

        // Auto-create category
        $category = Category::create([
            'name' => trim($name),
            'slug' => Str::slug($name),
        ]);

        $this->categoryCache[$key] = $category;

        return $category->id;
    }

    // ── Helpers ──

    protected function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);
        return $cleaned === '' ? null : $cleaned;
    }

    protected function parseNumber(mixed $value): float|false
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Remove thousand separators (dots) and replace comma with dot for decimal
        $cleaned = str_replace(['.', ','], ['', '.'], (string) $value);

        if (!is_numeric($cleaned)) {
            return false;
        }

        return (float) $cleaned;
    }

    protected function parseBoolean(mixed $value): bool
    {
        $val = Str::lower(trim((string) $value));
        return !in_array($val, ['tidak', 'no', '0', 'false', 'non-aktif', 'nonaktif'], true);
    }

    // ── Result Getters ──

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
