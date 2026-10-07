<?php

namespace App\Actions\Stock;

use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class SaveWarehouse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Warehouse $warehouse = null): Warehouse
    {
        $attributes = [
            'name' => trim((string) $data['name']),
        ];

        if ($warehouse === null) {
            $attributes['is_active'] = true;

            return Warehouse::query()->create($attributes);
        }

        if (array_key_exists('is_active', $data)) {
            $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);

            if (! $isActive && $warehouse->holdsStock()) {
                throw ValidationException::withMessages([
                    'is_active' => __('A warehouse with stock cannot be deactivated.'),
                ]);
            }

            $attributes['is_active'] = $isActive;
        }

        $warehouse->update($attributes);

        return $warehouse;
    }
}
