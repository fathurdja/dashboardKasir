<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Template' => new ProductTemplateSheet(),
            'Panduan' => new ProductGuideSheet(),
        ];
    }
}
