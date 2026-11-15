<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .details {
            margin-bottom: 20px;
            width: 100%;
        }
        .details td {
            padding: 4px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .totals {
            width: 100%;
            margin-top: 20px;
        }
        .totals td {
            padding: 4px;
        }
        .totals .bold {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Struk Pembayaran</h1>
        <p>Terima kasih atas kunjungan Anda</p>
    </div>

    <table class="details">
        <tr>
            <td width="100"><strong>No. Struk</strong></td>
            <td>: {{ $order->receipt_number }}</td>
            <td width="100" class="text-right"><strong>Tanggal</strong></td>
            <td class="text-right">: {{ $order->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Tipe Pesanan</strong></td>
            <td>: {{ ucwords(str_replace('-', ' ', $order->order_type)) }}</td>
            <td class="text-right"><strong>Pembayaran</strong></td>
            <td class="text-right">: {{ $order->payment_method ?: '-' }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Varian</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->variant?->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="text-right" width="80%">Subtotal</td>
            <td class="text-right">Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</td>
        </tr>
        @if($order->tax_amount > 0)
        <tr>
            <td class="text-right">Pajak / Service</td>
            <td class="text-right">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($order->discount_amount > 0)
        <tr>
            <td class="text-right">Diskon / Potongan (-)</td>
            <td class="text-right">Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-right bold" style="font-size: 16px;">Total Akhir</td>
            <td class="text-right bold" style="font-size: 16px;">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
