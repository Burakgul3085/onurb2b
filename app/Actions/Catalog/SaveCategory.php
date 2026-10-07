<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use Illuminate\Validation\ValidationException;

class SaveCategory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Category $category = null): Category
    {
        $parentId = $data['parent_id'] ?? null;
        $parentId = $parentId === '' || $parentId === null ? null : (int) $parentId;

        if ($parentId !== null) {
            $parent = Category::query()->find($parentId);

            if ($parent === null || $parent->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => __('A subcategory cannot contain another category.'),
                ]);
            }
        }

        if ($category !== null && $parentId !== null && $category->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => __('A category with subcategories must stay at the top level.'),
            ]);
        }

        if ($category !== null && $parentId === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => __('A category cannot be its own parent.'),
            ]);
        }

        $attributes = [
            'name' => trim((string) $data['name']),
            'parent_id' => $parentId,
        ];

        if ($category === null) {
            $attributes['is_active'] = true;

            return Category::query()->create($attributes);
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $category->update($attributes);

        return $category;
    }
}
