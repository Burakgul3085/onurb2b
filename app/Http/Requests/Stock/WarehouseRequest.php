<?php

namespace App\Http\Requests\Stock;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $warehouse = $this->route('warehouse');

        if ($warehouse instanceof Warehouse) {
            return $this->user()?->can('update', $warehouse) ?? false;
        }

        return $this->user()?->can('create', Warehouse::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'name')->ignore($warehouse instanceof Warehouse ? $warehouse : null)],
            'is_active' => [$warehouse instanceof Warehouse ? 'required' : 'sometimes', 'boolean'],
        ];
    }
}
