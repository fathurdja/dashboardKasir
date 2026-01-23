<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Models\MasterStock;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{
    Section,
    Grid,
    TextInput,
    Select,
    DateTimePicker,
    Repeater
};
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Section as ComponentsSection;
use Illuminate\Support\Carbon;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            // ================= TRANSAKSI =================
            ComponentsSection::make('Informasi Transaksi')
                ->schema([
                    ComponentsGrid::make(2)->schema([
                        TextInput::make('id_transaksi')
                            ->label('ID Transaksi')
                            ->required()
                            ->readOnly() // bukan disabled
                            ->default(function () {
                                $tanggal = Carbon::now()->format('Ymd');

                                $last = Transaction::whereDate('tanggal', Carbon::today())
                                    ->latest('id')
                                    ->first();

                                $urutan = $last
                                    ? str_pad(((int) substr($last->id_transaksi, 4, 3)) + 1, 3, '0', STR_PAD_LEFT)
                                    : '001';

                                return "TRX-{$urutan}-{$tanggal}";
                            }),

                        TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required(),

                        TextInput::make('alamat')
                            ->label('Alamat'),

                        DateTimePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'LUNAS' => 'LUNAS',
                                'BON'   => 'BON',
                            ])
                            ->required(),
                    ]),
                ])
                ->columnSpanFull(),

            // ================= DAFTAR BARANG (BAWAH) =================
            ComponentsSection::make('Daftar Barang')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->label('')
                        ->schema([
                            ComponentsGrid::make(12)->schema([

                                Select::make('tyunit')
                                    ->label('Barang')
                                    ->options(
                                        MasterStock::query()->pluck('NTYUNIT', 'TYUNIT')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(6)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {

                                        $barang = MasterStock::find($state);

                                        $set('harga', $barang?->hjual ?? 0);
                                        $set('bonus', 0);
                                        $set('quantity', 1);
                                        $set('subtotal', $barang?->hjual ?? 0);
                                    }),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn($state, $set, $get) =>
                                        self::hitungSubtotal($set, $get)
                                    ),

                                TextInput::make('harga')
                                    ->label('Harga')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->columnSpan(2),


                                TextInput::make('bonus')
                                    ->label('Bonus')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {

                                        $qty    = $get('quantity') ?? 0;
                                        $bonus  = $get('bonus') ?? 0;
                                        $harga  = $get('harga') ?? 0;

                                        $subtotal = $qty * $harga;
                                        $set('subtotal', $subtotal);

                                        $items = $get('../../items') ?? [];
                                        $total = collect($items)->sum(fn($i) => $i['subtotal'] ?? 0);
                                        $set('../../total', $total);
                                    }),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->columnSpan(2),

                            ]),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Tambah Barang')
                        ->columnSpanFull()
                ])
                ->columnSpanFull(),

            // ================= TOTAL =================
            ComponentsSection::make()
                ->schema([
                    ComponentsGrid::make(3)->schema([
                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(),

                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function hitungSubtotal($set, $get): void
    {
        $qty   = $get('quantity') ?? 0;
        $bonus = $get('bonus') ?? 0;
        $harga = $get('harga') ?? 0;

        $subtotal = $qty * $harga;
        $set('subtotal', $subtotal);

        $items = $get('../../items') ?? [];
        $total = collect($items)->sum(fn($i) => $i['subtotal'] ?? 0);

        $set('../../total', $total);
    }
}
