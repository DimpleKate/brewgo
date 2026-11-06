<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Category;
use App\Models\Promotion;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private readonly ContentImageService $contentImages) {}

    public function index(): View
    {
        $promotions = Promotion::with('category')->latest()->paginate(15);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.promotions.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image_file'], $data['remove_image']);

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->contentImages->store($request->file('image_file'), 'promotions');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion created.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.edit', [
            'promotion' => $promotion,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image_file'], $data['remove_image']);
        $previousImage = $promotion->image;

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->contentImages->store($request->file('image_file'), 'promotions');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        $promotion->update($data);

        if ((array_key_exists('image', $data) ? $data['image'] : $previousImage) !== $previousImage) {
            $this->contentImages->delete($previousImage);
        }

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();
        $this->contentImages->delete($promotion->image);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion deleted.');
    }
}
