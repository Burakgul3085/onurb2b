<?php

namespace App\Http\Requests\Prices;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PriceRecordRules
{
    /**
     * @return array<string, mixed>
     */
    public static function fields(bool $updating): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'price' => ['required', 'string', 'regex:/^\d{1,13}([.,]\d{1,2})?$/'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'prices_include_vat' => ['sometimes', 'boolean'],
            'discount_percent' => ['required', 'string', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
            'is_active' => [$updating ? 'required' : 'sometimes', 'boolean'],
        ];
    }

    public static function after(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $starts = $validator->getData()['starts_at'] ?? null;
            $ends = $validator->getData()['ends_at'] ?? null;

            if (is_string($starts) && $starts !== '' && is_string($ends) && $ends !== '' && $ends < $starts) {
                $validator->errors()->add('ends_at', __('The end date must be on or after the start date.'));
            }

            $discount = $validator->getData()['discount_percent'] ?? null;

            if (is_string($discount) && $discount !== '' && ! $validator->errors()->has('discount_percent')) {
                $normalized = str_replace(',', '.', $discount);

                if (is_numeric($normalized) && ((float) $normalized > 100)) {
                    $validator->errors()->add('discount_percent', __('The discount may not be greater than 100.'));
                }
            }
        });
    }
}
