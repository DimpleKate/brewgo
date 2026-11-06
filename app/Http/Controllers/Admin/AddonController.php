<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddonRequest;
use App\Models\Addon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AddonController extends Controller
{
    public function index(): View
    {
        $addons = Addon::withCount('products')->orderBy('name')->paginate(15);

        return view('admin.addons.index', compact('addons'));
    }

    public function create(): View
    {
        return view('admin.addons.create');
    }

    public function store(AddonRequest $request): RedirectResponse
    {
        Addon::create($request->validated());

        return redirect()->route('admin.addons.index')->with('success', 'Add-on created.');
    }

    public function edit(Addon $addon): View
    {
        return view('admin.addons.edit', compact('addon'));
    }

    public function update(AddonRequest $request, Addon $addon): RedirectResponse
    {
        $addon->update($request->validated());

        return redirect()->route('admin.addons.index')->with('success', 'Add-on updated.');
    }

    public function destroy(Addon $addon): RedirectResponse
    {
        $addon->delete();

        return redirect()->route('admin.addons.index')->with('success', 'Add-on deleted.');
    }
}
