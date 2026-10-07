<?php

namespace App\Actions\Products;

use App\Models\Product;

class SyncProductBarcodes
{
    /**
     * @param  list<string>  $barcodes
     */
    public function execute(Product $product, array $barcodes): void
    {
        if ($barcodes === []) {
            $product->barcodes()->delete();

            return;
        }

        $product->barcodes()->whereNotIn('barcode', $barcodes)->delete();

        foreach ($barcodes as $barcode) {
            $product->barcodes()->firstOrCreate(['barcode' => $barcode]);
        }
    }
}
