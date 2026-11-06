<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\PdfService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): View
    {
        $receipt->load(['order.items', 'order.user', 'payment.verifier', 'issuer']);

        $this->authorize('view', $receipt->order);

        return view('receipts.show', compact('receipt'));
    }

    public function pdf(Receipt $receipt, PdfService $pdf): Response
    {
        $receipt->load(['order.items', 'order.user', 'payment.verifier', 'issuer']);

        $this->authorize('view', $receipt->order);

        return response($pdf->receipt($receipt), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt->receipt_number.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
