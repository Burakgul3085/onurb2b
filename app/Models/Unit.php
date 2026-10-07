<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Unit extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'multiplier',
        'parent_id',
        'is_base',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'multiplier' => 'integer',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function pieces(int $depth = 0): int
    {
        if ($depth > 10) {
            throw new RuntimeException('Unit conversion is too deep.');
        }

        if ($this->parent_id === null) {
            return $this->multiplier;
        }

        $this->loadMissing('parent');

        return $this->multiplier * $this->parent->pieces($depth + 1);
    }
}
