<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveCategory;
use App\Http\Requests\Catalog\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageCatalog');

        return view('categories.index', [
            'categories' => Category::query()->with('parent')->orderBy('name')->paginate(20),
            'roots' => Category::query()->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->execute($request->validated());

        return redirect()->route('categories.index')->with('status', __('Category saved.'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('manageCatalog');

        return view('categories.edit', [
            'category' => $category,
            'roots' => Category::query()->whereNull('parent_id')->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->execute($request->validated(), $category);

        return redirect()->route('categories.index')->with('status', __('Category saved.'));
    }
}
