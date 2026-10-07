<?php

namespace App\Support\Reports;

use App\Enums\DealerApplicationStatus;
use App\Enums\DeliveryStatus;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReportType;
use App\Enums\StockMovementType;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Support\Finance\DeliveredValue;
use App\Support\Finance\LedgerBalance;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Builder;

class Reports
{
    public function __construct(private LedgerBalance $balances) {}

    public function count(ReportQuery $query): int
    {
        return match ($query->type) {
            ReportType::Sales => $this->sales($query)->count(),
            ReportType::Orders => $this->orders($query)->count(),
            ReportType::Stock => $this->stock($query)->count(),
            ReportType::StockMovements => $this->movements($query)->count(),
            ReportType::Dealers => $this->dealers($query)->count(),
            ReportType::DealerSales, ReportType::Ledger => $this->ledger($query)->count(),
            ReportType::Collections => $this->collections($query)->count(),
            ReportType::Deliveries => $this->deliveries($query)->count(),
            ReportType::ProductSales, ReportType::CategorySales, ReportType::BrandSales => $this->soldOrders($query)->count(),
            null => 0,
        };
    }

    public function build(ReportQuery $query): ReportResult
    {
        return match ($query->type) {
            ReportType::Sales => $this->salesResult($query),
            ReportType::Orders => $this->ordersResult($query),
            ReportType::Stock => $this->stockResult($query),
            ReportType::StockMovements => $this->movementResult($query),
            ReportType::Dealers => $this->dealerResult($query),
            ReportType::DealerSales => $this->dealerSalesResult($query),
            ReportType::Ledger => $this->ledgerResult($query),
            ReportType::Collections => $this->collectionResult($query),
            ReportType::Deliveries => $this->deliveryResult($query),
            ReportType::ProductSales => $this->groupedSales($query, 'product'),
            ReportType::CategorySales => $this->groupedSales($query, 'category'),
            ReportType::BrandSales => $this->groupedSales($query, 'brand'),
            null => new ReportResult([], [], []),
        };
    }

    /**
     * @return Builder<LedgerEntry>
     */
    private function sales(ReportQuery $query): Builder
    {
        return $this->ledger($query)->where('type', LedgerType::Sale);
    }

    /**
     * @return Builder<LedgerEntry>
     */
    private function collections(ReportQuery $query): Builder
    {
        $method = PaymentMethod::tryFrom((string) $query->statusValue('collection'));

        return $this->ledger($query)
            ->where('type', LedgerType::Collection)
            ->when($method !== null, fn (Builder $builder) => $builder->where('method', $method));
    }

