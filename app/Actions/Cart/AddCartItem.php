<?php

namespace App\Actions\Cart;

use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

class AddCartItem
{
    public const MAX_QUANTITY = 100000;

    public function __construct(private OpenCart $openCart) {}

    public function execute(User $user, Product $product, int $quantity): CartItem
    {
        $this->guard($product, $quantity);
        $cart = $this->openCart->for($user);
        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);
        $next = ($item->exists ? $item->quantity : 0) + $quantity;

        if ($next > self::MAX_QUANTITY) {
            throw new CartException(__('The quantity is too large.'));
        }

        $this->guardPieces($product, $next);
        $item->quantity = $next;
        $item->save();

        return $item;
    }

    private function guard(Product $product, int $quantity): void
    {
        if (! $product->is_active) {
            throw new CartException(__('This product is not for sale.'));
        }

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new CartException(__('Enter a quantity between 1 and :max.', ['max' => self::MAX_QUANTITY]));
        }
    }

    private function guardPieces(Product $product, int $quantity): void
    {
        $product->loadMissing('unit');
        $each = $product->unit->pieces();

        if ($each < 1 || $quantity > intdiv(1_000_000_000, $each)) {
            throw new CartException(__('The quantity is too large.'));
        }
    }
}
