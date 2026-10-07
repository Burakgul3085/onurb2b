<?php

namespace App\Http\Requests\Deliveries;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('delivery');

        return $delivery instanceof Delivery && ($this->user()?->can('update', $delivery) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'delivered' => ['required', 'array'],
            'delivered.*' => ['required', 'integer', 'min:0', 'max:10000000'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
