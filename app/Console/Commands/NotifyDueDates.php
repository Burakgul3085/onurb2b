<?php

namespace App\Console\Commands;

use App\Actions\Mail\Notify;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use Illuminate\Console\Command;

class NotifyDueDates extends Command
{
    protected $signature = 'ops:notify-dues';

    protected $description = 'Mail dealers whose sale due date falls within seven days';

    public function handle(Notify $notify): int
    {
        $sent = 0;

        LedgerEntry::query()
            ->with('dealer')
            ->where('type', LedgerType::Sale)
            ->whereNull('due_notified_at')
            ->whereDate('due_on', '>=', now()->toDateString())
            ->whereDate('due_on', '<=', now()->addDays(7)->toDateString())
            ->whereDoesntHave('reversal')
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use ($notify, &$sent) {
                if (! $notify->dueDateApproaching($entry)) {
                    return;
                }

                $entry->forceFill(['due_notified_at' => now()])->save();
                $sent++;
            });

        $this->info("Due notices queued: {$sent}");

        return self::SUCCESS;
    }
}
