<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveBrand;
use App\Http\Requests\Catalog\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageCatalog');

        return view('brands.index', [
            'brands' => Brand::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function store(BrandRequest $request, SaveBrand $saveBrand): RedirectResponse
    {
        $saveBrand->execute($request->validated());

        return redirect()->route('brands.index')->with('status', __('Brand saved.'));
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('manageCatalog');

        return view('brands.edit', ['brand' => $brand]);
    }

    public function update(BrandRequest $request, Brand $brand, SaveBrand $saveBrand): RedirectResponse
    {
        $saveBrand->execute($request->validated(), $brand);

        return redirect()->route('brands.index')->with('status', __('Brand saved.'));
    }
}
