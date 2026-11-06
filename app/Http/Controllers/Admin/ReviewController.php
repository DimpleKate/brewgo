<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with(['user', 'order.items', 'moderator'])->latest();

        if ($request->filled('status') && in_array($request->status, [
            Review::STATUS_PENDING,
            Review::STATUS_APPROVED,
            Review::STATUS_HIDDEN,
        ], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating') && in_array((int) $request->rating, [1, 2, 3, 4, 5], true)) {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($query) use ($search) {
                $query->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => Review::count(),
            'pending' => Review::where('status', Review::STATUS_PENDING)->count(),
            'approved' => Review::where('status', Review::STATUS_APPROVED)->count(),
            'hidden' => Review::where('status', Review::STATUS_HIDDEN)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'counts'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([Review::STATUS_PENDING, Review::STATUS_APPROVED, Review::STATUS_HIDDEN]),
            ],
        ]);

        $review->update([
            'status' => $validated['status'],
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        $message = match ($review->status) {
            Review::STATUS_APPROVED => 'Review approved and published to the product page.',
            Review::STATUS_HIDDEN => 'Review hidden from the storefront.',
            default => 'Review returned to the moderation queue.',
        };

        return back()->with('success', $message);
    }
}
