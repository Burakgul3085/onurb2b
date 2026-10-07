<?php

namespace App\Http\Requests\Finance;

use App\Models\LedgerEntry;
use App\Policies\LedgerEntryPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ReverseLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry instanceof LedgerEntry
            && app(LedgerEntryPolicy::class)->reverse($this->user(), $entry);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
