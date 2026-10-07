<?php

namespace App\Console\Commands;

use App\Actions\Mail\Notify;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\User;
use App\Support\Money\Money;
use Illuminate\Console\Command;

class SendDailyReport extends Command
{
    protected $signature = 'ops:daily-report';

    protected $description = 'Mail yesterday’s sales total and order count to report staff';

    public function handle(Notify $notify): int
    {
        $day = now()->subDay();
        $date = $day->toDateString();
        $sales = '0.00';

        foreach (LedgerEntry::query()->where('type', LedgerType::Sale)->whereDate('document_date', $date)->cursor() as $entry) {
            $sales = Money::add($sales, (string) $entry->debit);
        }

        $orders = Order::query()
            ->whereDate('created_at', $date)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->count();

        $sent = 0;

        foreach ($this->staff() as $user) {
            if ($notify->dailyReport($user, $day->format('d.m.Y'), Money::format($sales).' ₺', $orders)) {
                $sent++;
            }
        }

        $this->info("Daily reports queued: {$sent}");

        return self::SUCCESS;
    }

    /**
     * @return list<User>
     */
    private function staff(): array
    {
        return User::query()
            ->whereNull('dealer_id')
            ->where('is_active', true)
            ->permission(Permission::ReportsView->value)
            ->get()
            ->unique('email')
            ->values()
            ->all();
    }
}
