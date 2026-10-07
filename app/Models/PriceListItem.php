<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'price_list_id',
        'product_id',
        'price',
        'minimum_quantity',
        'starts_at',
        'ends_at',
        'prices_include_vat',
        'discount_percent',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'minimum_quantity' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'prices_include_vat' => 'boolean',
            'discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PriceList, $this>
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
