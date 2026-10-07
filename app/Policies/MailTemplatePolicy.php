<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MailTemplate;
use App\Models\User;

class MailTemplatePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->dealer_id === null && $actor->can(Permission::SettingsManage->value);
    }

    public function update(User $actor, MailTemplate $template): bool
    {
        return $this->viewAny($actor);
    }
}
