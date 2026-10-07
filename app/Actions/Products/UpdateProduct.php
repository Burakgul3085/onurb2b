<?php

namespace App\Actions\Products;

use App\Data\Products\ProductData;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateProduct
{
    public function __construct(private SyncProductBarcodes $syncProductBarcodes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $image) {
            $productData = ProductData::fromArray($data);
            $attributes = $productData->toAttributes();

            if (array_key_exists('is_active', $data)) {
                $attributes['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            if ($image !== null) {
                if ($product->image_path !== null) {
                    Storage::disk('public')->delete($product->image_path);
                }

                $attributes['image_path'] = $image->store('products', 'public');
            }

            $product->update($attributes);
            $this->syncProductBarcodes->execute($product, $productData->barcodes);

            return $product;
        });
    }
}
