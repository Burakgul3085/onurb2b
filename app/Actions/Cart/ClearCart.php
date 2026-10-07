<?php

namespace App\Actions\Cart;

use App\Models\Cart;

class ClearCart
{
    public function execute(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
