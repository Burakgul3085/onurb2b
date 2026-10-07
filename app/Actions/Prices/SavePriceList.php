<?php

namespace App\Actions\Prices;

use App\Models\PriceList;
use App\Support\Money\Discount;

class SavePriceList
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?PriceList $priceList = null): PriceList
    {
        $attributes = [
            'name' => trim((string) $data['name']),
            'document_discount_percent' => Discount::of($data['document_discount_percent'] ?? '0'),
        ];

        if ($priceList === null) {
            $attributes['is_active'] = true;

            return PriceList::query()->create($attributes);
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $priceList->update($attributes);

        return $priceList;
    }
}
