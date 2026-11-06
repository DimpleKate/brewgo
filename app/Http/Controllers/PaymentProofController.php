<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Services\PaymentProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    public function show(Request $request, Payment $payment, PaymentProofService $proofs): StreamedResponse
    {
        $payment->loadMissing('order');

        $user = $request->user();
        $isOwner = $payment->order && $payment->order->user_id === $user->id;

        abort_unless($user->isAdmin() || $isOwner, 403);
        abort_unless($payment->hasProof(), 404);

        return $proofs->response($payment->proof_path, 'payment-proof-'.$payment->id);
    }

    public function update(Request $request, Order $order, Payment $payment, PaymentProofService $proofs): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($payment->order_id === $order->id, 404);

        if (! $payment->isPending()) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Only a payment that is awaiting verification can be updated.',
            ]);
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($payment, $validated, $proofs, $request) {
            $previous = $payment->proof_path;
            $path = $proofs->store($validated['payment_proof']);

            $payment->update([
                'proof_path' => $path,
                'reference' => $validated['payment_reference'] ?: $payment->reference,
            ]);

            if ($previous && $previous !== $path) {
                $proofs->delete($previous);
            }

            $order = $payment->order;
            $order->statusHistories()->create([
                'note' => 'Customer replaced the proof of payment for the pending payment.',
                'status' => $order->status->value,
                'changed_by' => $request->user()->id,
            ]);

            $this->notifyAdmins($order, 'Payment proof updated', 'The customer uploaded a new proof of payment for order '.$order->order_number.'.');
        }, 3);

        return redirect()->route('orders.show', $order)->with('success', 'Your proof of payment has been updated for review.');
    }

    public function retry(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'method' => ['required', Rule::in([Payment::METHOD_CASH, Payment::METHOD_QR])],
            'payment_reference' => ['nullable', 'required_if:method,'.Payment::METHOD_QR, 'string', 'max:80'],
            'payment_proof' => ['nullable', 'required_if:method,'.Payment::METHOD_QR, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $payment = DB::transaction(function () use ($order, $validated, $request) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status->isFinal()) {
                throw ValidationException::withMessages([
                    'method' => 'This order can no longer accept a payment.',
                ]);
            }

            if ($order->payments()->where('status', Payment::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages([
                    'method' => 'You already have a payment awaiting verification.',
                ]);
            }

            $outstanding = round(max(
                (float) $order->required_payment_amount - (float) $order->amount_paid,
                0
            ), 2);

            if ($outstanding <= 0) {
                $outstanding = $order->balanceDue();
            }

            if ($outstanding <= 0) {
                throw ValidationException::withMessages([
                    'method' => 'This order has no outstanding balance.',
                ]);
            }

            $path = isset($validated['payment_proof'])
                ? app(PaymentProofService::class)->store($validated['payment_proof'])
                : null;

            $payment = $order->payments()->create([
                'method' => $validated['method'],
                'type' => $outstanding + 0.001 >= (float) $order->total ? Payment::TYPE_FULL : Payment::TYPE_DEPOSIT,
                'amount' => $outstanding,
                'status' => Payment::STATUS_PENDING,
                'reference' => $validated['payment_reference'] ?? null,
                'proof_path' => $path,
            ]);

            $order->update(['payment_status' => Order::PAYMENT_PENDING]);

            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => 'Customer submitted a new '.($validated['method'] === Payment::METHOD_QR ? 'GCash / QR' : 'cash').' payment for verification.',
                'changed_by' => $request->user()->id,
            ]);

            $this->notifyAdmins($order, 'New payment submitted', 'Order '.$order->order_number.' has a new payment awaiting verification.');

            return $payment;
        }, 3);

        return redirect()->route('orders.show', $order)->with('success', 'Your new payment was submitted for verification.');
    }

    private function notifyAdmins(Order $order, string $title, string $message): void
    {
        User::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->get()
            ->each(fn (User $admin) => $admin->notify(new AdminAlert(
                title: $title,
                message: $message,
                url: route('admin.orders.show', $order),
                orderNumber: $order->order_number,
            )));
    }
}
