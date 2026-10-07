<?php

namespace App\Http\Requests\Dealers;

use App\Models\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealerRequest extends FormRequest
{
    use NormalizesDealerFields;

    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer && ($this->user()?->can('update', $dealer) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dealer = $this->route('dealer');

        return [
            ...DealerRules::fields($dealer instanceof Dealer ? $dealer->id : null),
            'is_active' => ['sometimes', 'boolean'],
            'price_list_id' => ['nullable', 'integer', Rule::exists('price_lists', 'id')],
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
