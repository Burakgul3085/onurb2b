<?php

namespace App\Http\Requests\Dealers;

use App\Models\Dealer;
use Illuminate\Foundation\Http\FormRequest;

class RejectDealerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer && ($this->user()?->can('approve', $dealer) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return DealerRules::attributes();
    }
}
