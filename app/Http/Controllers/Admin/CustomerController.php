<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('is_admin', false)->withCount('orders')->latest();

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_if($customer->is_admin, 404);

        $customer->load(['orders' => fn ($query) => $query->latest()->with('items')]);

        return view('admin.customers.show', compact('customer'));
    }

    public function toggleActive(User $customer): RedirectResponse
    {
        abort_if($customer->is_admin, 403);

        $customer->update(['is_active' => ! $customer->is_active]);

        $state = $customer->is_active ? 'reactivated' : 'deactivated';

        return back()->with('success', "Customer account {$state}.");
    }

    public function export(User $customer): Response
    {
        abort_if($customer->is_admin, 404);

        $customer->load(['orders' => fn ($query) => $query->latest()->with('items')]);

        $rows = [
            ['Customer data export'],
            ['Name', $customer->name],
            ['Email', $customer->email],
            ['Phone', $customer->phone],
            ['Address', $customer->address],
            ['Joined', $customer->created_at?->toIso8601String()],
            ['Status', $customer->is_active ? 'Active' : 'Deactivated'],
            [],
            ['Order', 'Date', 'Service', 'Status', 'Payment status', 'Items', 'Subtotal', 'Discount', 'Total', 'Paid'],
        ];

        foreach ($customer->orders as $order) {
            $rows[] = [
                $order->order_number,
                $order->created_at?->toIso8601String(),
                $order->serviceLabel(),
                $order->statusLabel(),
                $order->paymentStatusLabel(),
                $order->items->sum('quantity'),
                number_format((float) $order->subtotal, 2),
                number_format((float) $order->discount, 2),
                number_format((float) $order->total, 2),
                number_format((float) $order->amount_paid, 2),
            ];
        }

        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customer-'.$customer->id.'-'.now()->format('Ymd').'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
