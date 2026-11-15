<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPrintController extends Controller
{
    public function __invoke(Order $order)
    {
        $order->load(['items.product', 'items.variant']);
        
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order]);
        
        return $pdf->stream('Invoice-' . $order->receipt_number . '.pdf');
    }
}
