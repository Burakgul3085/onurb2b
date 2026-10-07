<?php

namespace App\Services\Pricing;

use App\Data\Prices\PriceQuote;
use App\Enums\PriceSource;
use App\Models\Dealer;
use App\Models\DealerPrice;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Support\Money\Discount;
use App\Support\Money\Vat;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PriceResolver
{
    public function quote(?Dealer $dealer, Product $product, int $quantity, ?CarbonInterface $on = null): PriceQuote
    {
        $on = $this->day($on);
        $quantity = max(1, $quantity);
        $documentDiscount = $this->documentDiscount($dealer);

        $special = $dealer === null ? null : $this->pick(
            DealerPrice::query()->where('dealer_id', $dealer->id)->where('product_id', $product->id)->where('is_active', true)->get(),
            $quantity,
            $on,
        );

        if ($special instanceof DealerPrice) {
            return $this->fromRecord($product, PriceSource::Dealer, $special->price, $special->discount_percent, $special->prices_include_vat, $special->minimum_quantity, $documentDiscount);
        }

        $list = $this->activeList($dealer);
        $item = $list === null ? null : $this->pick(
            PriceListItem::query()->where('price_list_id', $list->id)->where('product_id', $product->id)->where('is_active', true)->get(),
            $quantity,
            $on,
        );

        if ($item instanceof PriceListItem) {
            return $this->fromRecord($product, PriceSource::List, $item->price, $item->discount_percent, $item->prices_include_vat, $item->minimum_quantity, $documentDiscount);
        }

        return $this->fromRecord($product, PriceSource::Product, $product->sale_price, '0', $product->prices_include_vat, 1, $documentDiscount);
    }

    /**
     * @param  iterable<Product>  $products
     * @return array<int, PriceQuote>
     */
    public function quoteMany(?Dealer $dealer, iterable $products, int $quantity, ?CarbonInterface $on = null): array
    {
        return $this->quoteEach($dealer, $products, static fn (Product $product): int => $quantity, $on);
    }

    /**
     * @param  iterable<Product>  $products
     * @param  callable(Product): int  $piecesFor
     * @return array<int, PriceQuote>
     */
    public function quoteEach(?Dealer $dealer, iterable $products, callable $piecesFor, ?CarbonInterface $on = null): array
    {
        $products = Collection::make($products);
        $ids = $products->pluck('id')->all();
        $on = $this->day($on);
        $documentDiscount = $this->documentDiscount($dealer);
        $list = $this->activeList($dealer);

        $specials = $dealer === null
            ? collect()
            : DealerPrice::query()->where('dealer_id', $dealer->id)->whereIn('product_id', $ids)->where('is_active', true)->get()->groupBy('product_id');

        $items = $list === null
            ? collect()
            : PriceListItem::query()->where('price_list_id', $list->id)->whereIn('product_id', $ids)->where('is_active', true)->get()->groupBy('product_id');

        $quotes = [];

        foreach ($products as $product) {
            $quantity = max(1, $piecesFor($product));
            $special = $this->pick($specials->get($product->id, collect()), $quantity, $on);

            if ($special instanceof DealerPrice) {
                $quotes[$product->id] = $this->fromRecord($product, PriceSource::Dealer, $special->price, $special->discount_percent, $special->prices_include_vat, $special->minimum_quantity, $documentDiscount);

                continue;
            }

            $item = $this->pick($items->get($product->id, collect()), $quantity, $on);

            if ($item instanceof PriceListItem) {
                $quotes[$product->id] = $this->fromRecord($product, PriceSource::List, $item->price, $item->discount_percent, $item->prices_include_vat, $item->minimum_quantity, $documentDiscount);

                continue;
            }

            $quotes[$product->id] = $this->fromRecord($product, PriceSource::Product, $product->sale_price, '0', $product->prices_include_vat, 1, $documentDiscount);
        }

        return $quotes;
    }

    /**
     * @param  Collection<int, DealerPrice|PriceListItem>  $rows
     */
    private function pick(Collection $rows, int $quantity, CarbonInterface $on): DealerPrice|PriceListItem|null
    {
        $day = $on->toDateString();

        return $rows
            ->filter(function (DealerPrice|PriceListItem $row) use ($quantity, $day) {
                if ($row->minimum_quantity > $quantity) {
                    return false;
                }

                $starts = $row->starts_at?->toDateString();
                $ends = $row->ends_at?->toDateString();

                return ($starts === null || $starts <= $day) && ($ends === null || $ends >= $day);
            })
            ->sort(function (DealerPrice|PriceListItem $left, DealerPrice|PriceListItem $right) {
                if ($left->minimum_quantity !== $right->minimum_quantity) {
                    return $right->minimum_quantity <=> $left->minimum_quantity;
                }

                $start = ($right->starts_at?->timestamp ?? 0) <=> ($left->starts_at?->timestamp ?? 0);

                return $start !== 0 ? $start : $right->id <=> $left->id;
            })
            ->first();
    }

    private function fromRecord(Product $product, PriceSource $source, string $price, string $discountPercent, bool $includesVat, int $minimumQuantity, string $documentDiscount): PriceQuote
    {
        $discounted = Discount::apply($price, $discountPercent);
        $split = Vat::split($discounted, $product->vat_rate, $includesVat);

        return new PriceQuote(
            source: $source,
            unitPrice: $price,
            discountPercent: Discount::of($discountPercent),
            discountedPrice: $discounted,
            includesVat: $includesVat,
            net: $split['net'],
            vat: $split['vat'],
            gross: $split['gross'],
            documentDiscountPercent: $documentDiscount,
            minimumQuantity: $minimumQuantity,
        );
    }

    private function activeList(?Dealer $dealer): ?PriceList
    {
        $list = $dealer?->priceList;

        if ($list === null || ! $list->is_active) {
            return null;
        }

        return $list;
    }

    public function documentDiscountPercent(?Dealer $dealer): string
    {
        return $this->documentDiscount($dealer);
    }

    private function documentDiscount(?Dealer $dealer): string
    {
        $list = $this->activeList($dealer);

        return Discount::of($list?->document_discount_percent ?? '0');
    }

    private function day(?CarbonInterface $on): CarbonInterface
    {
        return ($on ?? Carbon::now())->timezone(config('app.timezone'))->startOfDay();
    }
}
