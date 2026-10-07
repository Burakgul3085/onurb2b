<?php

namespace App\Http\Requests\Finance;

use App\Enums\PaymentMethod;
use App\Models\Dealer;
use App\Policies\LedgerEntryPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('dealer') instanceof Dealer
            && app(LedgerEntryPolicy::class)->collect($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d+([.,]\d{1,2})?$/'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'document_date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
