<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StockLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function holdsStock(): bool
    {
        return $this->levels()
            ->where(function ($query) {
                $query->where('physical_stock', '>', 0)
                    ->orWhere('reserved_stock', '>', 0);
            })
            ->exists();
    }
}
