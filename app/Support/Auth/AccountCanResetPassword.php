<?php

namespace App\Support\Auth;

use App\Enums\DealerApplicationStatus;
use App\Models\User;

class AccountCanResetPassword
{
    public function execute(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->dealer_id === null) {
            return true;
        }

        $dealer = $user->dealer;

        return $dealer !== null
            && $dealer->is_active
            && $dealer->application_status === DealerApplicationStatus::Approved;
    }
}
