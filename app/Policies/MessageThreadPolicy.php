<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MessageThread;
use App\Models\User;

class MessageThreadPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::MessagesView->value);
    }

    public function view(User $actor, MessageThread $thread): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        if ($actor->dealer_id === null) {
            return true;
        }

        return (int) $actor->dealer_id === (int) $thread->dealer_id;
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::MessagesSend->value);
    }

    public function import(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::MessagesView->value);
    }

    public function reply(User $actor, MessageThread $thread): bool
    {
        return $this->create($actor) && $this->view($actor, $thread);
    }

    public function deniedStatus(User $actor, MessageThread $thread): int
    {
        if ($actor->dealer_id !== null && (int) $actor->dealer_id !== (int) $thread->dealer_id) {
            return 404;
        }

        return 403;
    }
}
