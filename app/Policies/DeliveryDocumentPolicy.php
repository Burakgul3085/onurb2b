<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\DeliveryDocument;
use App\Models\User;

class DeliveryDocumentPolicy
{
    public function view(User $actor, DeliveryDocument $document): bool
    {
        if ($actor->dealer_id !== null) {
            return (int) $actor->dealer_id === (int) $document->dealer_id
                && $actor->can(Permission::OrdersView->value);
        }

        return $actor->can(Permission::OrdersView->value) || $actor->can(Permission::DeliveriesView->value);
    }

    public function deniedStatus(User $actor, DeliveryDocument $document): int
    {
        if ($actor->dealer_id !== null && (int) $actor->dealer_id !== (int) $document->dealer_id) {
            return 404;
        }

        return 403;
    }
}
