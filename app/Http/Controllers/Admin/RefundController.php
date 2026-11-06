<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    public function index(Request $request): View
    {
        $query = Refund::query()
            ->with(['order.user', 'processor'])
            ->latest();

        if ($request->filled('status') && in_array($request->string('status')->toString(), [
            Refund::STATUS_APPROVED,
            Refund::STATUS_PROCESSED,
            Refund::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('order.user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return view('admin.refunds.index', [
            'refunds' => $query->paginate(20)->withQueryString(),
            'status' => $request->string('status')->toString(),
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in([Payment::METHOD_CASH, Payment::METHOD_QR])],
            'reference' => ['nullable', 'string', 'max:80', 'required_if:method,'.Payment::METHOD_QR],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $refund = $this->refunds->record($order, $request->user(), $validated);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Refund of ₱'.number_format((float) $refund->amount, 2).' recorded.');
    }
}
