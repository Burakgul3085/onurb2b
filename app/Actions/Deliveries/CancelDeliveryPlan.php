<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Exceptions\DeliveryException;
use App\Models\Delivery;

class CancelDeliveryPlan
{
    public function execute(Delivery $delivery): void
    {
        if ($delivery->status !== DeliveryStatus::Preparing) {
            throw new DeliveryException(__('Only a planned delivery can be removed.'));
        }

        $delivery->delete();
    }
}
