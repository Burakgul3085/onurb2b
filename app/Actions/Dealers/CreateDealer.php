<?php

namespace App\Actions\Dealers;

use App\Actions\Mail\Notify;
use App\Data\Dealers\DealerData;
use App\Enums\DealerApplicationStatus;
use App\Models\Dealer;
use Illuminate\Support\Facades\DB;

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

        $dealer = Dealer::query()->create([
            ...$attributes,
            'application_status' => DealerApplicationStatus::Pending,
            'is_active' => false,
        ]);

        DB::afterCommit(fn () => app(Notify::class)->dealerApplication($dealer->id));

        return $dealer;
    }
}
