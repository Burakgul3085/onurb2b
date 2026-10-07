<?php

namespace App\Http\Requests\Catalog;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageCatalog') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');
        $isBase = $unit instanceof Unit && $unit->is_base;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($unit instanceof Unit ? $unit : null)],
            'parent_id' => [$isBase ? 'nullable' : 'required', 'integer', 'exists:units,id'],
            'multiplier' => [$isBase ? 'nullable' : 'required', 'integer', 'min:1', 'max:100000'],
            'is_active' => [$unit instanceof Unit ? 'required' : 'sometimes', 'boolean'],
        ];
    }
}
