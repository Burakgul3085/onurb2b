<?php

namespace App\Actions\Catalog;

use App\Models\Unit;
use Illuminate\Validation\ValidationException;

class SaveUnit
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Unit $unit = null): Unit
    {
        if ($unit?->is_base) {
            if (array_key_exists('is_active', $data) && ! filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                throw ValidationException::withMessages([
                    'is_active' => __('The base unit cannot be deactivated.'),
                ]);
            }

            $unit->update(['name' => trim((string) $data['name'])]);

            return $unit;
        }

        $parentId = (int) $data['parent_id'];
        $parent = Unit::query()->find($parentId);

        if ($parent === null || ($unit !== null && $this->createsCycle($unit, $parent))) {
            throw ValidationException::withMessages([
                'parent_id' => __('This unit conversion is invalid.'),
            ]);
        }

        $attributes = [
            'name' => trim((string) $data['name']),
            'parent_id' => $parentId,
            'multiplier' => (int) $data['multiplier'],
            'is_base' => false,
        ];

        if ($unit === null) {
            $attributes['is_active'] = true;

            return Unit::query()->create($attributes);
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $unit->update($attributes);

        return $unit;
    }

    private function createsCycle(Unit $unit, Unit $parent): bool
    {
        $current = $parent;
        $guard = 0;

        while ($guard < 10) {
            if ($current->id === $unit->id) {
                return true;
            }

            if ($current->parent_id === null) {
                return false;
            }

            $current->loadMissing('parent');
            $current = $current->parent;
            $guard++;
        }

        return true;
    }
}
