<?php

namespace App\Support\Dashboard;

use App\Enums\DealerApplicationStatus;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\ReportPeriod;
use App\Enums\ReportType;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\StockLevel;
use App\Models\User;
use App\Support\Finance\LedgerBalance;
use App\Support\Money\Money;
use App\Support\Reports\ReportQuery;
use App\Support\Reports\Reports;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetrics
{
    public function __construct(
        private LedgerBalance $balances,
        private Reports $reports,
    ) {}

    /**
     * @return array{period: ReportPeriod, from: ?string, to: ?string, kpis: list<array{label: string, value: string}>, charts: list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>, dues: list<array{label: string, meta: string, href: string}>, messages: list<array{label: string, meta: string, href: string}>, lastOrder: ?array{number: string, status: string, href: string}}
     */
    public function for(User $user, ReportPeriod $period, ?string $from, ?string $to): array
    {
        [$start, $end] = $period->bounds($from, $to);
        $dealerId = $user->dealer_id !== null ? (int) $user->dealer_id : null;

        if ($dealerId !== null) {
            return $this->payload($period, $from, $to, $this->dealerKpis($dealerId, $start, $end), $this->dealerCharts($dealerId, $period, $from, $to, $start, $end), $this->dues($dealerId), $this->messages($dealerId), $this->lastOrder($dealerId));
        }

        if ($user->can(Permission::ReportsView->value)) {
            return $this->payload($period, $from, $to, $this->adminKpis($start, $end), $this->adminCharts($period, $from, $to, $start, $end), $this->dues(null), [], null);
        }

        return $this->payload($period, $from, $to, $this->operationsKpis($user), $this->operationsCharts($user, $start, $end), [], [], null);
    }

    /**
     * @param  list<array{label: string, value: string}>  $kpis
     * @param  list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>  $charts
     * @param  list<array{label: string, meta: string, href: string}>  $dues
     * @param  list<array{label: string, meta: string, href: string}>  $messages
     * @param  ?array{number: string, status: string, href: string}  $lastOrder
     * @return array{period: ReportPeriod, from: ?string, to: ?string, kpis: list<array{label: string, value: string}>, charts: list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>, dues: list<array{label: string, meta: string, href: string}>, messages: list<array{label: string, meta: string, href: string}>, lastOrder: ?array{number: string, status: string, href: string}}
     */
    private function payload(ReportPeriod $period, ?string $from, ?string $to, array $kpis, array $charts, array $dues, array $messages, ?array $lastOrder): array
    {
        return [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'kpis' => $kpis,
            'charts' => $charts,
            'dues' => $dues,
            'messages' => $messages,
            'lastOrder' => $lastOrder,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function adminKpis(?string $start, ?string $end): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            ['label' => __('Today sales'), 'value' => Money::format($this->sales(null, $today, $today)).' ₺'],
            ['label' => __('Month sales'), 'value' => Money::format($this->sales(null, $monthStart, $today)).' ₺'],
            ['label' => __('Order count'), 'value' => (string) $this->orderCount(null, $start, $end)],
            ['label' => __('Active dealers'), 'value' => (string) Dealer::query()->where('application_status', DealerApplicationStatus::Approved)->where('is_active', true)->count()],
            ['label' => __('Pending orders'), 'value' => (string) Order::query()->where('status', OrderStatus::Pending)->count()],
            ['label' => __('Out for delivery'), 'value' => (string) Delivery::query()->where('status', DeliveryStatus::OutForDelivery)->count()],
            ['label' => __('Critical stock'), 'value' => (string) $this->criticalStock()->count()],
            ['label' => __('Total receivable'), 'value' => Money::format($this->receivable()).' ₺'],
            ['label' => __('Upcoming due dates'), 'value' => (string) $this->dueQuery(null)->count()],
        ];
    }

    /**
     * @return list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>
     */
    private function adminCharts(ReportPeriod $period, ?string $from, ?string $to, ?string $start, ?string $end): array
    {
        return [
            ['title' => __('Sales over time'), 'rows' => $this->bars($this->salesSeries(null, $period, $start, $end), true)],
            ['title' => __('Order statuses'), 'rows' => $this->bars($this->orderStatuses(null, $start, $end), false)],
            ['title' => __('Top dealers'), 'rows' => $this->bars($this->topDealers($start, $end), true)],
            ['title' => __('Top products'), 'rows' => $this->bars($this->topProducts(null, $period, $from, $to), true)],
            ['title' => __('Critical stock'), 'rows' => $this->criticalBars()],
            ['title' => __('Collections and sales'), 'rows' => $this->bars([
                ['label' => __('Sales'), 'amount' => $this->sales(null, $start, $end)],
                ['label' => __('Collection'), 'amount' => $this->collections(null, $start, $end)],
            ], true)],
            ['title' => __('Delivery statuses'), 'rows' => $this->bars($this->deliveryStatuses(null, $start, $end), false)],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function dealerKpis(int $dealerId, ?string $start, ?string $end): array
    {
        return [
            ['label' => __('Balance'), 'value' => Money::format($this->balances->forDealer($dealerId)).' ₺'],
            ['label' => __('Pending orders'), 'value' => (string) Order::query()->where('dealer_id', $dealerId)->where('status', OrderStatus::Pending)->count()],
            ['label' => __('Delivered orders'), 'value' => (string) Order::query()->where('dealer_id', $dealerId)->where('status', OrderStatus::Delivered)->count()],
            ['label' => __('Total purchases'), 'value' => Money::format($this->sales($dealerId, $start, $end)).' ₺'],
            ['label' => __('Upcoming due dates'), 'value' => (string) $this->dueQuery($dealerId)->count()],
        ];
    }

    /**
     * @return list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>
     */
    private function dealerCharts(int $dealerId, ReportPeriod $period, ?string $from, ?string $to, ?string $start, ?string $end): array
    {
        return [
            ['title' => __('Sales over time'), 'rows' => $this->bars($this->salesSeries($dealerId, $period, $start, $end), true)],
            ['title' => __('Order statuses'), 'rows' => $this->bars($this->orderStatuses($dealerId, $start, $end), false)],
            ['title' => __('Top products'), 'rows' => $this->bars($this->topProducts($dealerId, $period, $from, $to), true)],
            ['title' => __('Collections and sales'), 'rows' => $this->bars([
                ['label' => __('Sales'), 'amount' => $this->sales($dealerId, $start, $end)],
                ['label' => __('Collection'), 'amount' => $this->collections($dealerId, $start, $end)],
            ], true)],
            ['title' => __('Delivery statuses'), 'rows' => $this->bars($this->deliveryStatuses($dealerId, $start, $end), false)],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function operationsKpis(User $user): array
    {
        $kpis = [];

        if ($user->can('viewAny', Order::class)) {
            $kpis[] = ['label' => __('Pending orders'), 'value' => (string) Order::query()->where('status', OrderStatus::Pending)->count()];

            if (! $user->can('viewAny', Delivery::class)) {
                $kpis[] = ['label' => __('Out for delivery'), 'value' => (string) Order::query()->where('status', OrderStatus::OutForDelivery)->count()];
            }
        }

        if ($user->can('viewStock')) {
            $kpis[] = ['label' => __('Critical stock'), 'value' => (string) $this->criticalStock()->count()];
        }

        if ($user->can('viewAny', Delivery::class)) {
            $kpis[] = ['label' => __('Out for delivery'), 'value' => (string) Delivery::query()->where('status', DeliveryStatus::OutForDelivery)->count()];
        }

        return $kpis;
    }

    /**
     * @return list<array{title: string, rows: list<array{label: string, display: string, width: int}>}>
     */
    private function operationsCharts(User $user, ?string $start, ?string $end): array
    {
        $charts = [];

        if ($user->can('viewAny', Order::class)) {
            $charts[] = ['title' => __('Order statuses'), 'rows' => $this->bars($this->orderStatuses(null, $start, $end), false)];
        }

        if ($user->can('viewStock')) {
            $charts[] = ['title' => __('Critical stock'), 'rows' => $this->criticalBars()];
        }

        if ($user->can('viewAny', Delivery::class)) {
            $charts[] = ['title' => __('Delivery statuses'), 'rows' => $this->bars($this->deliveryStatuses(null, $start, $end), false)];
        }

        return $charts;
    }

    private function sales(?int $dealerId, ?string $start, ?string $end): string
    {
        $total = '0.00';

        LedgerEntry::query()
            ->where('type', LedgerType::Sale)
            ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
            ->when($start !== null, fn ($query) => $query->whereDate('document_date', '>=', $start))
            ->when($end !== null, fn ($query) => $query->whereDate('document_date', '<=', $end))
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$total): void {
                $total = Money::add($total, $entry->debit);
            });

        return $total;
    }

    private function collections(?int $dealerId, ?string $start, ?string $end): string
    {
        $total = '0.00';

        LedgerEntry::query()
            ->where('type', LedgerType::Collection)
            ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
            ->when($start !== null, fn ($query) => $query->whereDate('document_date', '>=', $start))
            ->when($end !== null, fn ($query) => $query->whereDate('document_date', '<=', $end))
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$total): void {
                $total = Money::add($total, $entry->credit);
            });

        return $total;
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    private function salesSeries(?int $dealerId, ReportPeriod $period, ?string $start, ?string $end): array
    {
        $monthly = in_array($period, [ReportPeriod::ThisYear, ReportPeriod::All], true);
        $buckets = [];

        LedgerEntry::query()
            ->where('type', LedgerType::Sale)
            ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
            ->when($start !== null, fn ($query) => $query->whereDate('document_date', '>=', $start))
            ->when($end !== null, fn ($query) => $query->whereDate('document_date', '<=', $end))
            ->orderBy('document_date')
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$buckets, $monthly): void {
                $key = $monthly ? $entry->document_date->format('Y-m') : $entry->document_date->toDateString();
                $buckets[$key]['label'] = $monthly ? $entry->document_date->format('m.Y') : $entry->document_date->format('d.m');
                $buckets[$key]['amount'] = Money::add($buckets[$key]['amount'] ?? '0.00', $entry->debit);
            });

        ksort($buckets);

        return array_values($buckets);
    }

    private function orderCount(?int $dealerId, ?string $start, ?string $end): int
    {
        return Order::query()
            ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
            ->when($start !== null, fn ($query) => $query->whereDate('created_at', '>=', $start))
            ->when($end !== null, fn ($query) => $query->whereDate('created_at', '<=', $end))
            ->count();
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    private function orderStatuses(?int $dealerId, ?string $start, ?string $end): array
    {
        $rows = [];

        foreach (OrderStatus::cases() as $status) {
            $count = Order::query()
                ->where('status', $status)
                ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
                ->when($start !== null, fn ($query) => $query->whereDate('created_at', '>=', $start))
                ->when($end !== null, fn ($query) => $query->whereDate('created_at', '<=', $end))
                ->count();

            if ($count > 0) {
                $rows[] = ['label' => $status->label(), 'amount' => (string) $count];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    private function deliveryStatuses(?int $dealerId, ?string $start, ?string $end): array
    {
        $rows = [];

        foreach (DeliveryStatus::cases() as $status) {
            $count = Delivery::query()
                ->where('status', $status)
                ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId))
                ->when($start !== null, fn ($query) => $query->whereDate('scheduled_on', '>=', $start))
                ->when($end !== null, fn ($query) => $query->whereDate('scheduled_on', '<=', $end))
                ->count();

            if ($count > 0) {
                $rows[] = ['label' => $status->label(), 'amount' => (string) $count];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    private function topDealers(?string $start, ?string $end): array
    {
        $totals = [];

        LedgerEntry::query()
            ->where('type', LedgerType::Sale)
            ->when($start !== null, fn ($query) => $query->whereDate('document_date', '>=', $start))
            ->when($end !== null, fn ($query) => $query->whereDate('document_date', '<=', $end))
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$totals): void {
                $totals[$entry->dealer_id] = Money::add($totals[$entry->dealer_id] ?? '0.00', $entry->debit);
            });

        uasort($totals, fn (string $left, string $right) => bccomp($right, $left, 2));
        $totals = array_slice($totals, 0, 5, true);
        $names = Dealer::query()->whereIn('id', array_keys($totals))->pluck('company_name', 'id');
        $rows = [];

        foreach ($totals as $id => $amount) {
            $rows[] = ['label' => (string) $names[$id], 'amount' => $amount];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    private function topProducts(?int $dealerId, ReportPeriod $period, ?string $from, ?string $to): array
    {
        return $this->reports->topProducts(new ReportQuery(
            type: ReportType::ProductSales,
            period: $period,
            from: $from,
            to: $to,
            dealerId: $dealerId,
            productId: null,
            categoryId: null,
            brandId: null,
            status: null,
        ));
    }

    private function receivable(): string
    {
        $total = '0.00';

        foreach ($this->balances->forDealers(Dealer::query()->pluck('id')) as $balance) {
            $total = Money::add($total, $balance);
        }

        return $total;
    }

    /**
     * @return Builder<StockLevel>
     */
    private function criticalStock()
    {
        return StockLevel::query()->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('products')
                ->whereColumn('products.id', 'stock_levels.product_id')
                ->whereColumn('stock_levels.physical_stock', '<=', 'products.critical_stock');
        });
    }

    /**
     * @return list<array{label: string, amount: string, display: string}>
     */
    private function criticalBars(): array
    {
        $rows = [];

        foreach ($this->criticalStock()->with(['product', 'warehouse'])->orderBy('physical_stock')->limit(5)->get() as $level) {
            $rows[] = [
                'label' => $level->product->sku.' — '.$level->warehouse->name,
                'amount' => (string) max($level->physical_stock, 0),
                'display' => (string) $level->physical_stock,
            ];
        }

        return $this->bars($rows, false);
    }

    /**
     * @return Builder<LedgerEntry>
     */
    private function dueQuery(?int $dealerId)
    {
        return LedgerEntry::query()
            ->where('type', LedgerType::Sale)
            ->whereDate('due_on', '<=', now()->addDays(7)->toDateString())
            ->whereDoesntHave('reversal')
            ->when($dealerId !== null, fn ($query) => $query->where('dealer_id', $dealerId));
    }

    /**
     * @return list<array{label: string, meta: string, href: string}>
     */
    private function dues(?int $dealerId): array
    {
        return $this->dueQuery($dealerId)
            ->with('dealer')
            ->orderBy('due_on')
            ->limit(5)
            ->get()
            ->map(fn (LedgerEntry $entry) => [
                'label' => $entry->number.' · '.Money::format($entry->debit).' ₺',
                'meta' => ($dealerId === null ? $entry->dealer->company_name.' · ' : '').$entry->due_on?->format('d.m.Y'),
                'href' => route('finance.show', $entry->dealer_id),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string, href: string}>
     */
    private function messages(int $dealerId): array
    {
        return MessageThread::query()
            ->where('dealer_id', $dealerId)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (MessageThread $thread) => [
                'label' => $thread->subject,
                'meta' => $thread->created_at->format('d.m.Y H:i'),
                'href' => route('messages.show', $thread),
            ])
            ->all();
    }

    /**
     * @return ?array{number: string, status: string, href: string}
     */
    private function lastOrder(int $dealerId): ?array
    {
        $order = Order::query()->where('dealer_id', $dealerId)->latest('id')->first();

        if ($order === null) {
            return null;
        }

        return [
            'number' => $order->number,
            'status' => $order->status->label(),
            'href' => route('orders.show', $order),
        ];
    }

    /**
     * @param  list<array{label: string, amount: string, display?: string}>  $rows
     * @return list<array{label: string, display: string, width: int}>
     */
    private function bars(array $rows, bool $money): array
    {
        $max = '0';

        foreach ($rows as $row) {
            if (bccomp($row['amount'], $max, 2) === 1) {
                $max = $row['amount'];
            }
        }

        return array_map(function (array $row) use ($max, $money) {
            $width = bccomp($max, '0', 2) === 1
                ? (int) bcmul(bcdiv($row['amount'], $max, 4), '100', 0)
                : 0;

            return [
                'label' => $row['label'],
                'display' => $row['display'] ?? ($money ? Money::format($row['amount']).' ₺' : $row['amount']),
                'width' => $width > 0 ? max($width, 4) : 0,
            ];
        }, $rows);
    }
}
