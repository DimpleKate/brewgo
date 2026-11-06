<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarouselImagesRequest;
use App\Models\CarouselSlide;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarouselSlideController extends Controller
{
    public function __construct(private readonly ContentImageService $contentImages) {}

    public function index(): View
    {
        $slides = CarouselSlide::ordered()->get();

        return view('admin.carousel.index', compact('slides'));
    }

    public function storeMany(CarouselImagesRequest $request): RedirectResponse
    {
        $images = $request->file('images', []);
        $sortOrder = (int) CarouselSlide::max('sort_order');

        foreach ($images as $image) {
            CarouselSlide::create([
                'title' => null,
                'image' => $this->contentImages->store($image, 'carousel'),
                'is_active' => true,
                'sort_order' => ++$sortOrder,
            ]);
        }

        $count = count($images);

        return redirect()
            ->route('admin.carousel.index')
            ->with('success', $count.' carousel '.($count === 1 ? 'image' : 'images').' uploaded and added to the homepage slider.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:carousel_slides,id'],
        ], [
            'ids.required' => 'Select at least one carousel image to delete.',
            'ids.min' => 'Select at least one carousel image to delete.',
            'ids.*.integer' => 'One of the selected images is invalid. Refresh the page and try again.',
            'ids.*.exists' => 'One of the selected images no longer exists. Refresh the page and try again.',
        ]);

        $slides = CarouselSlide::whereKey($data['ids'])->get();

        foreach ($slides as $slide) {
            $image = $slide->image;

            $slide->delete();
            $this->contentImages->delete($image);
        }

        $count = $slides->count();

        return redirect()
            ->route('admin.carousel.index')
            ->with('success', $count.' carousel '.($count === 1 ? 'image' : 'images').' deleted.');
    }

    public function destroy(CarouselSlide $carouselSlide): RedirectResponse
    {
        $image = $carouselSlide->image;

        $carouselSlide->delete();
        $this->contentImages->delete($image);

        return redirect()->route('admin.carousel.index')->with('success', 'Carousel image deleted.');
    }
}
