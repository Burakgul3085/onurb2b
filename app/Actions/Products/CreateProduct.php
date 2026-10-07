<?php

namespace App\Actions\Products;

use App\Data\Products\ProductData;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(private SyncProductBarcodes $syncProductBarcodes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            $productData = ProductData::fromArray($data);
            $attributes = $productData->toAttributes();
            $attributes['is_active'] = true;

            if ($image !== null) {
                $attributes['image_path'] = $image->store('products', 'public');
            }

            $product = Product::query()->create($attributes);
            $this->syncProductBarcodes->execute($product, $productData->barcodes);

            return $product;
        });
    }
}
