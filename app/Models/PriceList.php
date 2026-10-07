<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'document_discount_percent',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PriceListItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    /**
     * @return HasMany<Dealer, $this>
     */
    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }
}
