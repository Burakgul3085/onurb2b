<?php

namespace App\Http\Requests\Finance;

use App\Models\Order;
use App\Policies\LedgerEntryPolicy;
use Illuminate\Foundation\Http\FormRequest;

class RecordOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && app(LedgerEntryPolicy::class)->returnGoods($this->user(), $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'pieces' => ['required', 'array'],
            'pieces.*' => ['required', 'integer', 'min:0', 'max:10000000'],
        ];
    }
}
