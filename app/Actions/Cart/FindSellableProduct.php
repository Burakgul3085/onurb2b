<?php

namespace App\Actions\Cart;

use App\Exceptions\CartException;
use App\Models\Product;

class FindSellableProduct
{
    public function byCode(string $code): Product
    {
        $code = trim($code);
        $product = Product::query()->where('sku', $code)->first()
            ?? Product::query()->whereHas('barcodes', fn ($query) => $query->where('barcode', $code))->first();

        if ($product === null || ! $product->is_active) {
            throw new CartException(__('No active product matches this code.'));
        }

        return $product;
    }
}
