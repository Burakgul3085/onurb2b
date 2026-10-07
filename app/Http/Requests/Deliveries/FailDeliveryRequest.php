<?php

namespace App\Http\Requests\Deliveries;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;

class FailDeliveryRequest extends FormRequest
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
            'note' => ['required', 'string', 'max:1000'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
