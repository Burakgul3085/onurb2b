<?php

namespace App\Http\Requests\Deliveries;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Delivery::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'scheduled_on' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
