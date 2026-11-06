<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewOrderRequestRequest;
use App\Models\OrderRequest;
use App\Services\OrderRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderRequestController extends Controller
{
    public function __construct(private readonly OrderRequestService $requestService) {}

    public function index(Request $request): View
    {
        $query = OrderRequest::with(['order', 'user', 'reviewer'])->latest();

        if ($request->filled('status') && in_array($request->status, [
            OrderRequest::STATUS_PENDING,
            OrderRequest::STATUS_APPROVED,
            OrderRequest::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('admin.requests.index', compact('requests'));
    }

    public function review(ReviewOrderRequestRequest $review, OrderRequest $orderRequest): RedirectResponse
    {
        $this->requestService->review(
            $orderRequest,
            $review->user(),
            $review->string('decision')->toString(),
            $review->input('admin_note')
        );

        return redirect()->route('admin.requests.index')->with('success', 'Request reviewed successfully.');
    }
}
