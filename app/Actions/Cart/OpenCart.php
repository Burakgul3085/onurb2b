<?php

namespace App\Actions\Cart;

use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\User;

class OpenCart
{
    public function for(User $user): Cart
    {
        if ($user->dealer_id === null) {
            throw new CartException(__('This cart belongs to another company.'));
        }

        $cart = Cart::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['dealer_id' => $user->dealer_id],
        );

        if ($cart->dealer_id !== $user->dealer_id) {
            throw new CartException(__('This cart belongs to another company.'));
        }

        return $cart;
    }
}
