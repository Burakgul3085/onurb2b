<?php

namespace App\Http\Requests\Settings;

use App\Models\CompanySetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', CompanySetting::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'footnote' => ['required', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
