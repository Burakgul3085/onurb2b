<?php

namespace App\Actions\Prices;

use App\Models\DealerPrice;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Support\Money\Discount;
use App\Support\Money\Money;

class SavePriceRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function forList(PriceList $priceList, array $data, ?PriceListItem $item = null): PriceListItem
    {
        $item ??= new PriceListItem(['price_list_id' => $priceList->id]);
        $item->fill($this->attributes($data, $item->exists));
        $item->price_list_id = $priceList->id;
        $item->save();

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function forDealer(int $dealerId, array $data, ?DealerPrice $price = null): DealerPrice
    {
        $price ??= new DealerPrice(['dealer_id' => $dealerId]);
        $price->fill($this->attributes($data, $price->exists));
        $price->dealer_id = $dealerId;
        $price->save();

        return $price;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, bool $exists): array
    {
        $starts = $data['starts_at'] ?? null;
        $ends = $data['ends_at'] ?? null;

        $attributes = [
            'product_id' => (int) $data['product_id'],
            'price' => Money::of((string) $data['price']),
            'minimum_quantity' => (int) $data['minimum_quantity'],
            'starts_at' => is_string($starts) && $starts !== '' ? $starts : null,
            'ends_at' => is_string($ends) && $ends !== '' ? $ends : null,
            'prices_include_vat' => filter_var($data['prices_include_vat'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'discount_percent' => Discount::of($data['discount_percent'] ?? '0'),
        ];

        if ($exists && array_key_exists('is_active', $data)) {
            $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (! $exists) {
            $attributes['is_active'] = true;
        }

        return $attributes;
    }
}
