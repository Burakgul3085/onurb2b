<?php

namespace App\Actions\Dealers;

use App\Data\Dealers\DealerData;
use App\Enums\DealerApplicationStatus;
use App\Models\Dealer;

class CreateDealer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Dealer
    {
        $attributes = DealerData::fromArray($data)->toAttributes();

        if (array_key_exists('price_list_id', $data)) {
            $attributes['price_list_id'] = $data['price_list_id'] ?: null;
        }

        return Dealer::query()->create([
            ...$attributes,
            'application_status' => DealerApplicationStatus::Pending,
            'is_active' => false,
        ]);
    }
}
