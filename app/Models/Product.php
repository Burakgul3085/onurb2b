<?php

namespace App\Models;

use App\Enums\VatRate;
use App\Support\Money\Vat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'brand_id',
        'category_id',
        'unit_id',
        'vat_rate',
        'purchase_price',
        'sale_price',
        'prices_include_vat',
        'minimum_stock',
        'critical_stock',
        'description',
        'image_path',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vat_rate' => VatRate::class,
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'prices_include_vat' => 'boolean',
            'minimum_stock' => 'integer',
            'critical_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<ProductBarcode, $this>
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * @return array{net: string, vat: string, gross: string}
     */
    public function saleBreakdown(): array
    {
        return Vat::split($this->sale_price, $this->vat_rate, $this->prices_include_vat);
    }

    /**
     * @return array{net: string, vat: string, gross: string}
     */
    public function purchaseBreakdown(): array
    {
        return Vat::split($this->purchase_price, $this->vat_rate, $this->prices_include_vat);
    }
}
