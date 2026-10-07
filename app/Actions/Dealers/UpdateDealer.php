<?php

namespace App\Actions\Dealers;

use App\Data\Dealers\DealerData;
use App\Enums\DealerApplicationStatus;
use App\Models\Dealer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDealer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Dealer $dealer, array $data): Dealer
    {
        return DB::transaction(function () use ($dealer, $data) {
            $attributes = DealerData::fromArray($data)->toAttributes();

            if (array_key_exists('is_active', $data)) {
                $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);

                if ($isActive && $dealer->application_status !== DealerApplicationStatus::Approved) {
                    throw ValidationException::withMessages([
                        'is_active' => __('Only an approved dealer can be activated.'),
                    ]);
                }

                $attributes['is_active'] = $isActive;
            }

            $dealer->update($attributes);

            if (array_key_exists('is_active', $attributes)) {
                $dealer->users()->update(['is_active' => $dealer->is_active]);
            }

            return $dealer;
        });
    }
}
