<?php

namespace App\Actions\Cart;

use App\Models\CartItem;

class RemoveCartItem
{
    public function execute(CartItem $item): void
    {
        $item->delete();
    }
}
