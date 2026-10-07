<?php

namespace App\Actions\Catalog;

use App\Models\Brand;

class SaveBrand
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Brand $brand = null): Brand
    {
        $attributes = [
            'name' => trim((string) $data['name']),
        ];

        if ($brand === null) {
            $attributes['is_active'] = true;

            return Brand::query()->create($attributes);
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $brand->update($attributes);

        return $brand;
    }
}
