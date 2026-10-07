<?php

namespace App\Http\Requests\Prices;

use App\Models\PriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $priceList = $this->route('priceList');

        if ($priceList instanceof PriceList) {
            return $this->user()?->can('update', $priceList) ?? false;
        }

        return $this->user()?->can('create', PriceList::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $priceList = $this->route('priceList');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('price_lists', 'name')->ignore($priceList instanceof PriceList ? $priceList : null)],
            'document_discount_percent' => ['required', 'string', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
            'is_active' => [$priceList instanceof PriceList ? 'required' : 'sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $discount = $validator->getData()['document_discount_percent'] ?? null;

            if (is_string($discount) && $discount !== '' && ! $validator->errors()->has('document_discount_percent')) {
                $normalized = str_replace(',', '.', $discount);

                if (is_numeric($normalized) && (float) $normalized > 100) {
                    $validator->errors()->add('document_discount_percent', __('The discount may not be greater than 100.'));
                }
            }
        });
    }
}
