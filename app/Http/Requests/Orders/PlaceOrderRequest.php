<?php

namespace App\Http\Requests\Orders;

use App\Enums\District;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivery_address' => ['required', 'string', 'max:1000'],
            'district' => ['required', Rule::enum(District::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
