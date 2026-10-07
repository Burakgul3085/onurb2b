<?php

namespace App\Console\Commands;

use App\Actions\Mail\Notify;
use App\Enums\Permission;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Console\Command;

class NotifyCriticalStock extends Command
{
    protected $signature = 'ops:critical-stock';

    protected $description = 'Mail stock staff a digest of products at or below the critical level';

    public function handle(Notify $notify): int
    {
        $lines = [];

        $levels = StockLevel::query()
            ->with(['product', 'warehouse'])
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.id', 'stock_levels.product_id')
                    ->where('products.is_active', true)
                    ->whereColumn('stock_levels.physical_stock', '<=', 'products.critical_stock');
            })
            ->orderBy('physical_stock')
            ->limit(30)
            ->get();

        foreach ($levels as $level) {
            if ($level->product === null || $level->warehouse === null) {
                continue;
            }

            $lines[] = $level->product->sku.' — '.$level->warehouse->name.' — '.$level->physical_stock.' adet';
        }

        if ($lines === []) {
            $this->info('No critical stock.');

            return self::SUCCESS;
        }

        $body = implode("\n", $lines);
        $sent = 0;

        foreach ($this->staff(Permission::StockView->value) as $user) {
            if ($notify->criticalStock($user, $body)) {
                $sent++;
            }
        }

        $this->info("Critical stock notices queued: {$sent}");

        return self::SUCCESS;
    }

    /**
     * @return list<User>
     */
    private function staff(string $permission): array
    {
        return User::query()
            ->whereNull('dealer_id')
            ->where('is_active', true)
            ->permission($permission)
            ->get()
            ->unique('email')
            ->values()
            ->all();
    }
}
