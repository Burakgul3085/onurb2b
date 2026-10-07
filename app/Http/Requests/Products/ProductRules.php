<?php

namespace App\Http\Requests\Products;

use App\Data\Products\ProductData;
use App\Enums\VatRate;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRules
{
    /**
     * @return array<string, mixed>
     */
    public static function fields(?Product $product = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'vat_rate' => ['required', Rule::enum(VatRate::class)],
            'purchase_price' => ['required', 'string', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'],
            'sale_price' => ['required', 'string', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'],
            'prices_include_vat' => ['required', 'boolean'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:100000000'],
            'critical_stock' => ['required', 'integer', 'min:0', 'lte:minimum_stock'],
            'description' => ['nullable', 'string', 'max:5000'],
            'barcodes' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public static function validate(Validator $validator, FormRequest $request, ?Product $product = null): void
    {
        $validator->after(function () use ($validator, $request, $product): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $category = Category::query()->find($request->integer('category_id'));

            if ($category === null || $category->parent_id !== null) {
                $validator->errors()->add('category_id', __('Choose a top-level category.'));
            }

            $subcategoryId = $request->input('subcategory_id');

            if ($subcategoryId) {
                $subcategory = Category::query()->find((int) $subcategoryId);

                if ($subcategory === null || $subcategory->parent_id !== $category?->id) {
                    $validator->errors()->add('subcategory_id', __('The subcategory does not belong to the selected category.'));
                }
            }

            $codes = ProductData::barcodes($request->input('barcodes'));
            $tooLong = collect($codes)->contains(fn (string $code) => strlen($code) > 64);

            if ($tooLong) {
                $validator->errors()->add('barcodes', __('Each barcode may be at most 64 characters.'));
            }

            $taken = ProductBarcode::query()
                ->whereIn('barcode', $codes)
                ->when($product !== null, fn ($query) => $query->where('product_id', '!=', $product->id))
                ->exists();

            if ($taken) {
                $validator->errors()->add('barcodes', __('One of these barcodes is already used.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'ad',
            'brand_id' => 'marka',
            'category_id' => 'kategori',
            'subcategory_id' => 'alt kategori',
            'unit_id' => 'birim',
            'vat_rate' => 'KDV oranı',
            'purchase_price' => 'alış fiyatı',
            'sale_price' => 'satış fiyatı',
            'prices_include_vat' => 'KDV',
            'minimum_stock' => 'minimum stok',
            'critical_stock' => 'kritik stok',
            'description' => 'açıklama',
            'barcodes' => 'barkod',
            'image' => 'görsel',
            'is_active' => 'durum',
        ];
    }
}
