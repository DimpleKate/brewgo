<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ContentImageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ContentImageService $contentImages) {}

    public function index(): View
    {
        $categories = Category::withCount('products')->ordered()->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image_file'], $data['remove_image']);

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->contentImages->store($request->file('image_file'), 'categories');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image_file'], $data['remove_image']);
        $previousImage = $category->image;

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->contentImages->store($request->file('image_file'), 'categories');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        $category->update($data);

        if ((array_key_exists('image', $data) ? $data['image'] : $previousImage) !== $previousImage) {
            $this->contentImages->delete($previousImage);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();
        } catch (QueryException) {
            return back()->with('error', 'This category still has products. Move or delete those products first.');
        }

        $this->contentImages->delete($category->image);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
