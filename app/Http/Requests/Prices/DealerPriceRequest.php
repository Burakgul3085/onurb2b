<?php

namespace App\Http\Requests\Prices;

use App\Models\DealerPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DealerPriceRequest extends FormRequest
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
        return PriceRecordRules::fields($this->route('dealerPrice') instanceof DealerPrice);
    }

    public function withValidator(Validator $validator): void
    {
        PriceRecordRules::after($validator);
    }
}