    /**
     * @return Builder<LedgerEntry>
     */
    private function ledger(ReportQuery $query): Builder
    {
        $type = LedgerType::tryFrom((string) $query->statusValue('ledger'));

        return LedgerEntry::query()
            ->when($type !== null, fn (Builder $builder) => $builder->where('type', $type))
            ->when($query->dealerId !== null, fn (Builder $builder) => $builder->where('dealer_id', $query->dealerId))
            ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('document_date', '>=', $query->start()))
            ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('document_date', '<=', $query->end()))
            ->when($query->productId !== null, fn (Builder $builder) => $builder->whereHas('order.lines', fn (Builder $lines) => $lines->where('product_id', $query->productId)))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('order.lines.product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('order.lines.product', fn (Builder $product) => $product->where('brand_id', $query->brandId)));
    }

    private function salesResult(ReportQuery $query): ReportResult
    {
        $rows = [];
        $total = '0.00';

        foreach ($this->sales($query)->with(['dealer', 'order'])->orderBy('document_date')->orderBy('id')->get() as $entry) {
            $total = Money::add($total, $entry->debit);
            $rows[] = [
                'number' => $entry->number,
                'date' => $entry->document_date->format('d.m.Y'),
                'dealer' => $entry->dealer->company_name,
                'order' => $entry->order?->number ?? '',
                'debit' => Money::format($entry->debit),
            ];
        }

        return $this->table(
            [['number', 'No'], ['date', 'Tarih'], ['dealer', 'Bayi'], ['order', 'Sipariş'], ['debit', 'Tutar']],
            $rows,
            ['debit' => Money::format($total)],
            ['debit'],
        );
    }

    /**
     * @return Builder<Order>
     */
    private function orders(ReportQuery $query): Builder
    {
        $status = OrderStatus::tryFrom((string) $query->statusValue('order'));

        return Order::query()
            ->when($status !== null, fn (Builder $builder) => $builder->where('status', $status))
            ->when($query->dealerId !== null, fn (Builder $builder) => $builder->where('dealer_id', $query->dealerId))
            ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $query->start()))
            ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $query->end()))
            ->when($query->productId !== null, fn (Builder $builder) => $builder->whereHas('lines', fn (Builder $lines) => $lines->where('product_id', $query->productId)))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('lines.product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('lines.product', fn (Builder $product) => $product->where('brand_id', $query->brandId)));
    }

    private function ordersResult(ReportQuery $query): ReportResult
    {
        $rows = [];
        $total = '0.00';

        foreach ($this->orders($query)->with('dealer')->orderBy('id')->get() as $order) {
            $total = Money::add($total, $order->payable);
            $rows[] = [
                'number' => $order->number,
                'date' => $order->created_at->format('d.m.Y'),
                'dealer' => $order->dealer->company_name,
                'status' => $order->status->label(),
                'payable' => Money::format($order->payable),
            ];
        }

        return $this->table(
            [['number', 'Sipariş no'], ['date', 'Tarih'], ['dealer', 'Bayi'], ['status', 'Durum'], ['payable', 'Ödenecek']],
            $rows,
            ['payable' => Money::format($total)],
            ['payable'],
        );
    }

    /**
     * @return Builder<StockLevel>
     */
    private function stock(ReportQuery $query): Builder
    {
        return StockLevel::query()
            ->when($query->productId !== null, fn (Builder $builder) => $builder->where('product_id', $query->productId))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('product', fn (Builder $product) => $product->where('brand_id', $query->brandId)))
            ->when($query->statusValue('stock') === 'critical', function (Builder $builder) {
                $builder->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('products')
                        ->whereColumn('products.id', 'stock_levels.product_id')
                        ->whereColumn('stock_levels.physical_stock', '<=', 'products.critical_stock');
                });
            });
    }

    private function stockResult(ReportQuery $query): ReportResult
    {
        $rows = [];

        foreach ($this->stock($query)->with(['warehouse', 'product'])->orderBy('warehouse_id')->orderBy('product_id')->get() as $level) {
            $rows[] = [
                'warehouse' => $level->warehouse->name,
                'sku' => $level->product->sku,
                'product' => $level->product->name,
                'physical' => (string) $level->physical_stock,
                'reserved' => (string) $level->reserved_stock,
                'available' => (string) $level->available(),
                'critical' => (string) $level->product->critical_stock,
            ];
        }

        return $this->table(
            [['warehouse', 'Depo'], ['sku', 'SKU'], ['product', 'Ürün'], ['physical', 'Fiziki'], ['reserved', 'Rezerve'], ['available', 'Kullanılabilir'], ['critical', 'Kritik']],
            $rows,
            [],
        );
    }

    /**
     * @return Builder<StockMovement>
     */
    private function movements(ReportQuery $query): Builder
    {
        $type = StockMovementType::tryFrom((string) $query->statusValue('movement'));

        return StockMovement::query()
            ->when($type !== null, fn (Builder $builder) => $builder->where('type', $type))
            ->when($query->productId !== null, fn (Builder $builder) => $builder->where('product_id', $query->productId))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('product', fn (Builder $product) => $product->where('brand_id', $query->brandId)))
            ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $query->start()))
            ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $query->end()));
    }

    private function movementResult(ReportQuery $query): ReportResult
    {
        $rows = [];

        foreach ($this->movements($query)->with(['warehouse', 'product'])->orderBy('id')->get() as $movement) {
            $rows[] = [
                'date' => $movement->created_at->format('d.m.Y H:i'),
                'warehouse' => $movement->warehouse->name,
                'sku' => $movement->product->sku,
                'product' => $movement->product->name,
                'type' => $movement->type->label(),
                'quantity' => (string) $movement->quantity,
            ];
        }

        return $this->table(
            [['date', 'Tarih'], ['warehouse', 'Depo'], ['sku', 'SKU'], ['product', 'Ürün'], ['type', 'Tür'], ['quantity', 'Adet']],
            $rows,
            [],
        );
    }

    /**
     * @return Builder<Dealer>
     */
    private function dealers(ReportQuery $query): Builder
    {
        $status = DealerApplicationStatus::tryFrom((string) $query->statusValue('dealer'));

        return Dealer::query()
            ->when($query->dealerId !== null, fn (Builder $builder) => $builder->whereKey($query->dealerId))
            ->when($status !== null, fn (Builder $builder) => $builder->where('application_status', $status))
            ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '>=', $query->start()))
            ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('created_at', '<=', $query->end()))
            ->when($query->productId !== null, fn (Builder $builder) => $builder->whereHas('orders.lines', fn (Builder $lines) => $lines->where('product_id', $query->productId)))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('orders.lines.product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('orders.lines.product', fn (Builder $product) => $product->where('brand_id', $query->brandId)));
    }

    private function dealerResult(ReportQuery $query): ReportResult
    {
        $dealers = $this->dealers($query)->orderBy('company_name')->get();
        $balances = $this->balances->forDealers($dealers->modelKeys());
        $rows = [];
        $total = '0.00';

        foreach ($dealers as $dealer) {
            $balance = $balances[$dealer->id] ?? '0.00';
            $total = Money::add($total, $balance);
            $rows[] = [
                'dealer' => $dealer->company_name,
                'district' => $dealer->district->label(),
                'status' => $dealer->application_status->label(),
                'term' => (string) $dealer->payment_term_days,
                'balance' => Money::format($balance),
            ];
        }

        return $this->table(
            [['dealer', 'Bayi'], ['district', 'İlçe'], ['status', 'Durum'], ['term', 'Vade gün'], ['balance', 'Bakiye']],
            $rows,
            ['balance' => Money::format($total)],
            ['balance'],
        );
    }

    private function dealerSalesResult(ReportQuery $query): ReportResult
    {
        $sales = [];
        $returns = [];

        foreach ($this->sales($query)->get() as $entry) {
            $sales[$entry->dealer_id] = Money::add($sales[$entry->dealer_id] ?? '0.00', $entry->debit);
        }

        foreach ($this->ledger($query)->where('type', LedgerType::Return)->get() as $entry) {
            $returns[$entry->dealer_id] = Money::add($returns[$entry->dealer_id] ?? '0.00', $entry->credit);
        }

        $ids = array_values(array_unique([...array_keys($sales), ...array_keys($returns)]));
        $dealers = Dealer::query()->whereIn('id', $ids)->orderBy('company_name')->get();
        $rows = [];
        $saleTotal = '0.00';
        $returnTotal = '0.00';
        $netTotal = '0.00';

        foreach ($dealers as $dealer) {
            $sale = $sales[$dealer->id] ?? '0.00';
            $return = $returns[$dealer->id] ?? '0.00';
            $net = Money::sub($sale, $return);
            $saleTotal = Money::add($saleTotal, $sale);
            $returnTotal = Money::add($returnTotal, $return);
            $netTotal = Money::add($netTotal, $net);
            $rows[] = [
                'dealer' => $dealer->company_name,
                'sales' => Money::format($sale),
                'returns' => Money::format($return),
                'net' => Money::format($net),
            ];
        }

        return $this->table(
            [['dealer', 'Bayi'], ['sales', 'Satış'], ['returns', 'İade'], ['net', 'Net']],
            $rows,
            ['sales' => Money::format($saleTotal), 'returns' => Money::format($returnTotal), 'net' => Money::format($netTotal)],
            ['sales', 'returns', 'net'],
        );
    }

    private function ledgerResult(ReportQuery $query): ReportResult
    {
        $rows = [];
        $debit = '0.00';
        $credit = '0.00';

        foreach ($this->ledger($query)->with('dealer')->orderBy('document_date')->orderBy('id')->get() as $entry) {
            $debit = Money::add($debit, $entry->debit);
            $credit = Money::add($credit, $entry->credit);
            $rows[] = [
                'number' => $entry->number,
                'date' => $entry->document_date->format('d.m.Y'),
                'dealer' => $entry->dealer->company_name,
                'type' => $entry->type->label(),
                'debit' => Money::format($entry->debit),
                'credit' => Money::format($entry->credit),
                'due' => $entry->due_on?->format('d.m.Y') ?? '',
            ];
        }

        return $this->table(
            [['number', 'No'], ['date', 'Tarih'], ['dealer', 'Bayi'], ['type', 'Tür'], ['debit', 'Borç'], ['credit', 'Alacak'], ['due', 'Vade']],
            $rows,
            ['debit' => Money::format($debit), 'credit' => Money::format($credit)],
            ['debit', 'credit'],
        );
    }

    private function collectionResult(ReportQuery $query): ReportResult
    {
        $rows = [];
        $total = '0.00';

        foreach ($this->collections($query)->with('dealer')->orderBy('document_date')->orderBy('id')->get() as $entry) {
            $total = Money::add($total, $entry->credit);
            $rows[] = [
                'number' => $entry->number,
                'date' => $entry->document_date->format('d.m.Y'),
                'dealer' => $entry->dealer->company_name,
                'method' => $entry->method?->label() ?? '',
                'credit' => Money::format($entry->credit),
            ];
        }

        return $this->table(
            [['number', 'No'], ['date', 'Tarih'], ['dealer', 'Bayi'], ['method', 'Yöntem'], ['credit', 'Tutar']],
            $rows,
            ['credit' => Money::format($total)],
            ['credit'],
        );
    }

    /**
     * @return Builder<Delivery>
     */
    private function deliveries(ReportQuery $query): Builder
    {
        $status = DeliveryStatus::tryFrom((string) $query->statusValue('delivery'));

        return Delivery::query()
            ->when($status !== null, fn (Builder $builder) => $builder->where('status', $status))
            ->when($query->dealerId !== null, fn (Builder $builder) => $builder->where('dealer_id', $query->dealerId))
            ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('scheduled_on', '>=', $query->start()))
            ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('scheduled_on', '<=', $query->end()))
            ->when($query->productId !== null, fn (Builder $builder) => $builder->whereHas('lines.orderLine', fn (Builder $line) => $line->where('product_id', $query->productId)))
            ->when($query->categoryId !== null, fn (Builder $builder) => $builder->whereHas('lines.orderLine.product', fn (Builder $product) => $product->where('category_id', $query->categoryId)))
            ->when($query->brandId !== null, fn (Builder $builder) => $builder->whereHas('lines.orderLine.product', fn (Builder $product) => $product->where('brand_id', $query->brandId)));
    }

    private function deliveryResult(ReportQuery $query): ReportResult
    {
        $rows = [];

        foreach ($this->deliveries($query)->with(['order', 'dealer', 'driver'])->orderBy('scheduled_on')->orderBy('sequence')->get() as $delivery) {
            $rows[] = [
                'date' => $delivery->scheduled_on->format('d.m.Y'),
                'order' => $delivery->order->number,
                'dealer' => $delivery->dealer->company_name,
                'status' => $delivery->status->label(),
                'driver' => $delivery->driver->name,
                'recipient' => (string) $delivery->recipient_name,
            ];
        }

        return $this->table(
            [['date', 'Tarih'], ['order', 'Sipariş'], ['dealer', 'Bayi'], ['status', 'Durum'], ['driver', 'Şoför'], ['recipient', 'Teslim alan']],
            $rows,
            [],
        );
    }

    /**
     * @return Builder<Order>
     */
    private function soldOrders(ReportQuery $query): Builder
    {
        return Order::query()
            ->when($query->dealerId !== null, fn (Builder $builder) => $builder->where('dealer_id', $query->dealerId))
            ->whereHas('ledgerEntries', function (Builder $entries) use ($query) {
                $entries->where('type', LedgerType::Sale)
                    ->when($query->start() !== null, fn (Builder $builder) => $builder->whereDate('document_date', '>=', $query->start()))
                    ->when($query->end() !== null, fn (Builder $builder) => $builder->whereDate('document_date', '<=', $query->end()));
            });
    }

    private function groupedSales(ReportQuery $query, string $group): ReportResult
    {
        $buckets = [];

        foreach ($this->soldOrders($query)->with(['lines.product.brand', 'lines.product.category'])->get() as $order) {
            foreach ($this->allocate($order) as $line) {
                if ($query->productId !== null && $line['product_id'] !== $query->productId) {
                    continue;
                }

                if ($query->categoryId !== null && $line['category_id'] !== $query->categoryId) {
                    continue;
                }

                if ($query->brandId !== null && $line['brand_id'] !== $query->brandId) {
                    continue;
                }

                $key = match ($group) {
                    'category' => (string) ($line['category'] !== '' ? $line['category'] : 'Kategorisiz'),
                    'brand' => (string) ($line['brand'] !== '' ? $line['brand'] : 'Markasız'),
                    default => $line['sku'].' '.$line['product'],
                };
                $buckets[$key]['pieces'] = ($buckets[$key]['pieces'] ?? 0) + $line['pieces'];
                $buckets[$key]['amount'] = Money::add($buckets[$key]['amount'] ?? '0.00', $line['amount']);
                $buckets[$key]['sku'] = $line['sku'];
                $buckets[$key]['product'] = $line['product'];
            }
        }

        uasort($buckets, fn (array $left, array $right) => bccomp($right['amount'], $left['amount'], 2));

        $rows = [];
        $pieces = 0;
        $amount = '0.00';

        foreach ($buckets as $name => $bucket) {
            $pieces += $bucket['pieces'];
            $amount = Money::add($amount, $bucket['amount']);
            $rows[] = $group === 'product'
                ? ['sku' => $bucket['sku'], 'product' => $bucket['product'], 'pieces' => (string) $bucket['pieces'], 'amount' => Money::format($bucket['amount'])]
                : ['name' => $name, 'pieces' => (string) $bucket['pieces'], 'amount' => Money::format($bucket['amount'])];
        }

        $columns = $group === 'product'
            ? [['sku', 'SKU'], ['product', 'Ürün'], ['pieces', 'Adet'], ['amount', 'Tutar']]
            : [['name', $group === 'brand' ? 'Marka' : 'Kategori'], ['pieces', 'Adet'], ['amount', 'Tutar']];

        return $this->table($columns, $rows, ['pieces' => (string) $pieces, 'amount' => Money::format($amount)], ['amount']);
    }

    /**
     * @return list<array{product_id: int, sku: string, product: string, category: string, brand: string, category_id: ?int, brand_id: ?int, pieces: int, amount: string}>
     */
    private function allocate(Order $order): array
    {
        $pieces = [];

        foreach ($order->lines as $line) {
            $kept = $line->delivered_pieces - $line->returned_pieces;

            if ($kept > 0) {
                $pieces[$line->id] = $kept;
            }
        }

        if ($pieces === []) {
            return [];
        }

        $payable = DeliveredValue::payable($order, $pieces);
        $weight = $this->weight($order, $pieces);
        $allocated = '0.00';
        $ids = array_keys($pieces);
        $last = count($ids) - 1;
        $rows = [];

        foreach ($ids as $index => $id) {
            $line = $order->lines->firstWhere('id', $id);
            $share = $index === $last || bccomp($weight, '0.00', 2) === 0
                ? Money::sub($payable, $allocated)
                : Money::round(bcdiv(bcmul($payable, DeliveredValue::lineGross($line, $pieces[$id]), 6), $weight, 6));

            if ($index !== $last) {
                $allocated = Money::add($allocated, $share);
            }

            $rows[] = [
                'product_id' => $line->product_id,
                'sku' => $line->sku,
                'product' => $line->product_name,
                'category' => $line->product?->category?->name ?? '',
                'brand' => $line->product?->brand?->name ?? '',
                'category_id' => $line->product?->category_id,
                'brand_id' => $line->product?->brand_id,
                'pieces' => $pieces[$id],
                'amount' => $share,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, int>  $pieces
     */
    private function weight(Order $order, array $pieces): string
    {
        $weight = '0.00';

        foreach ($order->lines as $line) {
            $kept = $pieces[$line->id] ?? 0;

            if ($kept > 0) {
                $weight = Money::add($weight, DeliveredValue::lineGross($line, $kept));
            }
        }

        return $weight;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $columns
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string>  $totals
     * @param  list<string>  $moneyKeys
     */
    private function table(array $columns, array $rows, array $totals, array $moneyKeys = []): ReportResult
    {
        return new ReportResult(
            array_map(fn (array $column) => ['key' => $column[0], 'label' => $column[1]], $columns),
            $rows,
            $totals,
            $moneyKeys,
        );
    }
}
