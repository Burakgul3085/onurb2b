<?php

namespace App\Actions\Dealers;

use App\Models\Dealer;
use App\Models\User;

class UpdateDealerNotificationEmail
{
    public function execute(Dealer $dealer, User $actor, string $email): void
    {
        $previous = $dealer->email;
        $dealer->update(['email' => $email]);

        if (strcasecmp($actor->email, $previous) === 0) {
            $actor->update(['email' => $email]);
        }
    }
}
