<?php

namespace App\Actions\Orders;

use App\Actions\Mail\Notify;
use App\Enums\District;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Cart;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;

class PlaceOrder
{
    public function __construct(private CartCalculator $calculator) {}

    /**
     * @param  array{delivery_address: string, district: string, note?: string|null}  $data
     */
    public function execute(User $user, array $data): Order
    {
        $dealer = $user->dealer;

        if ($dealer === null || $user->dealer_id === null) {
            throw new OrderException(__('This cart belongs to another company.'));
        }

        return DB::transaction(function () use ($user, $dealer, $data) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('dealer_id', $dealer->id)
                ->lockForUpdate()
                ->first();

            $summary = $this->calculator->summarize($cart, $dealer);
            $sellable = array_values(array_filter($summary->lines, fn ($line) => $line->sellable));

            if ($sellable === []) {
                throw new OrderException(__('The cart is empty.'));
            }

            if (count($sellable) !== count($summary->lines)) {
                throw new OrderException(__('Remove products that are not for sale before sending the order.'));
            }

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'dealer_id' => $dealer->id,
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'province' => Dealer::PROVINCE,
                'district' => District::from($data['district']),
                'delivery_address' => trim($data['delivery_address']),
                'note' => isset($data['note']) && trim((string) $data['note']) !== '' ? trim((string) $data['note']) : null,
                'document_discount_percent' => $summary->documentDiscountPercent,
                'net' => $summary->net,
                'vat' => $summary->vat,
                'gross' => $summary->gross,
                'discount_amount' => $summary->discountAmount,
                'payable' => $summary->payable,
            ]);

            foreach ($sellable as $line) {
                $product = $line->item->product;
                $quote = $line->quote;

                $order->lines()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'unit_name' => $product->unit->name,
                    'quantity' => $line->item->quantity,
                    'pieces_per_unit' => $product->unit->pieces(),
                    'requested_pieces' => $line->pieces,
                    'approved_pieces' => 0,
                    'delivered_pieces' => 0,
                    'unit_price' => $quote->unitPrice,
                    'discount_percent' => $quote->discountPercent,
                    'prices_include_vat' => $quote->includesVat,
                    'vat_rate' => $product->vat_rate,
                    'net' => $line->net,
                    'vat' => $line->vat,
                    'gross' => $line->gross,
                ]);
            }

            $cart?->items()->delete();

            DB::afterCommit(fn () => app(Notify::class)->orderPlaced($order->id));

            return $order->load('lines');
        });
    }

    private function nextNumber(): string
    {
        $prefix = 'SP-'.now()->year.'-';
        $last = Order::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');
        $sequence = $last === null ? 1 : ((int) substr((string) $last, -5)) + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
