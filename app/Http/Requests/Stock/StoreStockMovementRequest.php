<?php

namespace App\Http\Requests\Stock;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjustStock') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'type' => ['required', Rule::in(StockMovementType::manualValues())],
            'quantity' => ['required', 'integer', 'min:0', 'max:10000000'],
            'direction' => ['required_if:type,adjustment', 'nullable', Rule::in(['in', 'out'])],
            'destination_warehouse_id' => ['required_if:type,transfer', 'nullable', 'integer', 'different:warehouse_id', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = (string) $this->input('type');
            $quantity = (int) $this->input('quantity');

            if ($type !== StockMovementType::Count->value && $quantity < 1) {
                $validator->errors()->add('quantity', __('Enter a quantity of at least 1.'));
            }
        });
    }
}
