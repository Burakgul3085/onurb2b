<?php

namespace App\Models;

use App\Enums\VatRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'product_name',
        'unit_name',
        'quantity',
        'pieces_per_unit',
        'requested_pieces',
        'approved_pieces',
        'delivered_pieces',
        'unit_price',
        'discount_percent',
        'prices_include_vat',
        'vat_rate',
        'net',
        'vat',
        'gross',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'pieces_per_unit' => 'integer',
            'requested_pieces' => 'integer',
            'approved_pieces' => 'integer',
            'delivered_pieces' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'prices_include_vat' => 'boolean',
            'vat_rate' => VatRate::class,
            'net' => 'decimal:2',
            'vat' => 'decimal:2',
            'gross' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
