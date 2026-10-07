<?php

namespace App\Http\Requests\Prices;

use App\Models\PriceListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePrices') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return PriceRecordRules::fields($this->route('item') instanceof PriceListItem);
    }

    public function withValidator(Validator $validator): void
    {
        PriceRecordRules::after($validator);
    }
}
