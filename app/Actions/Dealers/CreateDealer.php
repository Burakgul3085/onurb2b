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
        return Dealer::query()->create([
            ...DealerData::fromArray($data)->toAttributes(),
            'application_status' => DealerApplicationStatus::Pending,
            'is_active' => false,
        ]);
    }
}
