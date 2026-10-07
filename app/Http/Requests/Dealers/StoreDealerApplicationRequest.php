<?php

namespace App\Http\Requests\Dealers;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealerApplicationRequest extends FormRequest
{
    use NormalizesDealerFields;

    public function authorize(): bool
    {
        return true;
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
