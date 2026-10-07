<?php

namespace App\Data\Products;

use App\Enums\VatRate;
use App\Support\Money\Money;

readonly class ProductData
{
    /**
     * @param  list<string>  $barcodes
     */
    public function __construct(
        public string $sku,
        public string $name,
        public int $brandId,
        public int $categoryId,
        public int $unitId,
        public VatRate $vatRate,
        public string $purchasePrice,
        public string $salePrice,
        public bool $pricesIncludeVat,
        public int $minimumStock,
        public int $criticalStock,
        public ?string $description,
        public array $barcodes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $vatRate = $data['vat_rate'];
        $description = $data['description'] ?? null;

        return new self(
            sku: trim((string) $data['sku']),
            name: trim((string) $data['name']),
            brandId: (int) $data['brand_id'],
            categoryId: (int) ($data['subcategory_id'] ?: $data['category_id']),
            unitId: (int) $data['unit_id'],
            vatRate: $vatRate instanceof VatRate ? $vatRate : VatRate::from((string) $vatRate),
            purchasePrice: Money::of((string) $data['purchase_price']),
            salePrice: Money::of((string) $data['sale_price']),
            pricesIncludeVat: filter_var($data['prices_include_vat'] ?? false, FILTER_VALIDATE_BOOLEAN),
            minimumStock: (int) $data['minimum_stock'],
            criticalStock: (int) $data['critical_stock'],
            description: is_string($description) && trim($description) !== '' ? trim($description) : null,
            barcodes: self::barcodes($data['barcodes'] ?? ''),
        );
    }

    /**
     * @return list<string>
     */
    public static function barcodes(mixed $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];

        return collect($lines)
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'brand_id' => $this->brandId,
            'category_id' => $this->categoryId,
            'unit_id' => $this->unitId,
            'vat_rate' => $this->vatRate,
            'purchase_price' => $this->purchasePrice,
            'sale_price' => $this->salePrice,
            'prices_include_vat' => $this->pricesIncludeVat,
            'minimum_stock' => $this->minimumStock,
            'critical_stock' => $this->criticalStock,
            'description' => $this->description,
        ];
    }
}
