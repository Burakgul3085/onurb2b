<?php

namespace App\Actions\Dealers;

use App\Actions\Audit\RecordAudit;
use App\Actions\Mail\Notify;
use App\Enums\AuditAction;
use App\Enums\DealerApplicationStatus;
use App\Models\Dealer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectDealer
{
    public function execute(Dealer $dealer, string $reason): Dealer
    {
        if ($dealer->application_status !== DealerApplicationStatus::Pending) {
            throw ValidationException::withMessages([
                'application_status' => __('This application has already been decided.'),
            ]);
        }

        return DB::transaction(function () use ($dealer, $reason) {
            $dealer->update([
                'application_status' => DealerApplicationStatus::Rejected,
                'is_active' => false,
                'rejection_reason' => $reason,
            ]);

            $dealer->users()->update(['is_active' => false]);

            app(RecordAudit::class)->write(
                AuditAction::DealerRejected,
                $dealer,
                $dealer->company_name,
                ['application_status' => DealerApplicationStatus::Pending->value],
                ['application_status' => DealerApplicationStatus::Rejected->value, 'reason' => $reason],
            );

            DB::afterCommit(fn () => app(Notify::class)->dealerRejected($dealer->id));

            return $dealer;
        });
    }
}
