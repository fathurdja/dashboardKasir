<?php

namespace App\Filament\Admin\Pages;

use App\Models\PpobTransaction;
use App\Services\IakService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PpobTopup extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.ppob-topup';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'PPOB';
    }

    public static function getNavigationLabel(): string
    {
        return 'Transaksi PPOB';
    }

    public function getTitle(): string| \Illuminate\Contracts\Support\Htmlable
    {
        return 'Transaksi PPOB (Pulsa / Paket Data)';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('customer_number')
                    ->label('Nomor HP / Pelanggan')
                    ->placeholder('Contoh: 081234567890')
                    ->required(),
                Select::make('product_code')
                    ->label('Pilih Produk (Terkoneksi API IAK)')
                    ->searchable()
                    ->options(function () {
                        return Cache::remember('iak_pricelist_options_v2', 3600, function () {
                            try {
                                $iak = new IakService();
                                $response = $iak->getPricelist();
                                $data = $response['data']['pricelist'] ?? $response['data'] ?? [];
                                $options = [];
                                foreach ($data as $item) {
                                    if (($item['status'] ?? '') === 'active') {
                                        $type = strtoupper($item['product_type'] ?? 'LAINNYA');
                                        
                                        $desc = $item['product_description'] ?? $item['product_code'];
                                        $details = $item['product_details'] ?? '';
                                        
                                        $label = $desc;
                                        if (!empty($details) && $details !== '-' && $details !== $desc) {
                                            $label .= " | {$details}";
                                        }
                                        
                                        $price = $item['product_price'] ?? 0;
                                        $label .= ' | Modal: Rp ' . number_format($price, 0, ',', '.');
                                        
                                        // Group by Type (e.g. PULSA, DATA)
                                        $options[$type][$item['product_code']] = $label;
                                    }
                                }
                                return $options;
                            } catch (\Exception $e) {
                                return [];
                            }
                        });
                    })
                    ->required()
                    ->helperText('Daftar ini ditarik langsung dari server IAK (dicache selama 1 jam).'),
                TextInput::make('price')
                    ->label('Harga Jual (ke Pelanggan)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();
        $iak = new IakService();

        // 1. Buat Ref ID unik
        $refId = 'PPOB-' . date('YmdHis') . '-' . Str::random(4);

        // 2. Tembak API IAK Topup
        try {
            $response = $iak->topUp($data['customer_number'], $data['product_code'], $refId);

            // 3. Simpan ke database
            $status = 'PENDING';
            $sn = null;
            $iakPrice = null;

            // Biasanya data ada di response JSON ['data']
            $responseData = $response['data'] ?? $response;

            if (isset($responseData['status'])) {
                if ($responseData['status'] == 1) {
                    $status = 'SUCCESS';
                } elseif ($responseData['status'] == 2) {
                    $status = 'FAILED';
                }
            }

            if (isset($responseData['price'])) {
                $iakPrice = $responseData['price'];
            }
            if (isset($responseData['sn'])) {
                $sn = $responseData['sn'];
            }

            PpobTransaction::create([
                'user_id' => auth()->id(),
                'ref_id' => $refId,
                'customer_number' => $data['customer_number'],
                'product_code' => $data['product_code'],
                'price' => $data['price'],
                'iak_price' => $iakPrice,
                'status' => $status,
                'sn' => $sn,
                'iak_response' => json_encode($response),
            ]);

            Notification::make()
                ->title('Pesanan PPOB Berhasil Dikirim')
                ->body('Status saat ini: ' . $status . '. Cek riwayat untuk detailnya.')
                ->success()
                ->send();

            $this->form->fill();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
