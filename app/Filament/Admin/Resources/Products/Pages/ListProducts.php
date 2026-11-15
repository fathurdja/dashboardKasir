<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Exports\ProductTemplateExport;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Imports\ProductImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Excel::download(
                        new ProductTemplateExport(),
                        'template_import_produk.xlsx'
                    );
                }),

            Action::make('importProducts')
                ->label('Import Produk')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText('Format yang didukung: .xlsx, .xls, .csv — Maksimal 5MB')
                        ->maxSize(5120)
                        ->directory('imports/products')
                        ->storeFileNamesIn('original_filename'),
                ])
                ->modalHeading('Import Produk dari Excel')
                ->modalDescription('Upload file Excel sesuai template. Pastikan Anda telah mengunduh dan mengisi template terlebih dahulu.')
                ->modalSubmitActionLabel('Mulai Import')
                ->modalIcon('heroicon-o-arrow-up-tray')
                ->action(function (array $data) {
                    $filePath = $data['file'];

                    try {
                        $import = new ProductImport();
                        Excel::import($import, Storage::path($filePath));

                        $successCount = $import->getSuccessCount();
                        $errors = $import->getErrors();

                        if ($import->hasErrors()) {
                            $errorMessages = implode("\n", array_slice($errors, 0, 10));
                            $moreErrors = count($errors) > 10
                                ? "\n... dan " . (count($errors) - 10) . " error lainnya."
                                : '';

                            Notification::make()
                                ->title("Import selesai dengan catatan")
                                ->body("✅ {$successCount} produk berhasil diimport.\n❌ {$import->getSkipCount()} baris di-skip.\n\nDetail error:\n{$errorMessages}{$moreErrors}")
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Berhasil! 🎉')
                                ->body("✅ {$successCount} produk berhasil diimport ke database.")
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Gagal')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    } finally {
                        // Cleanup uploaded file
                        if (isset($filePath) && Storage::exists($filePath)) {
                            Storage::delete($filePath);
                        }
                    }
                }),

            CreateAction::make(),
        ];
    }
}
