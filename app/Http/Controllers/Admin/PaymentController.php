<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function verify(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        $this->assertBelongsToOrder($order, $payment);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $receipt = $this->payments->verify($order, $payment, $request->user(), $validated['note'] ?? null);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Payment verified. Receipt '.$receipt->receipt_number.' generated.');
    }

    public function reject(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        $this->assertBelongsToOrder($order, $payment);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->payments->reject($order, $payment, $request->user(), $validated['rejection_reason']);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Payment rejected. The order has not been marked as paid.');
    }

    public function record(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in([Payment::METHOD_CASH, Payment::METHOD_QR])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:80', 'required_if:method,'.Payment::METHOD_QR],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $receipt = $this->payments->record($order, $validated, $request->user());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Payment recorded. Receipt '.$receipt->receipt_number.' generated.');
    }

    private function assertBelongsToOrder(Order $order, Payment $payment): void
    {
        abort_unless($payment->order_id === $order->id, 404);
    }
}
