<?php

namespace App\Actions\Dealers;

use App\Actions\Audit\RecordAudit;
use App\Actions\Mail\Notify;
use App\Actions\Users\CreateUser;
use App\Enums\AuditAction;
use App\Enums\DealerApplicationStatus;
use App\Enums\Role;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveDealer
{
    public function __construct(private CreateUser $createUser) {}

    public function execute(User $actor, Dealer $dealer, string $password): User
    {
        if ($dealer->application_status !== DealerApplicationStatus::Pending) {
            throw ValidationException::withMessages([
                'application_status' => __('This application has already been decided.'),
            ]);
        }

        if ($dealer->users()->exists()) {
            throw ValidationException::withMessages([
                'email' => __('A login user is already linked to this dealer.'),
            ]);
        }

        if (User::query()->where('email', $dealer->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This email is already used by a user.'),
            ]);
        }

        return DB::transaction(function () use ($actor, $dealer, $password) {
            $user = $this->createUser->execute([
                'name' => $dealer->contact_name,
                'email' => $dealer->email,
                'password' => $password,
                'roles' => [Role::Dealer->value],
                'is_active' => true,
                'dealer_id' => $dealer->id,
            ]);

            $dealer->update([
                'application_status' => DealerApplicationStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'rejection_reason' => null,
            ]);

            app(RecordAudit::class)->write(
                AuditAction::DealerApproved,
                $dealer,
                $dealer->company_name,
                ['application_status' => DealerApplicationStatus::Pending->value],
                ['application_status' => DealerApplicationStatus::Approved->value],
                $actor,
            );

            DB::afterCommit(function () use ($dealer, $user) {
                app(Notify::class)->dealerApproved($dealer->id);
                app(Notify::class)->accountActivated($user->id);
            });

            return $user;
        });
    }
}
