<?php

namespace App\Http\Requests\Dealers;

use App\Models\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ApproveDealerRequest extends FormRequest
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
            'password' => ['required', 'confirmed', Password::defaults()],
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
