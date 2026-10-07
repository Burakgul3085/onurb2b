<?php

namespace App\Http\Requests\Catalog;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageCatalog') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $parentId = $this->input('parent_id');
        $parentId = $parentId === '' || $parentId === null ? null : (int) $parentId;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId))
                    ->ignore($category instanceof Category ? $category : null),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => [$category instanceof Category ? 'required' : 'sometimes', 'boolean'],
        ];
    }
}
