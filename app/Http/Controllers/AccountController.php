<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentProofService;
use App\Services\ProfilePictureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'orders.items',
            'orders.payments',
            'orders.receipts',
            'orders.refunds',
            'orderRequests',
        ]);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'orders' => $user->orders->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'service_type' => $order->service_type,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status,
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount,
                'total' => (float) $order->total,
                'amount_paid' => (float) $order->amount_paid,
                'pickup_at' => $order->pickup_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'note' => $item->note,
                ])->values()->all(),
                'payments' => $order->payments->map(fn ($payment) => [
                    'method' => $payment->method,
                    'type' => $payment->type,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'reference' => $payment->reference,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->values()->all(),
                'receipts' => $order->receipts->map(fn ($receipt) => [
                    'receipt_number' => $receipt->receipt_number,
                    'amount' => (float) $receipt->amount,
                    'issued_at' => $receipt->issued_at?->toIso8601String(),
                ])->values()->all(),
                'refunds' => $order->refunds->map(fn ($refund) => [
                    'amount' => (float) $refund->amount,
                    'method' => $refund->method,
                    'reference' => $refund->reference,
                    'status' => $refund->status,
                    'processed_at' => $refund->processed_at?->toIso8601String(),
                ])->values()->all(),
            ])->values()->all(),
            'requests' => $user->orderRequests->map(fn ($orderRequest) => [
                'order_id' => $orderRequest->order_id,
                'type' => $orderRequest->type,
                'status' => $orderRequest->status,
                'reason' => $orderRequest->reason,
                'admin_note' => $orderRequest->admin_note,
                'created_at' => $orderRequest->created_at?->toIso8601String(),
            ])->values()->all(),
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="brew-and-go-account-'.now()->format('Ymd-His').'.json"',
            'Cache-Control' => 'private, no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, ProfilePictureService $profilePictures, PaymentProofService $proofs): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.in' => 'Type DELETE to confirm account deletion.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $profilePictures, $proofs) {
            $user->loadMissing('orders.payments');

            foreach ($user->orders as $order) {
                foreach ($order->payments as $payment) {
                    if ($payment->proof_path) {
                        $proofs->delete($payment->proof_path);
                        $payment->forceFill(['proof_path' => null])->save();
                    }
                }
            }

            if ($user->avatar_path) {
                $profilePictures->delete($user->avatar_path);
            }

            $user->cart()->delete();

            $anonymizedEmail = 'deleted+'.$user->id.'-'.Str::lower(Str::random(8)).'@example.invalid';

            $user->forceFill([
                'name' => 'Deleted customer',
                'email' => $anonymizedEmail,
                'phone' => null,
                'address' => null,
                'avatar_path' => null,
                'is_active' => false,
                'password' => Str::random(40),
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Your account has been deleted and your personal details were anonymised. Order records are retained for accounting and tax purposes.');
    }
}
