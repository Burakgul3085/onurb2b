<?php

namespace App\Actions\Cart;

use App\Exceptions\CartException;
use App\Models\CartItem;

class UpdateCartItem
{
    public function execute(CartItem $item, int $quantity): CartItem
    {
        $item->loadMissing('product.unit');

        if ($item->product === null || ! $item->product->is_active) {
            throw new CartException(__('This product is not for sale.'));
        }

        if ($quantity < 1 || $quantity > AddCartItem::MAX_QUANTITY) {
            throw new CartException(__('Enter a quantity between 1 and :max.', ['max' => AddCartItem::MAX_QUANTITY]));
        }

        $each = $item->product->unit->pieces();

        if ($each < 1 || $quantity > intdiv(1_000_000_000, $each)) {
            throw new CartException(__('The quantity is too large.'));
        }

        $item->quantity = $quantity;
        $item->save();

        return $item;
    }
}
