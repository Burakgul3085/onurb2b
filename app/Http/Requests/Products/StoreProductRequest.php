<?php

namespace App\Http\Requests\Products;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ProductRules::fields();
    }

    public function withValidator($validator): void
    {
        ProductRules::validate($validator, $this);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ProductRules::attributes();
    }
}
