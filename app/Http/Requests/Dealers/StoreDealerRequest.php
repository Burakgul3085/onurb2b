<?php

namespace App\Http\Requests\Dealers;

use App\Models\Dealer;
use Illuminate\Foundation\Http\FormRequest;

class StoreDealerRequest extends FormRequest
{
    use NormalizesDealerFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Dealer::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return DealerRules::fields();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return DealerRules::attributes();
    }
}
