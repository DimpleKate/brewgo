<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PreventDuplicateProductSubmission;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use App\Services\ProductSizePriceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductImageService $productImages,
        private readonly ProductSizePriceService $sizePrices,
    ) {}

    public function index(Request $request): View
    {
        $query = Product::with('category')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.trim($request->string('search')->toString()).'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::ordered()->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(Request $request): View
    {
        $formToken = $request->session()->get(PreventDuplicateProductSubmission::SESSION_KEY);

        if (! is_string($formToken) || $formToken === '') {
            $formToken = Str::random(40);
            $request->session()->put(PreventDuplicateProductSubmission::SESSION_KEY, $formToken);
        }

        return view('admin.products.create', [
            'categories' => Category::ordered()->get(),
            'addons' => Addon::orderBy('name')->get(),
            'formToken' => $formToken,
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $request->session()->forget(PreventDuplicateProductSubmission::SESSION_KEY);

        $data = $request->validated();
        $sizePrices = $data['size_prices'];
        $data['base_price'] = round((float) $sizePrices['medium'], 2);
        unset($data['addons'], $data['image_file'], $data['remove_image'], $data['size_prices']);

        $storedImage = null;

        try {
            $product = DB::transaction(function () use ($request, $data, $sizePrices, &$storedImage) {
                if ($request->hasFile('image_file')) {
                    $storedImage = $this->productImages->store($request->file('image_file'));
                    $data['image'] = $storedImage;
                }

                $product = Product::create($data);
                $this->sizePrices->sync($product, $sizePrices);
                $product->addons()->sync($request->input('addons', []));

                return $product;
            });
        } catch (QueryException $exception) {
            $this->productImages->delete($storedImage);

            $existingProduct = Product::where('slug', $data['slug'])->first();

            if ($existingProduct) {
                return redirect()
                    ->route('admin.products.create')
                    ->with('error', 'This product was already saved, so a duplicate was not created. The form is ready for a new product.');
            }

            return $this->failedStoreResponse($request, $exception);
        } catch (Throwable $exception) {
            $this->productImages->delete($storedImage);

            return $this->failedStoreResponse($request, $exception);
        }

        return redirect()
            ->route('admin.products.create')
            ->with('success', 'Product "'.$product->name.'" was created successfully. The form is ready for the next product.');
    }

    public function edit(Product $product): View
    {
        $product->load(['options', 'addons']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::ordered()->get(),
            'addons' => Addon::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $sizePrices = $data['size_prices'];
        $data['base_price'] = round((float) $sizePrices['medium'], 2);
        unset($data['addons'], $data['image_file'], $data['remove_image'], $data['size_prices']);

        $previousImage = null;
        $storedImage = null;

        try {
            DB::transaction(function () use ($request, $product, $data, $sizePrices, &$previousImage, &$storedImage) {
                if ($request->hasFile('image_file')) {
                    $previousImage = $product->image;
                    $storedImage = $this->productImages->store($request->file('image_file'));
                    $data['image'] = $storedImage;
                } elseif ($request->boolean('remove_image')) {
                    $previousImage = $product->image;
                    $data['image'] = null;
                }

                $product->update($data);
                $this->sizePrices->sync($product, $sizePrices);
                $product->addons()->sync($request->input('addons', []));
            });
        } catch (Throwable $exception) {
            $this->productImages->delete($storedImage);

            report($exception);

            return redirect()
                ->route('admin.products.edit', $product)
                ->withInput($request->except(['image_file', '_token']))
                ->with('error', 'We could not save your changes. Your details are still here—please review them and try again.');
        }

        $this->productImages->delete($previousImage);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product "'.$product->name.'" was updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $image = $product->image;

        $product->delete();
        $this->productImages->delete($image);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted. Existing order snapshots are preserved.');
    }

    private function failedStoreResponse(ProductRequest $request, Throwable $exception): RedirectResponse
    {
        report($exception);

        $formToken = Str::random(40);
        $request->session()->put(PreventDuplicateProductSubmission::SESSION_KEY, $formToken);

        return redirect()
            ->route('admin.products.create')
            ->withInput($request->except(['image_file', '_token', 'submission_token']) + ['submission_token' => $formToken])
            ->with('error', 'We could not save the product. Your details are still here—please review them and try again.');
    }
}
